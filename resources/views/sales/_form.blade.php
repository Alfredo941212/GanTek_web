@csrf
<div class="form-grid">
    <div>
        <label>Animal *</label>
        <select name="cattle_id" required>
            <option value="">Seleccione</option>
            @foreach($cattle as $item)
                <option value="{{ $item->id }}" @selected(old('cattle_id', $venta->cattle_id ?? '') == $item->id)>
                    {{ $item->code }} {{ $item->name ? '- '.$item->name : '' }}
                </option>
            @endforeach
        </select>
    </div>

    <div><label>Fecha de venta *</label><input type="date" name="sale_date" value="{{ old('sale_date', isset($venta) ? $venta->sale_date?->format('Y-m-d') : '') }}" required></div>
    <div><label>Peso vendido (kg) *</label><input type="number" step="0.01" min="0.01" name="weight" value="{{ old('weight', $venta->weight ?? '') }}" required></div>
    <div><label>Precio por kg *</label><input type="number" step="0.01" min="0.01" name="price_per_kg" value="{{ old('price_per_kg', $venta->price_per_kg ?? '') }}" required></div>
    <div><label>Comprador</label><input name="buyer" value="{{ old('buyer', $venta->buyer ?? '') }}"></div>
    <div class="full-row"><label>Notas</label><textarea name="notes" rows="4">{{ old('notes', $venta->notes ?? '') }}</textarea></div>
</div>
<button class="btn primary">Guardar</button>
<a class="btn" href="{{ route('ventas.index') }}">Cancelar</a>
