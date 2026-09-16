@extends('layouts.app')
@section('title', 'Veterinarios | GanTek')
@section('heading', 'Editar · Veterinarios')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('veterinarios.update', $veterinario) }}">
        @method('PUT')
        @include('veterinarios._form')
    </form>
</div>
@endsection
