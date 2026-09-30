import { Html5Qrcode } from 'html5-qrcode';

/**
 * QR scanner for Digital Health Passport verification pages.
 * The camera starts only after the user presses "Start scanner".
 * Only same-origin /verify/by-token/{64-char token} codes (or a bare
 * 64-character token) are ever followed; anything else shows a generic
 * error and is never sent to the server.
 */
function extractVerifyToken(text) {
    const value = (text || '').trim();

    if (/^[A-Za-z0-9]{64}$/.test(value)) return value;

    try {
        const url = new URL(value);
        if (url.origin !== window.location.origin) return null;
        const match = url.pathname.match(/^\/verify\/by-token\/([A-Za-z0-9]{64})$/)
            || url.pathname.match(/^\/verifier\/by-token\/([A-Za-z0-9]{64})$/);
        return match ? match[1] : null;
    } catch {
        return null;
    }
}

window.dhpExtractVerifyToken = extractVerifyToken;

window.dhpVerifyScannerInit = function (options) {
    const region = document.getElementById(options.readerId);
    const status = document.getElementById(options.statusId);
    const startButton = document.getElementById(options.startId);
    const stopButton = document.getElementById(options.stopId);

    if (!region || !startButton) return;

    const scanner = new Html5Qrcode(options.readerId);
    let running = false;

    const setStatus = (text) => { if (status) status.textContent = text; };

    const stop = async () => {
        if (!running) return;
        try { await scanner.stop(); } catch { /* already stopped */ }
        running = false;
        startButton.disabled = false;
        if (stopButton) stopButton.disabled = true;
    };

    startButton.addEventListener('click', async () => {
        if (running) return;
        try {
            await scanner.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: { width: 220, height: 220 } },
                async (decoded) => {
                    const token = extractVerifyToken(decoded);
                    if (!token) {
                        setStatus('This QR code is not a Digital Health Passport credential. Try again or enter the credential number.');
                        return;
                    }
                    await stop();
                    setStatus('Code read. Opening verification.');
                    window.location.href = options.buildUrl(token);
                },
                () => {},
            );
            running = true;
            startButton.disabled = true;
            if (stopButton) stopButton.disabled = false;
            setStatus('Hold the QR certificate in front of the camera.');
        } catch {
            setStatus('Camera scanning is unavailable. Enter the credential number instead.');
        }
    });

    stopButton?.addEventListener('click', async () => {
        await stop();
        setStatus('Scanner stopped.');
    });
};
