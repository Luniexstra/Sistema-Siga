<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Restablecer contrasena | SIGA</title>
        <link rel="stylesheet" href="{{ asset('siga.css') }}">
    </head>
    <body class="siga-shell siga-auth-shell">
        <div class="siga-auth">
            <section class="siga-auth__hero">
                <p class="siga-eyebrow">Seguridad</p>
                <h1>Define una nueva contrasena</h1>
                <p>
                    Elige una contrasena segura para volver a entrar a SIGA.
                </p>
            </section>

            <section class="siga-auth__card">
                <div class="siga-auth__header">
                    <p class="siga-eyebrow">Reinicio</p>
                    <h2>Actualizar acceso</h2>
                </div>

                <form method="POST" action="{{ route('password.update') }}" class="siga-form-card siga-form-card--flat">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token }}">

                    <label class="siga-field">
                        <span>Correo</span>
                        <input type="email" name="email" value="{{ old('email', $email) }}" required autofocus>
                    </label>

                    <label class="siga-field">
                        <span>Nueva contrasena</span>
                        <input type="password" name="password" required>
                    </label>

                    <label class="siga-field">
                        <span>Confirmar contrasena</span>
                        <input type="password" name="password_confirmation" required>
                    </label>

                    @if ($errors->any())
                        <div class="siga-alert siga-alert--error">
                            {{ $errors->first() }}
                        </div>
                    @endif

                    <button class="siga-button siga-button--primary" type="submit">Guardar contrasena</button>
                    <a class="siga-button siga-button--ghost" href="{{ route('login') }}">Volver al login</a>
                </form>
            </section>
        </div>
    </body>
</html>
