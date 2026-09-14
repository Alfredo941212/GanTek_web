@extends('layouts.app')
@section('title', 'Ganado | GanTek')
@section('heading', 'Registrar · Ganado')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('ganado.store') }}">

        @include('cattle._form')
    </form>
</div>
@endsection
