@extends('layouts.siga')

@php($title = 'SIGA | Seguridad')
@php($user = auth()->user())

@section('content')
    <div data-siga-page="security">
        <section class="siga-page-head">
            <div>
                <p class="siga-eyebrow">Cuenta</p>
                <h2>Seguridad de la cuenta</h2>
                <p class="siga-hero__copy">Gestiona tu correo de acceso y actualiza tu contrasena desde esta seccion.</p>
            </div>
        </section>

        <section class="siga-grid">
            <article class="siga-panel">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Correo</p>
                        <h3>Estado de verificacion</h3>
                    </div>
                </div>

                <div class="siga-stack">
                    <div class="siga-account__card">
                        <strong>{{ $user->email }}</strong>
                        <p>Estado: {!! $user->hasVerifiedEmail() ? '<span class="siga-status-pill siga-status-pill--verde">Verificado</span>' : '<span class="siga-status-pill siga-status-pill--amarillo">Pendiente</span>' !!}</p>
                    </div>

                    @if (session('security_status'))
                        <div class="siga-form-feedback is-success">
                            {{ session('security_status') }}
                        </div>
                    @endif

                    @if (! $user->hasVerifiedEmail())
                        <form method="POST" action="{{ route('verification.send') }}" class="siga-form-card siga-form-card--flat">
                            @csrf
                            <button class="siga-button siga-button--primary" type="submit">Enviar verificacion</button>
                        </form>

                        @if (session('verification_preview_url') || $previewVerificationUrl)
                            <div class="siga-account__card">
                                <strong>Enlace de verificacion</strong>
                                <p>
                                    <a href="{{ session('verification_preview_url', $previewVerificationUrl) }}">
                                        {{ session('verification_preview_url', $previewVerificationUrl) }}
                                    </a>
                                </p>
                            </div>
                        @endif
                    @endif
                </div>
            </article>

            <article class="siga-panel siga-panel--wide">
                <div class="siga-panel__header">
                    <div>
                        <p class="siga-panel__eyebrow">Contrasena</p>
                        <h3>Actualizar contrasena</h3>
                    </div>
                </div>

                <form method="POST" action="{{ route('security.password.update') }}" class="siga-form-card">
                    @csrf
                    @method('PUT')

                    <label class="siga-field">
                        <span>Contrasena actual</span>
                        <input type="password" name="current_password" required>
                    </label>

                    <label class="siga-field">
                        <span>Nueva contrasena</span>
                        <input type="password" name="password" required>
                    </label>

                    <label class="siga-field">
                        <span>Confirmar nueva contrasena</span>
                        <input type="password" name="password_confirmation" required>
                    </label>

                    @if ($errors->any())
                        <div class="siga-form-feedback is-error">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <button class="siga-button siga-button--primary" type="submit">Guardar cambios</button>
                </form>
            </article>
        </section>
    </div>
@endsection
