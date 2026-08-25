@php
    $acertos = $getRecord()->acertos;
    $hasResultado = $acertos !== null;
@endphp

@if($hasResultado)
    <div style="display: flex; align-items: center;">
        <div style="background-color: #4b5563; color: #e5e7eb; width: 2.5rem; height: 2.5rem; border-radius: 9999px; display: flex; align-items: center; justify-content: center; font-size: 0.875rem; font-weight: 700; box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);">
            {{ $acertos }}
        </div>
    </div>
@endif
