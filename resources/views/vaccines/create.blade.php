@extends('layouts.app')
@section('title', 'Registrar vacuna | GanTek')
@section('heading', 'Registrar vacuna')
@section('content')
<div class="panel form-panel">
<form method="POST" action="{{ route('vacunas.store') }}">
    @include('vaccines._form')
</form>
</div>
@endsection
