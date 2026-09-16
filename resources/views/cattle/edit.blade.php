@extends('layouts.app')
@section('title', 'Ganado | GanTek')
@section('heading', 'Editar · Ganado')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('ganado.update', $animal) }}">
        @method('PUT')
        @include('cattle._form')
    </form>
</div>
@endsection
