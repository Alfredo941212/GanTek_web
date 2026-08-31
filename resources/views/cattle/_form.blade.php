@csrf
<div class="form-grid">
    <div><label>Código / Arete *</label><input name="code" value="{{ old('code', $cattle->code ?? '') }}" required></div>
    <div><label>Nombre</label><input name="name" value="{{ old('name', $cattle->name ?? '') }}"></div>

    <div><label>Sexo *</label>
        <select name="sex" required>
            <option value="">Seleccione</option>
            @foreach(['Macho','Hembra'] as $sex)
                <option value="{{ $sex }}" @selected(old('sex', $cattle->sex ?? '') === $sex)>{{ $sex }}</option>
            @endforeach
        </select>
    </div>

    <div><label>Raza</label><input name="breed" value="{{ old('breed', $cattle->breed ?? '') }}"></div>
    <div><label>Fecha de ingreso *</label><input type="date" name="entry_date" value="{{ old('entry_date', isset($cattle) ? $cattle->entry_date?->format('Y-m-d') : '') }}" required></div>
    <div><label>Peso inicial (kg)</label><input type="number" step="0.01" name="initial_weight" value="{{ old('initial_weight', $cattle->initial_weight ?? '') }}"></div>
    <div><label>Lote</label><input name="lot" value="{{ old('lot', $cattle->lot ?? '') }}"></div>
    <div><label>Corral</label><input name="corral" value="{{ old('corral', $cattle->corral ?? '') }}"></div>

    <div><label>Estado *</label>
        <select name="status" required>
            @foreach(['Disponible','Vendido'] as $status)
                <option value="{{ $status }}" @selected(old('status', $cattle->status ?? 'Disponible') === $status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>

    <div class="full-row"><label>Observaciones</label><textarea name="observations" rows="4">{{ old('observations', $cattle->observations ?? '') }}</textarea></div>
</div>
<button class="btn primary" type="submit">Guardar</button>
<a class="btn" href="{{ route('ganado.index') }}">Cancelar</a>
