@csrf
<div class="form-grid">
    <div>
        <label>Animal *</label>
        <select name="cattle_id" required>
            <option value="">Seleccione</option>
            @foreach($cattle as $item)
                <option value="{{ $item->id }}" @selected(old('cattle_id', $vacuna->cattle_id ?? '') == $item->id)>
                    {{ $item->code }} {{ $item->name ? '- '.$item->name : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div><label>Vacuna *</label><input name="vaccine_name" value="{{ old('vaccine_name', $vacuna->vaccine_name ?? '') }}" required></div>
    <div><label>Fecha de aplicación *</label><input type="date" name="application_date" value="{{ old('application_date', isset($vacuna) ? $vacuna->application_date?->format('Y-m-d') : '') }}" required></div>
    <div><label>Próxima aplicación</label><input type="date" name="next_date" value="{{ old('next_date', isset($vacuna) ? $vacuna->next_date?->format('Y-m-d') : '') }}"></div>
    <div class="full-row"><label>Notas</label><textarea name="notes" rows="4">{{ old('notes', $vacuna->notes ?? '') }}</textarea></div>
</div>
<button class="btn primary">Guardar</button>
<a class="btn" href="{{ route('vacunas.index') }}">Cancelar</a>
