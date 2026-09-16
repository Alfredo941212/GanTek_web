@extends('layouts.app')
@section('title', 'Registros de ordeño | GanTek')
@section('heading', 'Registrar · Registros de ordeño')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('ordenios.store') }}">

        @include('ordenios._form')
    </form>
</div>
@endsection
