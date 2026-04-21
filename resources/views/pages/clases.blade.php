@extends('layouts.siga')

@php($title = 'SIGA | Clases')
@php($user = auth()->user())

@section('content')
    <div data-siga-page="resource" data-siga-resource="clases">
        <section class="siga-page-head">
            <div>
                <p class="siga-eyebrow">Modulo</p>
                <h2>Gestion de clases</h2>
                <p class="siga-hero__copy">Agenda sesiones practicas y teoricas sin choques de horario entre alumno e instructor.</p>
            </div>
        </section>

        <section class="siga-grid">
            @if ($user->isAdministrador() || $user->isRecepcionista())
                <article class="siga-panel">
                    <div class="siga-panel__header">
                        <div>
                            <p class="siga-panel__eyebrow">Nueva clase</p>
                            <h3>Programar agenda</h3>
                        </div>
                    </div>
                    <form class="siga-form-card" data-endpoint="/api/clases" data-method="POST">
                        <div class="siga-form-feedback" data-form-feedback hidden></div>
                        <label class="siga-field"><span>Fecha</span><input name="fecha" type="date" required /></label>
                        <label class="siga-field"><span>Hora</span><input name="hora" type="time" required /></label>
                        <label class="siga-field">
                            <span>Alumno</span>
                            <select name="alumno_id" data-catalog="alumnos" required>
                                <option value="">Selecciona un alumno</option>
                            </select>
                        </label>
                        <label class="siga-field">
                            <span>Instructor</span>
                            <select name="instructor_id" data-catalog="instructores" required>
                                <option value="">Selecciona un instructor</option>
                            </select>
                        </label>
                        <button class="siga-button siga-button--primary" type="submit">Guardar clase</button>
                    </form>
                </article>
            @endif

            <article class="siga-panel {{ $user->isAdministrador() || $user->isRecepcionista() ? 'siga-panel--wide' : 'siga-panel--full' }}">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Agenda</p>
                        <h3>{{ $user->isInstructor() ? 'Tus clases asignadas' : ($user->isAlumno() ? 'Tus clases programadas' : 'Clases registradas') }}</h3>
                    </div>
                </div>
                <div class="siga-table-wrap">
                    <table class="siga-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Fecha</th>
                                <th>Alumno</th>
                                <th>Instructor</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="resource-table">
                            <tr><td colspan="5">Sin datos cargados.</td></tr>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>
    </div>
@endsection
