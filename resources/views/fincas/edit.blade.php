@extends('layouts.app')
@section('title', 'Fincas | GanTek')
@section('heading', 'Editar · Fincas')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('fincas.update', $finca) }}">
        @method('PUT')
        @include('fincas._form')
    </form>
</div>
@endsection
