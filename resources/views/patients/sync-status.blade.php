<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Sync Status') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if ($message = Session::get('success'))
                <div class="mb-4 px-4 py-3 rounded bg-green-100 border border-green-400 text-green-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif
            @if ($message = Session::get('error'))
                <div class="mb-4 px-4 py-3 rounded bg-red-100 border border-red-400 text-red-700">
                    <strong>{{ $message }}</strong>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Synced</p>
                            <p class="text-2xl font-bold text-green-600">{{ $syncedCount }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Pending</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $pendingCount }}</p>
                        </div>
                        <div class="text-center">
                            <p class="text-sm text-gray-500">Failed</p>
                            <p class="text-2xl font-bold text-red-600">{{ $failedCount }}</p>
                        </div>
                    </div>

                    @if($syncQueue->isNotEmpty())
                        <h5 class="text-lg font-semibold mb-3">Recent Sync Queue</h5>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2">Record Type</th>
                                        <th class="px-4 py-2">Record ID</th>
                                        <th class="px-4 py-2">Status</th>
                                        <th class="px-4 py-2">Created At</th>
                                        <th class="px-4 py-2">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y">
                                    @foreach($syncQueue as $item)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                            <td class="px-4 py-2">{{ ucfirst($item->record_type) }}</td>
                                            <td class="px-4 py-2">{{ $item->record_id }}</td>
                                            <td class="px-4 py-2">
                                                @if($item->status === 'synced')
                                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Synced</span>
                                                @elseif($item->status === 'pending')
                                                    <span class="px-2 py-1 text-xs rounded bg-blue-100 text-blue-800">Pending</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs rounded bg-red-100 text-red-800">Failed</span>
                                                @endif
                                            </td>
                                            <td class="px-4 py-2">{{ $item->created_at->format('M d, H:i') }}</td>
                                            <td class="px-4 py-2">
                                                @if($item->status === 'failed' && $item->canRetry())
                                                    <form method="POST" action="{{ route('sync.retry', $item) }}">
                                                        @csrf
                                                        <button type="submit" class="px-3 py-1 text-xs rounded bg-blue-600 text-white hover:bg-blue-700">Retry</button>
                                                    </form>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="bg-blue-50 text-blue-800 px-4 py-3 rounded mb-4">
                            No sync queue items.
                        </div>
                    @endif

                    <div class="mt-4">
                        <a href="{{ route('dashboard') }}" class="text-gray-600 hover:underline text-sm">
                            &larr; Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>