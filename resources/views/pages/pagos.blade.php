@extends('layouts.siga')

@php($title = 'SIGA | Pagos')
@php($user = auth()->user())

@section('content')
    <div data-siga-page="pagos">
        <section class="siga-page-head">
            <div>
                <p class="siga-eyebrow">Modulo</p>
                <h2>Pagos y estado de cuenta</h2>
                <p class="siga-hero__copy">Controla movimientos financieros y consulta el estado de cuenta del alumno sin tener que salir a otra vista.</p>
            </div>
        </section>

        <section class="siga-grid">
            @if ($user->isAdministrador() || $user->isRecepcionista())
                <article class="siga-panel">
                    <div class="siga-panel__header">
                        <div>
                            <p class="siga-panel__eyebrow">Nuevo movimiento</p>
                            <h3>Registrar pago</h3>
                        </div>
                    </div>
                    <form class="siga-form-card" data-endpoint="/api/pagos" data-method="POST">
                        <div class="siga-form-feedback" data-form-feedback hidden></div>
                        <label class="siga-field">
                            <span>Alumno</span>
                            <select name="alumno_id" data-catalog="alumnos" required>
                                <option value="">Selecciona un alumno</option>
                            </select>
                        </label>
                        <label class="siga-field"><span>Monto</span><input name="monto" type="number" min="0.01" step="0.01" required /></label>
                        <label class="siga-field"><span>Fecha de pago</span><input name="fecha_pago" type="date" required /></label>
                        <label class="siga-field">
                            <span>Estado</span>
                            <select name="estado" required>
                                <option value="pagado">Pagado</option>
                                <option value="pendiente">Pendiente</option>
                            </select>
                        </label>
                        <button class="siga-button siga-button--primary" type="submit">Guardar pago</button>
                    </form>
                </article>
            @endif

            <article class="siga-panel {{ $user->isAdministrador() || $user->isRecepcionista() ? 'siga-panel--wide' : 'siga-panel--full' }}">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Comprobante</p>
                        <h3>{{ $user->isAlumno() ? 'Tu estado de cuenta' : 'Estado de cuenta y PDF' }}</h3>
                    </div>
                </div>
                <div class="siga-account">
                    <form class="siga-inline-form" id="estado-cuenta-form">
                        @if ($user->isAlumno())
                            <label class="siga-field"><span>Alumno ID</span><input name="alumno_id" type="number" min="1" required /></label>
                        @else
                            <label class="siga-field">
                                <span>Alumno</span>
                                <select name="alumno_id" data-catalog="alumnos" required>
                                    <option value="">Selecciona un alumno</option>
                                </select>
                            </label>
                        @endif
                        <button class="siga-button siga-button--primary" type="submit">Consultar estado</button>
                        <button class="siga-button siga-button--ghost" id="download-pdf-button" type="button">Descargar PDF</button>
                    </form>
                    <div class="siga-account__summary" id="estado-cuenta-summary">
                        <p class="siga-log__placeholder">Consulta un alumno para ver su estado de cuenta.</p>
                    </div>
                </div>
            </article>

            <article class="siga-panel siga-panel--wide siga-panel--full-mobile">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Historial</p>
                        <h3>{{ $user->isAlumno() ? 'Tus pagos registrados' : 'Pagos registrados' }}</h3>
                    </div>
                </div>
                <div class="siga-stack" id="resource-list">
                    <p class="siga-log__placeholder">Sin pagos cargados.</p>
                </div>
            </article>
        </section>
    </div>
@endsection
