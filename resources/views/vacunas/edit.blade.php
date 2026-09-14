@extends('layouts.app')
@section('title', 'Catálogo de vacunas | GanTek')
@section('heading', 'Editar · Catálogo de vacunas')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('vacunas.update', $vacuna) }}">
        @method('PUT')
        @include('vacunas._form')
    </form>
</div>
@endsection
