@extends('layouts.app')
@section('title', 'Catálogo de vacunas | GanTek')
@section('heading', 'Registrar · Catálogo de vacunas')
@section('content')
<div class="panel form-panel">
    <form method="POST" action="{{ route('vacunas.store') }}">

        @include('vacunas._form')
    </form>
</div>
@endsection
