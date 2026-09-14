@extends('layouts.app')
@section('title', 'Lotes | GanTek')
@section('heading', 'Registrar · Lotes')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('lotes.store') }}">

        @include('lotes._form')
    </form>
</div>
@endsection
