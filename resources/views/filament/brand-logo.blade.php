@php
    $user = filament()->auth()->user();
@endphp

@if ($user)
    {{-- Usuario autenticado → logo + nombre en horizontal --}}
    <div class="flex items-center gap-2">
        <img src="{{ asset('assets/logo.png') }}" width="48" height="48" alt="Logo">
        <span class="text-lg font-semibold">
            {{ config('app.name') }}
        </span>
    </div>
@else
    {{-- No autenticado → diseño original del login --}}
    <div class="mx-auto">
        <img width="48" height="48" class="mx-auto" src="{{ asset('assets/logo.png') }}" alt="Logo">
        <span class="text-lg font-semibold mx-auto">
            {{ config('app.name') }}
        </span>
    </div>
@endif
