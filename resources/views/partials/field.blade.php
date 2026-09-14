@php($fieldValue = old($name, $value ?? ''))
<div @class(['full-row' => ($type ?? 'text') === 'textarea'])>
    <label for="{{ $name }}">{{ $label }}{{ ($required ?? false) ? ' *' : '' }}</label>
    @if(($type ?? 'text') === 'select')
        <select id="{{ $name }}" name="{{ $name }}" @required($required ?? false)>
            <option value="">Seleccione</option>
            @foreach($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $fieldValue === (string) $optionValue)>{{ $optionLabel }}</option>
            @endforeach
        </select>
    @elseif(($type ?? 'text') === 'textarea')
        <textarea id="{{ $name }}" name="{{ $name }}" rows="4">{{ $fieldValue }}</textarea>
    @else
        <input id="{{ $name }}" name="{{ $name }}" type="{{ $type ?? 'text' }}" value="{{ $fieldValue }}"
            @required($required ?? false)
            @if(isset($step)) step="{{ $step }}" @endif
            @if(isset($min)) min="{{ $min }}" @endif
            @if(isset($max)) max="{{ $max }}" @endif>
    @endif
</div>
