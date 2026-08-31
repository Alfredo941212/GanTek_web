@extends('layouts.app')
@section('title', 'Editar ganado | GanTek')
@section('heading', 'Editar animal')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('ganado.update', $cattle) }}">
        @method('PUT')
        @include('cattle._form')
    </form>
</div>
@endsection
