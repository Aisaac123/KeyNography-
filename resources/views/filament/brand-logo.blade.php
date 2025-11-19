@php
    $user = filament()->auth()->user();
@endphp

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

    @if ($user)
        <span class="text-lg font-semibold">{{ config('app.name') }}</span>
    @else
        <span class="text-lg font-semibold mx-auto">{{ config('app.name') }}</span>
    @endif
</div>
