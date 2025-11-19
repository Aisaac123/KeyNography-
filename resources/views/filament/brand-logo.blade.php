@php
    $user = filament()->auth()->user();
@endphp

@if ($user)
    {{-- Usuario autenticado → logo + nombre en horizontal --}}
    <div class="flex items-center gap-2">
        {{-- Logo modo claro --}}
        <img
            src="{{ asset('assets/logo-light.png') }}"
            width="48"
            height="48"
            alt="Logo"
            class="block dark:hidden"
        >

        {{-- Logo modo oscuro --}}
        <img
            src="{{ asset('assets/logo-dark.png') }}"
            width="48"
            height="48"
            alt="Logo"
            class="hidden dark:block"
        >

        <span class="text-lg font-semibold">
            {{ config('app.name') }}
        </span>
    </div>
@else
    {{-- No autenticado → diseño original del login --}}
    <div class="mx-auto">
        {{-- Logo modo claro --}}
        <img
            width="48"
            height="48"
            class="mx-auto block dark:hidden"
            src="{{ asset('assets/logo-light.png') }}"
            alt="Logo"
        >

        {{-- Logo modo oscuro --}}
        <img
            width="48"
            height="48"
            class="mx-auto hidden dark:block"
            src="{{ asset('assets/logo-dark.png') }}"
            alt="Logo"
        >

        <span class="text-lg font-semibold mx-auto">
            {{ config('app.name') }}
        </span>
    </div>
@endif
