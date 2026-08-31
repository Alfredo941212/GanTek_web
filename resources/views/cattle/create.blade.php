@extends('layouts.app')
@section('title', 'Registrar ganado | GanTek')
@section('heading', 'Registrar animal')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('ganado.store') }}">
        @include('cattle._form')
    </form>
</div>
@endsection
