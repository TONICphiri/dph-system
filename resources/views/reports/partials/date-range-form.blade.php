<div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
    <div class="p-6 text-gray-900 dark:text-gray-100">
        <form method="GET" action="{{ $route }}" class="flex flex-wrap items-end gap-4">
            <div>
                <label for="from" class="block text-sm text-gray-500 mb-1">From</label>
                <input type="date" name="from" id="from" value="{{ $from?->toDateString() }}" class="px-4 py-2 border rounded">
            </div>
            <div>
                <label for="to" class="block text-sm text-gray-500 mb-1">To</label>
                <input type="date" name="to" id="to" value="{{ $to?->toDateString() }}" class="px-4 py-2 border rounded">
            </div>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Apply</button>
            <a href="{{ $route }}" class="px-4 py-2 text-sm text-gray-500 hover:text-gray-700">Reset</a>
        </form>
    </div>
</div>