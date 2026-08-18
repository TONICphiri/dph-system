@extends('layouts.app')

@section('content')
<div class="container mx-4 py-8">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h4 class="mb-0">Pharmacy - {{ $patient->full_name }}</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">Dispense medication and update inventory</p>
                    
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    @if(empty($prescriptions))
                        <div class="alert alert-info">
                            No prescriptions found for this patient.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Medication</th>
                                        <th>Strength</th>
                                        <th>Quantity Prescribed</th>
                                        <th>Quantity Dispensed</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($prescriptions as $prescription)
                                        <tr>
                                            <td>{{ $prescription->medication_name }}</td>
                                            <td>{{ $prescription->dose }}</td>
                                            <td>{{ $prescription->quantity }}</td>
                                            <td>{{ $prescription->status === 'dispensed' ? $prescription->quantity : 'Not dispensed' }}</td>
                                            <td>
                                                @if($prescription->status !== 'dispensed')
                                                    <form action="{{ route('pharmacy.dispense', ['patient' => $patient->id, 'prescription_id' => $prescription->id]) }}" method="POST" class="d-inline">
                                                        @method('POST')
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary btn-sm">
                                                            <i class="bi bi-droplet me-1"></i> Dispense
                                                        </button>
                                                    </form>
                                                @else
                                                    <span class="btn btn-secondary btn-sm">
                                                        <i class="bi bi-check me-1"></i> Dispensed
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        <h5>Inventory Stock Levels</h5>
                        <div class="row mt-4">
                            @foreach($inventory as $item)
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body">
                                            <h5 class="card-title">{{ $item->medication_name }}</h5>
                                            <p class="card-text">Stock: {{ $item->current_stock }}</p>
                                            @if($item->current_stock < 10)
                                                <p class="text-danger">Low stock!</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    
                    <div class="d-grid gap-2 mt-4">
                        <a href="{{ route('patients.show', $patient) }}" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-2"></i> Back to Patient
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection