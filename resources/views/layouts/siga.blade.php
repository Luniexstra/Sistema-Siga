<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? 'SIGA' }}</title>
        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
            <link rel="stylesheet" href="{{ asset('siga.css') }}">
            <script src="{{ asset('siga.js') }}" defer></script>
        @endif
    </head>
    <body class="siga-shell">
        <div
            class="siga-app"
            data-siga-app
            data-auth-user-id="{{ auth()->id() }}"
            data-auth-role="{{ auth()->user()?->role }}"
            data-auth-name="{{ auth()->user()?->name }}"
            data-auth-email="{{ auth()->user()?->email }}"
            data-auth-alumno-id="{{ auth()->user()?->alumno?->id }}"
        >
            <aside class="siga-sidebar">
                <div class="siga-brand">
                    <div class="siga-brand__mark">S</div>
                    <div>
                        <p class="siga-eyebrow">Gestion academica</p>
                        <h1>SIGA</h1>
                    </div>
                </div>

                <div class="siga-sidebar__section">
                    <p class="siga-sidebar__label">Sesion</p>
                    <div class="siga-profile">
                        <p class="siga-profile__name" id="current-user-name">{{ auth()->user()->name }}</p>
                        <p class="siga-profile__meta" id="current-user-meta">
                            {{ auth()->user()->email }} · ID {{ auth()->id() }}
                        </p>
                        <span class="siga-badge" id="current-user-role">{{ auth()->user()->role }}</span>
                        <span class="siga-chip">{{ auth()->user()->hasVerifiedEmail() ? 'Correo verificado' : 'Verificacion pendiente' }}</span>
                    </div>
                </div>

                <nav class="siga-sidebar__section siga-nav">
                    <p class="siga-sidebar__label">Modulos</p>
                    <a class="siga-nav__link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}" href="{{ route('dashboard') }}">
                        Inicio
                    </a>
                    @if (auth()->user()->isAdministrador() || auth()->user()->isRecepcionista())
                        <a class="siga-nav__link {{ request()->routeIs('pages.alumnos') ? 'is-active' : '' }}" href="{{ route('pages.alumnos') }}">
                            Alumnos
                        </a>
                    @endif
                    <a class="siga-nav__link {{ request()->routeIs('pages.clases') ? 'is-active' : '' }}" href="{{ route('pages.clases') }}">
                        Clases
                    </a>
                    <a class="siga-nav__link {{ request()->routeIs('pages.evaluaciones') ? 'is-active' : '' }}" href="{{ route('pages.evaluaciones') }}">
                        Evaluaciones
                    </a>
                    <a class="siga-nav__link {{ request()->routeIs('pages.observaciones') ? 'is-active' : '' }}" href="{{ route('pages.observaciones') }}">
                        Observaciones
                    </a>
                    @if (auth()->user()->isAdministrador() || auth()->user()->isRecepcionista() || auth()->user()->isAlumno())
                        <a class="siga-nav__link {{ request()->routeIs('pages.pagos') ? 'is-active' : '' }}" href="{{ route('pages.pagos') }}">
                            Pagos
                        </a>
                    @endif
                    @if (auth()->user()->isAdministrador())
                        <a class="siga-nav__link {{ request()->routeIs('pages.users') ? 'is-active' : '' }}" href="{{ route('pages.users') }}">
                            Usuarios
                        </a>
                    @endif
                </nav>

                <div class="siga-sidebar__section">
                    <p class="siga-sidebar__label">Cuenta</p>
                    <div class="siga-actions">
                        <button class="siga-button siga-button--ghost" id="refresh-button" type="button">
                            Actualizar
                        </button>
                        <a class="siga-button siga-button--ghost" href="{{ route('pages.security') }}">Seguridad</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="siga-button siga-button--ghost" type="submit">Cerrar sesion</button>
                        </form>
                    </div>
                </div>
            </aside>

            <main class="siga-main">
                @yield('content')
            </main>
        </div>

        <div class="siga-modal" id="edit-modal" hidden>
            <div class="siga-modal__backdrop" data-modal-close></div>
            <div class="siga-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
                <div class="siga-modal__header">
                    <div>
                        <p class="siga-eyebrow">Edicion</p>
                        <h3 id="edit-modal-title">Actualizar registro</h3>
                    </div>
                    <button class="siga-modal__close" type="button" data-modal-close>x</button>
                </div>
                <form class="siga-modal__form" id="edit-modal-form">
                    <div class="siga-modal__body" id="edit-modal-fields"></div>
                    <div class="siga-modal__footer">
                        <button class="siga-button siga-button--ghost" type="button" data-modal-close>Cancelar</button>
                        <button class="siga-button siga-button--primary" type="submit">Guardar cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </body>
</html>
