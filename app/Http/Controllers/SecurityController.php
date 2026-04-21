<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Illuminate\View\View;

class SecurityController extends Controller
{
    public function showSecurity(Request $request): View
    {
        return view('pages.security', [
            'previewVerificationUrl' => $this->buildVerificationPreviewUrl($request),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->password = Hash::make($data['password']);
        $user->setRememberToken(Str::random(60));
        $user->save();

        return back()->with('security_status', 'Tu contrasena se actualizo correctamente.');
    }

    public function showForgotPassword(): View
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = \App\Models\User::where('email', $data['email'])->first();
        $previewUrl = null;

        if ($user) {
            $token = Password::broker()->createToken($user);
            $previewUrl = route('password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);

            try {
                $user->sendPasswordResetNotification($token);
            } catch (\Throwable $exception) {
                // Si no hay servicio de correo disponible, el enlace sigue visible en la interfaz.
            }
        }

        return back()
            ->with('status', 'Si el correo existe en SIGA, se genero un enlace para restablecer la contrasena.')
            ->with('password_reset_preview_url', $previewUrl);
    }

    public function showResetPassword(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset(
            $data,
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()
                ->withErrors(['email' => __($status)])
                ->withInput($request->only('email'));
        }

        return redirect()->route('login')->with('status', 'La contrasena fue restablecida correctamente. Ya puedes iniciar sesion.');
    }

    public function sendVerificationEmail(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return back()->with('security_status', 'Tu correo ya esta verificado.');
        }

        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            // Si no hay servicio de correo disponible, el enlace sigue visible en la interfaz.
        }

        return back()
            ->with('security_status', 'Se genero un enlace de verificacion para tu cuenta.')
            ->with('verification_preview_url', $this->buildVerificationPreviewUrl($request));
    }

    public function verifyEmail(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return redirect()->route('pages.security')->with('security_status', 'Tu correo ha sido verificado correctamente.');
    }

    protected function buildVerificationPreviewUrl(Request $request): ?string
    {
        $user = $request->user();

        if (! $user || $user->hasVerifiedEmail()) {
            return null;
        }

        return URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );
    }
}
