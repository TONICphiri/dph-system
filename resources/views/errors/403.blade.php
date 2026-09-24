@extends('errors.layout')
@section('code', '403')
@section('title', 'You do not have access to this page')
@section('message')
    @php $reason = isset($exception) ? $exception->getMessage() : ''; @endphp
    {{ $reason && $reason !== 'This action is unauthorized.' ? $reason : 'Your role does not allow this action.' }}
    If you need access, ask your Facility Administrator or the System Administrator.
@endsection
