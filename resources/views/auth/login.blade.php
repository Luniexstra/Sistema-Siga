<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Acceso | SIGA</title>
        <link rel="stylesheet" href="{{ asset('siga.css') }}">
    </head>
    <body class="siga-shell siga-auth-shell">
        <div class="siga-auth">
            <section class="siga-auth__hero">
                <p class="siga-eyebrow">Sistema Integral de Gestion Academica</p>
                <h1>Acceso seguro a SIGA</h1>
                <p>
                    Administra alumnos, clases, evaluaciones, observaciones y pagos desde una sola plataforma.
                </p>
            </section>

            <section class="siga-auth__card">
                <div class="siga-auth__header">
                    <p class="siga-eyebrow">Acceso</p>
                    <h2>Bienvenido</h2>
                    <p>Ingresa con tu cuenta institucional para continuar.</p>
                </div>

                <form method="POST" action="{{ route('login.attempt') }}" class="siga-form-card siga-form-card--flat">
                    @csrf

                    <label class="siga-field">
                        <span>Correo</span>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                    </label>

                    <label class="siga-field">
                        <span>Contrasena</span>
                        <div class="siga-password-wrap">
                            <input id="login-password" type="password" name="password" required>
                            <button class="siga-password-toggle" type="button" id="toggle-password">Mostrar</button>
                        </div>
                    </label>

                    <label class="siga-field">
                        <span>Verificacion</span>
                        <div class="siga-captcha">
                            <div class="siga-captcha__question">
                                {{ session('captcha_question', $captchaQuestion) }}
                            </div>
                            <input type="text" name="captcha" inputmode="numeric" autocomplete="off" placeholder="Respuesta" required>
                        </div>
                    </label>

                    <label class="siga-check">
                        <input type="checkbox" name="remember" value="1">
                        <span>Mantener sesion activa</span>
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

                    <button class="siga-button siga-button--primary" type="submit">Iniciar sesion</button>
                    <a class="siga-button siga-button--ghost" href="{{ route('password.request') }}">Recuperar acceso</a>
                </form>
            </section>
        </div>

        <script>
            const togglePasswordButton = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('login-password');

            if (togglePasswordButton && passwordInput) {
                togglePasswordButton.addEventListener('click', () => {
                    const nextType = passwordInput.type === 'password' ? 'text' : 'password';
                    passwordInput.type = nextType;
                    togglePasswordButton.textContent = nextType === 'password' ? 'Mostrar' : 'Ocultar';
                });
            }
        </script>
    </body>
</html>
