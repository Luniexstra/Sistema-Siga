<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Recuperar acceso | SIGA</title>
        <link rel="stylesheet" href="{{ asset('siga.css') }}">
    </head>
    <body class="siga-shell siga-auth-shell">
        <div class="siga-auth">
            <section class="siga-auth__hero">
                <p class="siga-eyebrow">Recuperacion de acceso</p>
                <h1>Restablece tu contrasena</h1>
                <p>
                    Ingresa tu correo y continua con el proceso de recuperacion para volver a acceder a tu cuenta.
                </p>
            </section>

            <section class="siga-auth__card">
                <div class="siga-auth__header">
                    <p class="siga-eyebrow">Seguridad</p>
                    <h2>Recuperar acceso</h2>
                    <p>Usa el correo asociado a tu cuenta.</p>
                </div>

                <form method="POST" action="{{ route('password.email') }}" class="siga-form-card siga-form-card--flat">
                    @csrf

                    <label class="siga-field">
                        <span>Correo</span>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                    </label>

                    @if ($errors->any())
                        <div class="siga-alert siga-alert--error">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="siga-alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('password_reset_preview_url'))
                        <div class="siga-alert">
                            Enlace de recuperacion:
                            <a href="{{ session('password_reset_preview_url') }}">{{ session('password_reset_preview_url') }}</a>
                        </div>
                    @endif

                    <button class="siga-button siga-button--primary" type="submit">Continuar</button>
                    <a class="siga-button siga-button--ghost" href="{{ route('login') }}">Volver</a>
                </form>
            </section>
        </div>
    </body>
</html>
