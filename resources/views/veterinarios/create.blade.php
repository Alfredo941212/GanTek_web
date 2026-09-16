@extends('layouts.app')
@section('title', 'Veterinarios | GanTek')
@section('heading', 'Registrar · Veterinarios')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('veterinarios.store') }}">

        @include('veterinarios._form')
    </form>
</div>
@endsection
