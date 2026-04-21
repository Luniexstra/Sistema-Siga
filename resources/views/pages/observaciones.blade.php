@extends('layouts.siga')

@php($title = 'SIGA | Observaciones')
@php($user = auth()->user())

@section('content')
    <div data-siga-page="resource" data-siga-resource="observaciones">
        <section class="siga-page-head">
            <div>
                <p class="siga-eyebrow">Modulo</p>
                <h2>Observaciones privadas</h2>
                <p class="siga-hero__copy">Lleva seguimiento por clase sin mezclar comentarios con pagos o evaluaciones.</p>
            </div>
        </section>

        <section class="siga-grid">
            @if ($user->isInstructor())
                <article class="siga-panel">
                    <div class="siga-panel__header">
                        <div>
                            <p class="siga-panel__eyebrow">Nueva observacion</p>
                            <h3>Seguimiento por clase</h3>
                        </div>
                    </div>
                    <form class="siga-form-card" data-endpoint="/api/observaciones" data-method="POST">
                        <div class="siga-form-feedback" data-form-feedback hidden></div>
                        <label class="siga-field">
                            <span>Clase</span>
                            <select name="clase_id" data-catalog="clases" required>
                                <option value="">Selecciona una clase</option>
                            </select>
                        </label>
                        <label class="siga-field"><span>Comentario</span><textarea name="comentario" rows="7" required></textarea></label>
                        <button class="siga-button siga-button--primary" type="submit">Guardar observacion</button>
                    </form>
                </article>
            @endif

            <article class="siga-panel {{ $user->isInstructor() ? 'siga-panel--wide' : 'siga-panel--full' }}">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Historial</p>
                        <h3>{{ $user->isAlumno() ? 'Observaciones de tus clases' : ($user->isInstructor() ? 'Observaciones de tus clases' : 'Observaciones registradas') }}</h3>
                    </div>
                </div>
                <div class="siga-stack" id="resource-list">
                    <p class="siga-log__placeholder">Sin observaciones cargadas.</p>
                </div>
            </article>
        </section>
    </div>
@endsection
