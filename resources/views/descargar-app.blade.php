
@extends('layouts.app')

@section('title', 'Descargar aplicación | GanTek')
@section('heading', 'Descargar aplicación')
@section('subheading', 'Lleva la gestión de tu ganado a tu dispositivo Android.')

@push('styles')
<style>
    .apk-container {
        max-width: 1050px;
        margin: 0 auto;
    }

    .apk-hero {
        background: #ffffff;
        border: 1px solid #e1ebe5;
        border-radius: 18px;
        padding: 45px 25px;
        text-align: center;
    }

    .apk-icon {
        width: 76px;
        height: 76px;
        background: #e5f3e9;
        color: #176b48;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
    }

    .apk-hero h2 {
        color: #164d38;
        font-size: 30px;
        margin-bottom: 15px;
    }

    .apk-description {
        max-width: 600px;
        margin: 0 auto 25px;
        line-height: 1.7;
        color: #65756b;
    }

    .apk-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 15px 28px;
        border-radius: 10px;
        background: #176b48;
        color: white;
        font-weight: 600;
        text-decoration: none;
    }

    .apk-btn:hover {
        background: #105235;
        color: white;
    }

    .apk-btn.disabled {
        background: #89968e;
        cursor: not-allowed;
    }

    .apk-note {
        font-size: 13px;
        color: #6d7d71;
        margin-top: 15px;
    }

    .apk-features {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 18px;
        margin-top: 22px;
    }

    .apk-card,
    .apk-steps {
        background: #fff;
        padding: 25px;
        border: 1px solid #e1ebe5;
        border-radius: 14px;
    }

    .apk-card h3,
    .apk-steps h3 {
        color: #176b48;
        margin-bottom: 12px;
    }

    .apk-card p,
    .apk-steps li {
        color: #65756b;
        line-height: 1.7;
    }

    .apk-steps {
        margin-top: 22px;
    }

    .apk-steps ol {
        padding-left: 22px;
    }

    @media (max-width: 768px) {
        .apk-features {
            grid-template-columns: 1fr;
        }

        .apk-hero {
            padding: 30px 18px;
        }

        .apk-hero h2 {
            font-size: 24px;
        }
    }
</style>
@endpush

@section('content')
<div class="apk-container">

    <section class="apk-hero">
        <div class="apk-icon">
            <svg width="40" height="40"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="1.7"
                stroke-linecap="round"
                stroke-linejoin="round"
                aria-hidden="true">
                <rect x="6" y="2" width="12" height="20" rx="2"/>
                <path d="M11 18h2"/>
                <path d="M12 6v8"/>
                <path d="m9 11 3 3 3-3"/>
            </svg>
        </div>

        <h2>GanTek para Android</h2>

        <p class="apk-description">
            Lleva el control de tu ganado a cualquier lugar.
            Consulta tus registros, administra la producción
            de leche y organiza tus actividades desde
            tu dispositivo móvil.
        </p>

        @if ($apkDisponible)
            <a href="{{ asset('descargas/GanTek.apk') }}"
                class="apk-btn"
                download="GanTek.apk">
                Descargar APK para Android
            </a>
            <p class="apk-note">
                Descarga directa desde GanTek.
            </p>
        @else
            <span class="apk-btn disabled">
                Próximamente disponible
            </span>
            <p class="apk-note">
                Estamos preparando la aplicación para su descarga.
            </p>
        @endif
    </section>

    <section class="apk-features">
        <article class="apk-card">
            <h3>Gestión ganadera</h3>
            <p>
                Consulta los datos de los animales
                registrados en tu finca.
            </p>
        </article>

        <article class="apk-card">
            <h3>Producción de leche</h3>
            <p>
                Registra y consulta la producción
                lechera de tu ganado.
            </p>
        </article>

        <article class="apk-card">
            <h3>Agenda de actividades</h3>
            <p>
                Consulta tus tareas y actividades
                pendientes.
            </p>
        </article>
    </section>

    <section class="apk-steps">
        <h3>¿Cómo instalar GanTek?</h3>

        <ol>
            <li>Descarga el archivo APK desde esta página.</li>
            <li>Abre el archivo descargado en tu teléfono Android.</li>
            <li>
                Si Android lo solicita, autoriza la instalación
                de aplicaciones desde esa fuente.
            </li>
            <li>Confirma la instalación y abre GanTek.</li>
        </ol>
    </section>

</div>
@endsection
