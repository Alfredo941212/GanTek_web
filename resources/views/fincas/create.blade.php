@extends('layouts.app')
@section('title', 'Fincas | GanTek')
@section('heading', 'Registrar · Fincas')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('fincas.store') }}">

        @include('fincas._form')
    </form>
</div>
@endsection
