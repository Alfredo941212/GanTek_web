
@extends('layouts.app')

@section('title', 'Mi perfil | GanTek')
@section('heading', 'Mi perfil')
@section('subheading', 'Consulta y actualiza tu información personal.')

@section('content')

<div class="profile-page">

    <div class="profile-card">

        <div class="profile-header">
            
            <div class="profile-photo-container">

                <label for="avatar-input" class="profile-photo-label">

                    @if($user->avatar)
                        <img
                            id="profile-preview"
                            src="{{ str_starts_with($user->avatar, 'http') ? $user->avatar : asset('storage/' . $user->avatar) }}"
                            alt="Foto de perfil"
                            class="profile-photo"
                        >
                    @else
                        <span id="profile-initial" class="profile-photo-initial">
                            {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                        </span>
                        <img
                            id="profile-preview"
                            class="profile-photo"
                            alt="Vista previa de la foto"
                            style="display:none"
                        >
                    @endif

                    <span class="profile-photo-edit" aria-hidden="true">📷</span>
                </label>

                <input
                    type="file"
                    id="avatar-input"
                    name="avatar"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                    class="profile-file-input"
                >

                <small class="profile-photo-help">
                    Haz clic para cambiar tu foto. Máximo 2 MB.
                </small>

                @error('avatar')
                    <small class="profile-error">{{ $message }}</small>
                @enderror

            </div>


            <div>
                <h2>{{ $user->name }}</h2>
                <p>{{ $user->email }}</p>
                <span class="profile-status">
                    Cuenta activa
                </span>
            </div>
        </div>

        <form action="{{ route('profile.update') }}"method="POST" enctype="multipart/form-data">

            @csrf
            @method('PATCH')

            <div class="profile-grid">

                
                
                <div class="profile-field">
                    <label for="name">Nombre completo *</label>

                    <input
                        type="text"
                        id="name"
                        name="name"
                        value="{{ old('name', trim($user->name . ' ' . ($user->last_name ?? ''))) }}"
                        placeholder="Escribe tu nombre completo"
                        maxlength="255"
                        autocomplete="name"
                        required
                    >

                    @error('name')
                        <small class="profile-error">{{ $message }}</small>
                    @enderror
                </div>

                <div class="profile-field">
                    <label for="email">Correo electrónico</label>
                    <input
                        type="email"
                        id="email"
                        value="{{ $user->email }}"
                        readonly
                        aria-describedby="email-help"
                    >
                    <small id="email-help">
                        Tu correo está asociado al inicio de sesión.
                    </small>
                </div>

                <div class="profile-field">
                    <label for="phone">Número telefónico</label>
                    <input
                        type="tel"
                        id="phone"
                        name="phone"
                        value="{{ old('phone', $user->phone) }}"
                        maxlength="20"
                        autocomplete="tel"
                        placeholder="Ej. 9611234567"
                    >
                    @error('phone')
                        <small class="profile-error">{{ $message }}</small>
                    @enderror
                </div>

            </div>

            <div class="profile-security">
                <h3>Seguridad de la cuenta</h3>
                <p>
                    El correo electrónico y las credenciales
                    de Google o Facebook no se modificarán
                    al actualizar este perfil.
                </p>
            </div>

            <div class="profile-actions">
                <a href="{{ route('dashboard') }}"
                   class="profile-cancel">
                    Cancelar
                </a>

                <button type="submit" class="profile-save">
                    Guardar cambios
                </button>
            </div>

        </form>

    </div>

</div>

@endsection

@push('scripts')
<script>
document.getElementById('avatar-input')
    ?.addEventListener('change', function (event) {

        const file = event.target.files[0];

        if (!file) return;

        const allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp'
        ];

        if (!allowedTypes.includes(file.type) ||
            file.size > 2 * 1024 * 1024) {
            alert('Selecciona una imagen JPG, PNG o WEBP de máximo 2 MB.');
            event.target.value = '';
            return;
        }

        const preview = document.getElementById('profile-preview');
        const initial = document.getElementById('profile-initial');

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
</script>
@endpush

