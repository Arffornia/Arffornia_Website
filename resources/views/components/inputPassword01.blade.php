@props(['id', 'name', 'placeholder', 'value' => null])

<input
    class="inputTextContainer"
    
    id="{{ $id }}" 
    type="password" 
    name="{{ $name }}" 
    placeholder="{{ $placeholder }}"
    value="{{ $value }}"
>