@extends('layouts.siga')

@php($title = 'SIGA | Evaluaciones')
@php($user = auth()->user())

@section('content')
    <div data-siga-page="resource" data-siga-resource="evaluaciones">
        <section class="siga-page-head">
            <div>
                <p class="siga-eyebrow">Modulo</p>
                <h2>Evaluaciones tecnicas</h2>
                <p class="siga-hero__copy">Registra metricas del 1 al 5 y deja que SIGA calcule promedio y semaforizacion automaticamente.</p>
            </div>
        </section>

        <section class="siga-grid">
            @if ($user->isInstructor())
                <article class="siga-panel">
                    <div class="siga-panel__header">
                        <div>
                            <p class="siga-panel__eyebrow">Nueva evaluacion</p>
                            <h3>Captura de instructor</h3>
                        </div>
                    </div>
                    <form class="siga-form-card" data-endpoint="/api/evaluaciones" data-method="POST">
                        <div class="siga-form-feedback" data-form-feedback hidden></div>
                        <label class="siga-field">
                            <span>Clase</span>
                            <select name="clase_id" data-catalog="clases" required>
                                <option value="">Selecciona una clase</option>
                            </select>
                        </label>
                        <label class="siga-field"><span>Senales</span><input name="senales" type="number" min="1" max="5" required /></label>
                        <label class="siga-field"><span>Frenado</span><input name="frenado" type="number" min="1" max="5" required /></label>
                        <label class="siga-field"><span>Seguridad</span><input name="seguridad" type="number" min="1" max="5" required /></label>
                        <button class="siga-button siga-button--primary" type="submit">Guardar evaluacion</button>
                    </form>
                </article>
            @endif

            <article class="siga-panel {{ $user->isInstructor() ? 'siga-panel--wide' : 'siga-panel--full' }}">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Historial</p>
                        <h3>{{ $user->isAlumno() ? 'Tus evaluaciones' : ($user->isInstructor() ? 'Evaluaciones de tus clases' : 'Evaluaciones registradas') }}</h3>
                    </div>
                </div>
                <div class="siga-stack" id="resource-list">
                    <p class="siga-log__placeholder">Sin evaluaciones cargadas.</p>
                </div>
            </article>
        </section>
    </div>
@endsection
