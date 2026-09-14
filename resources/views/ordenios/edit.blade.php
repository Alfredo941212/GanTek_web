@extends('layouts.app')
@section('title', 'Registros de ordeño | GanTek')
@section('heading', 'Editar · Registros de ordeño')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('ordenios.update', $ordenio) }}">
        @method('PUT')
        @include('ordenios._form')
    </form>
</div>
@endsection
