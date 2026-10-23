
@extends('layouts.app')

@section('title', 'Editar perfil | GanTek')
@section('heading', 'Editar perfil')
@section('subheading', 'Actualiza tu información personal.')

@php
    $foto = $user->avatar
        ? (str_starts_with($user->avatar, 'http')
            ? $user->avatar
            : asset('storage/' . $user->avatar))
        : null;

  $nombreCompleto = trim($user->name);
        
@endphp

@section('content')

<div class="gantek-profile">

    <div class="gp-title">
        <a href="{{ route('dashboard') }}"
           class="gp-back" aria-label="Volver al inicio">
            ←
        </a>

        <div>
            <h2>Editar perfil</h2>
            <p>Actualiza tu información personal.</p>
        </div>
    </div>

    <form action="{{ route('profile.update') }}"
          method="POST"
          enctype="multipart/form-data"
          id="profile-form">

        @csrf
        @method('PATCH')

        <div class="gp-layout">

            {{-- TARJETA DEL USUARIO --}}
            <aside class="gp-card gp-summary">

                <div class="gp-cover"></div>

                <div class="gp-summary-body">

                    <div class="gp-avatar-area">

                        <label for="avatar-input"
                               class="gp-avatar-label"
                               title="Cambiar fotografía">

                            <span id="profile-initial"
                                  class="gp-avatar-initial"
                                  style="{{ $foto ? 'display:none' : '' }}">
                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                            </span>

                            <img
                                id="profile-preview"
                                src="{{ $foto ?? '' }}"
                                alt="Foto de perfil"
                                class="gp-avatar-image"
                                style="{{ $foto ? '' : 'display:none' }}"
                            >

                            <span class="gp-camera">📷</span>
                        </label>

                    </div>

                    <h2 class="gp-username">
                        {{ $nombreCompleto }}
                    </h2>

                    <span class="gp-role">
                        Ganadero
                    </span>

                    <div class="gp-status">
                        <span class="gp-status-icon">✓</span>
                        <div>
                            <strong>Cuenta activa</strong>
                            <small>
                                Tu cuenta está asociada
                                al inicio de sesión.
                            </small>
                        </div>
                    </div>

                    <div class="gp-detail">
                        <span class="gp-detail-icon">✉</span>
                        <div>
                            <small>Correo electrónico</small>
                            <span>{{ $user->email }}</span>
                        </div>
                    </div>

                    <div class="gp-detail">
                        <span class="gp-detail-icon">☎</span>
                        <div>
                            <small>Número telefónico</small>
                            <span>
                                {{ $user->phone ?: 'Sin registrar' }}
                            </span>
                        </div>
                    </div>

                    <div class="gp-detail">
                        <span class="gp-detail-icon">▦</span>
                        <div>
                            <small>Miembro desde</small>
                            <span>
                                {{ $user->created_at?->format('d/m/Y') ?? 'No disponible' }}
                            </span>
                        </div>
                    </div>

                </div>
            </aside>

            {{-- FORMULARIO --}}
            <section class="gp-card gp-form-card">

                <div class="gp-section-heading">
                    <span class="gp-section-icon">♙</span>
                    <div>
                        <h3>Información personal</h3>
                        <p>
                            Mantén tu información actualizada
                            para una mejor experiencia en GanTek.
                        </p>
                    </div>
                </div>

                <div class="gp-field">
                    <label for="name">
                        Nombre completo <span>*</span>
                    </label>

                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $nombreCompleto) }}"
                           maxlength="255"
                           minlength="2"
                           autocomplete="name"
                           placeholder="Ingresa tu nombre completo"
                           required>

                    @error('name')
                        <small class="gp-error">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

                <div class="gp-field">
                    <label for="email">
                        Correo electrónico
                    </label>

                    <div class="gp-email-container">
                        <input type="email"
                               id="email"
                               value="{{ $user->email }}"
                               readonly>

                        <span class="gp-readonly">
                            Solo lectura
                        </span>
                    </div>

                    <small class="gp-help">
                        🔒 El correo electrónico no se
                        puede modificar desde este perfil.
                    </small>
                </div>

                <div class="gp-field">
                    <label for="phone">
                        Número telefónico
                    </label>

                    <input type="tel"
                           id="phone"
                           name="phone"
                           value="{{ old('phone', $user->phone) }}"
                           maxlength="20"
                           autocomplete="tel"
                           placeholder="Ej. 9611234567">

                    <small class="gp-help">
                        Ingresa un número telefónico de contacto.
                    </small>

                    @error('phone')
                        <small class="gp-error">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

                <div class="gp-field">
                    <label for="avatar-input">
                        Foto de perfil
                    </label>

                    <input type="file"
                           id="avatar-input"
                           name="avatar"
                           accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                           class="gp-file-hidden">

                    <label for="avatar-input"
                           class="gp-upload">
                        <span class="gp-upload-icon">📷</span>
                        <span>
                            <strong>Cambiar foto</strong>
                            <small>
                                JPG, PNG o WEBP. Máximo 2 MB.
                            </small>
                        </span>
                    </label>

                    @error('avatar')
                        <small class="gp-error">
                            {{ $message }}
                        </small>
                    @enderror
                </div>

                <div class="gp-security">
                    <span class="gp-security-icon">🛡</span>

                    <div>
                        <h3>Seguridad de la cuenta</h3>
                        <p>
                            El correo electrónico y las credenciales
                            de Google o Facebook no se modificarán
                            al actualizar este perfil.
                        </p>
                    </div>
                </div>

                <div class="gp-actions">
                    <a href="{{ route('dashboard') }}"
                       class="gp-btn-cancel">
                        Cancelar
                    </a>

                    <button type="submit"
                            class="gp-btn-save">
                        Guardar cambios
                    </button>
                </div>

            </section>

        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('avatar-input');
    const preview = document.getElementById('profile-preview');
    const initial = document.getElementById('profile-initial');

    if (!input || !preview) return;

    input.addEventListener('change', function () {
        const file = this.files[0];

        if (!file) return;

        const allowed = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (
            !allowed.includes(file.type) ||
            file.size > 2 * 1024 * 1024
        ) {
            alert(
                'Selecciona una imagen JPG, PNG o WEBP de máximo 2 MB.'
            );
            this.value = '';
            return;
        }

        if (preview.dataset.objectUrl) {
            URL.revokeObjectURL(preview.dataset.objectUrl);
        }

        const url = URL.createObjectURL(file);

        preview.dataset.objectUrl = url;
        preview.src = url;
        preview.style.display = 'block';

        if (initial) {
            initial.style.display = 'none';
        }
    });
});
</script>
@endpush
