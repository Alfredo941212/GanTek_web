@extends('layouts.app')
@section('title', 'Lotes | GanTek')
@section('heading', 'Editar · Lotes')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('lotes.update', $lote) }}">
        @method('PUT')
        @include('lotes._form')
    </form>
</div>
@endsection
