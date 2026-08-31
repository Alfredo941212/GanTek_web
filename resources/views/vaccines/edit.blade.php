@extends('layouts.app')
@section('title', 'Editar vacuna | GanTek')
@section('heading', 'Editar vacuna')
@section('content')
<div class="panel form-panel">
<form method="POST" action="{{ route('vacunas.update', $vacuna) }}">
    @method('PUT')
    @include('vaccines._form')
</form>
</div>
@endsection
