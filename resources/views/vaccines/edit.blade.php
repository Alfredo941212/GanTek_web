@extends('layouts.app')
@section('title', 'Vacunaciones | GanTek')
@section('heading', 'Editar · Vacunaciones')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('vacunaciones.update', $vacunacion) }}">
        @method('PUT')
        @include('vaccines._form')
    </form>
</div>
@endsection
