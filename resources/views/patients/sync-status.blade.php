@extends('layouts.app')

@section('content')
<div class="container mx-4 py-8">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0">Sync Status</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="h4 text-primary">Synced</div>
                            <div class="h2 font-weight-bold">{{ $syncedCount }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="h4 text-success">Pending</div>
                            <div class="h2 font-weight-warning">{{ count($syncQueue) > 0 ? count($syncQueue) : 0 }}</div>
                        </div>
                        <div class="col-md-4">
                            <div class="h4 text-danger">Failed</div>
                            <div class="h2 font-weight-bold">{{ $failedCount }}</div>
                        </div>
                    </div>
                    
                    @if($syncQueue->isNotEmpty())
                        <h5>Recent Sync Queue</h5>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Record Type</th>
                                        <th>Record ID</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($syncQueue as $item)
                                        <tr>
                                            <td>{{ ucfirst($item->record_type) }}</td>
                                            <td>{{ $item->record_id }}</td>
                                            <td>
                                                @if($item->status === 'synced')
                                                    <span class="badge badge-success">Synced</span>
                                                @else
                                                    <span class="badge badge-danger">Failed</span>
                                                @endif
                                            </td>
                                            <td>{{ $item->created_at->format('M d, H:i') }}</td>
                                            <td>
                                                @if($item->status !== 'synced')
                                                    <a href="#" class="btn btn-sm btn-outline-primary">Retry</a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else>
                        <div class="alert alert-info">
                            No sync queue items.
                        </div>
                    @endif
                    
                    <div class="mt-4">
                        <a href="{{ route('patients.show', auth()->patient() ?? 1) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i> Back to Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection