@php
    $user = filament()->auth()->user();
@endphp

@if ($user)
    {{-- Usuario autenticado → logo + nombre en horizontal --}}
    <div class="flex items-center gap-3 group">
        {{-- Logo modo claro --}}
        <img
            src="{{ asset('assets/logo-light.png') }}"
            width="48"
            height="48"
            alt="Logo"
            class="block dark:hidden transition-all duration-500 group-hover:scale-110 group-hover:rotate-3"
        >

        {{-- Logo modo oscuro --}}
        <img
            src="{{ asset('assets/logo-dark.png') }}"
            width="48"
            height="48"
            alt="Logo"
            class="hidden dark:block transition-all duration-500 group-hover:scale-110 group-hover:rotate-3"
        >

        <span class="text-lg font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary-700 via-primary-500 to-primary-300 dark:from-primary-600 dark:via-primary-400 dark:to-primary-200 gradient-flow">
            {{ config('app.name') }}
        </span>
    </div>
@else
    {{-- No autenticado → diseño original del login --}}
    <div class="mx-auto text-center group cursor-pointer">
        {{-- Logo modo claro --}}
        <img
            width="48"
            height="48"
            class="mx-auto block dark:hidden transition-all duration-500 group-hover:scale-110 group-hover:rotate-3"
            src="{{ asset('assets/logo-light.png') }}"
            alt="Logo"
        >

        {{-- Logo modo oscuro --}}
        <img
            width="48"
            height="48"
            class="mx-auto hidden dark:block transition-all duration-500 group-hover:scale-110 group-hover:rotate-3"
            src="{{ asset('assets/logo-dark.png') }}"
            alt="Logo"
        >

        <span class="block mt-3 text-lg font-bold text-transparent bg-clip-text bg-gradient-to-r from-primary-700 via-primary-500 to-primary-300 dark:from-primary-600 dark:via-primary-400 dark:to-primary-200 gradient-flow">
            {{ config('app.name') }}
        </span>
    </div>
@endif

<style>
    .gradient-flow {
        background-size: 200% auto;
        animation: gradient-flow 3s cubic-bezier(0.6, 0, 0.25, 1) infinite;
    }

    @keyframes gradient-flow {
        0% {
            background-position: 200% center;
        }
        100% {
            background-position: 0% center;
        }
    }
</style>
