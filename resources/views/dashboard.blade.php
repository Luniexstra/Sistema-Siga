@extends('layouts.siga')

@php($title = 'SIGA | Inicio')
@php($user = auth()->user())

@section('content')
    <div data-siga-page="home">
        <section class="siga-hero">
            <div>
                <p class="siga-eyebrow">Sistema Integral de Gestion Academica</p>
                <h2>Operacion central de SIGA</h2>
                <p class="siga-hero__copy">
                    Supervisa la operacion academica, administrativa y financiera desde un solo entorno de trabajo.
                </p>
            </div>

            <div class="siga-hero__rail">
                <div class="siga-glass-card">
                    <p class="siga-glass-card__label">Cuenta activa</p>
                    <p class="siga-glass-card__value">{{ ucfirst($user->role) }}</p>
                    <p class="siga-glass-card__hint">Tu vista y tus acciones se ajustan automaticamente a tu perfil.</p>
                </div>
                <div class="siga-glass-card siga-glass-card--accent">
                    <p class="siga-glass-card__label">Cobertura</p>
                    <p class="siga-glass-card__value">Control integral</p>
                    <p class="siga-glass-card__hint">Alumnos, clases, evaluaciones, observaciones, pagos y seguridad.</p>
                </div>
            </div>
        </section>

        <section class="siga-stats">
            <article class="siga-stat-card">
                <span class="siga-stat-card__label">Alumnos</span>
                <strong id="stat-alumnos">0</strong>
                <small>Expedientes registrados</small>
            </article>
            <article class="siga-stat-card">
                <span class="siga-stat-card__label">Clases</span>
                <strong id="stat-clases">0</strong>
                <small>Agenda activa</small>
            </article>
            <article class="siga-stat-card">
                <span class="siga-stat-card__label">Evaluaciones</span>
                <strong id="stat-evaluaciones">0</strong>
                <small>Registros academicos</small>
            </article>
            <article class="siga-stat-card">
                <span class="siga-stat-card__label">Pagos</span>
                <strong id="stat-pagos">0</strong>
                <small>Movimientos registrados</small>
            </article>
            <article class="siga-stat-card">
                <span class="siga-stat-card__label">Riesgo rojo</span>
                <strong id="stat-riesgo-rojo">0</strong>
                <small>Evaluaciones con alerta critica</small>
            </article>
            <article class="siga-stat-card">
                <span class="siga-stat-card__label">Pendientes</span>
                <strong id="stat-pagos-pendientes">0</strong>
                <small>Pagos por confirmar</small>
            </article>
        </section>

        <section class="siga-grid">
            <article class="siga-panel siga-panel--wide">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Modulos</p>
                        <h3>Accesos principales</h3>
                    </div>
                </div>

                <div class="siga-cards-grid">
                    @if ($user->isAdministrador() || $user->isRecepcionista())
                        <a class="siga-module-card" href="{{ route('pages.alumnos') }}">
                            <strong>Alumnos</strong>
                            <p>Consulta y administracion de expedientes.</p>
                        </a>
                    @endif
                    <a class="siga-module-card" href="{{ route('pages.clases') }}">
                        <strong>Clases</strong>
                        <p>Agenda operativa y seguimiento de sesiones.</p>
                    </a>
                    <a class="siga-module-card" href="{{ route('pages.evaluaciones') }}">
                        <strong>Evaluaciones</strong>
                        <p>Desempeno tecnico y semaforizacion.</p>
                    </a>
                    <a class="siga-module-card" href="{{ route('pages.observaciones') }}">
                        <strong>Observaciones</strong>
                        <p>Seguimiento academico por clase.</p>
                    </a>
                    @if ($user->isAdministrador() || $user->isRecepcionista() || $user->isAlumno())
                        <a class="siga-module-card" href="{{ route('pages.pagos') }}">
                            <strong>Pagos</strong>
                            <p>Estado de cuenta, movimientos y comprobantes.</p>
                        </a>
                    @endif
                    @if ($user->isAdministrador())
                        <a class="siga-module-card" href="{{ route('pages.users') }}">
                            <strong>Usuarios</strong>
                            <p>Administracion de accesos y roles.</p>
                        </a>
                    @endif
                </div>
            </article>

            <article class="siga-panel">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Actividad</p>
                        <h3>Bitacora reciente</h3>
                    </div>
                </div>
                <div class="siga-log" id="activity-log">
                    <p class="siga-log__placeholder">La actividad reciente aparecera en este espacio.</p>
                </div>
            </article>

            <article class="siga-panel">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Semaforo</p>
                        <h3>Distribucion de evaluaciones</h3>
                    </div>
                </div>
                <div class="siga-report-list" id="home-semaforo">
                    <p class="siga-log__placeholder">Cargando informacion.</p>
                </div>
            </article>

            <article class="siga-panel siga-panel--wide">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Agenda</p>
                        <h3>Proximas clases</h3>
                    </div>
                </div>
                <div class="siga-report-table" id="home-agenda">
                    <p class="siga-log__placeholder">Cargando agenda.</p>
                </div>
            </article>

            <article class="siga-panel siga-panel--full">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Cobranza</p>
                        <h3>Resumen de cartera</h3>
                    </div>
                </div>
                <div class="siga-report-grid" id="home-cartera">
                    <p class="siga-log__placeholder">Cargando informacion financiera.</p>
                </div>
            </article>
        </section>
    </div>
@endsection
