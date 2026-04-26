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
                        <div class="siga-field">
                            <span>Senales</span>
                            <div class="siga-score-scale" role="radiogroup" aria-label="Evaluacion de senales">
                                <label class="siga-score-scale__option siga-score-scale__option--rojo"><input type="radio" name="senales" value="1" required><span>1</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--rojo"><input type="radio" name="senales" value="2" required><span>2</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--amarillo"><input type="radio" name="senales" value="3" required><span>3</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--verde"><input type="radio" name="senales" value="4" required><span>4</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--verde"><input type="radio" name="senales" value="5" required><span>5</span></label>
                            </div>
                        </div>
                        <div class="siga-field">
                            <span>Frenado</span>
                            <div class="siga-score-scale" role="radiogroup" aria-label="Evaluacion de frenado">
                                <label class="siga-score-scale__option siga-score-scale__option--rojo"><input type="radio" name="frenado" value="1" required><span>1</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--rojo"><input type="radio" name="frenado" value="2" required><span>2</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--amarillo"><input type="radio" name="frenado" value="3" required><span>3</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--verde"><input type="radio" name="frenado" value="4" required><span>4</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--verde"><input type="radio" name="frenado" value="5" required><span>5</span></label>
                            </div>
                        </div>
                        <div class="siga-field">
                            <span>Seguridad</span>
                            <div class="siga-score-scale" role="radiogroup" aria-label="Evaluacion de seguridad">
                                <label class="siga-score-scale__option siga-score-scale__option--rojo"><input type="radio" name="seguridad" value="1" required><span>1</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--rojo"><input type="radio" name="seguridad" value="2" required><span>2</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--amarillo"><input type="radio" name="seguridad" value="3" required><span>3</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--verde"><input type="radio" name="seguridad" value="4" required><span>4</span></label>
                                <label class="siga-score-scale__option siga-score-scale__option--verde"><input type="radio" name="seguridad" value="5" required><span>5</span></label>
                            </div>
                            <small class="siga-score-scale__legend">1-2 rojo, 3 amarillo, 4-5 verde.</small>
                        </div>
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
