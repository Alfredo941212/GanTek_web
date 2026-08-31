@extends('layouts.app')
@section('title', 'Editar venta | GanTek')
@section('heading', 'Editar venta')
@section('content')
<div class="panel form-panel">
<form method="POST" action="{{ route('ventas.update', $venta) }}">
    @method('PUT')
    @include('sales._form')
</form>
</div>
@endsection
