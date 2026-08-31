@extends('layouts.app')
@section('title', 'Registrar venta | GanTek')
@section('heading', 'Registrar venta')
@section('content')
<div class="panel form-panel">
<form method="POST" action="{{ route('ventas.store') }}">
    @include('sales._form')
</form>
</div>
@endsection
