@extends('layouts.app')
@section('title', 'Vacunaciones | GanTek')
@section('heading', 'Registrar · Vacunaciones')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('vacunaciones.store') }}">

        @include('vaccines._form')
    </form>
</div>
@endsection
