import Alpine from 'alpinejs';

/**
 * Notification bell. Asks the server for unread notifications at a fixed
 * interval and shows a desktop notification when a new one arrives, if the
 * user has allowed notifications in the browser.
 */
Alpine.data('notificationBell', (endpoint, intervalSeconds) => ({
    count: 0,
    items: [],
    open: false,
    seen: new Set(),
    firstLoad: true,

    init() {
        this.refresh();
        setInterval(() => this.refresh(), intervalSeconds * 1000);
    },

    async refresh() {
        try {
            const response = await fetch(endpoint, { headers: { Accept: 'application/json' } });
            if (!response.ok) return;

            const data = await response.json();
            this.count = data.count;
            this.items = data.latest;

            data.latest.forEach((item) => {
                if (!this.firstLoad && !this.seen.has(item.id)) this.showDesktopAlert(item);
                this.seen.add(item.id);
            });
            this.firstLoad = false;
        } catch {
            // A failed check is ignored. The next interval tries again.
        }
    },

    showDesktopAlert(item) {
        if (!('Notification' in window) || Notification.permission !== 'granted') return;
        const alert = new Notification(item.title, { body: item.message, icon: '/images/logo.png' });
        alert.onclick = () => { window.location.href = item.url; };
    },

    get canAskPermission() {
        return 'Notification' in window && Notification.permission === 'default';
    },

    askPermission() {
        Notification.requestPermission();
    },
}));

/**
 * Repeating rows, used for emergency contacts and prescription items.
 */
Alpine.data('repeater', (initialRows, blankRow, maxRows = 15) => ({
    rows: initialRows.length ? initialRows : [{ ...blankRow }],
    add() {
        if (this.rows.length < maxRows) this.rows.push({ ...blankRow });
    },
    remove(index) {
        if (this.rows.length > 1) this.rows.splice(index, 1);
    },
}));

/**
 * Mother search on the patient registration form.
 */
Alpine.data('motherSearch', (endpoint, selected) => ({
    term: '',
    results: [],
    selected,
    loading: false,
    failed: false,

    async search() {
        if (this.term.length < 2) { this.results = []; return; }
        this.loading = true;
        this.failed = false;
        try {
            const response = await fetch(`${endpoint}?search=${encodeURIComponent(this.term)}`, { headers: { Accept: 'application/json' } });
            if (!response.ok) throw new Error('Search failed');
            this.results = await response.json();
        } catch {
            this.failed = true;
        } finally {
            this.loading = false;
        }
    },

    choose(mother) {
        this.selected = mother;
        this.results = [];
        this.term = '';
        this.$dispatch('mother-selected', mother);
    },

    clear() {
        this.selected = null;
    },
}));

window.Alpine = Alpine;
Alpine.start();
