<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(Request $request): View
    {
        $captcha = $this->generateCaptcha($request);

        return view('auth.login', [
            'captchaQuestion' => $captcha['question'],
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'captcha' => ['required'],
        ]);

        if ((string) $request->session()->get('login_captcha_answer') !== trim((string) $validated['captcha'])) {
            $captcha = $this->generateCaptcha($request);

            return back()
                ->withErrors([
                    'captcha' => 'La verificación captcha no es correcta.',
                ])
                ->with('captcha_question', $captcha['question'])
                ->onlyInput('email');
        }

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $captcha = $this->generateCaptcha($request);

            return back()
                ->withErrors([
                    'email' => 'Las credenciales no coinciden con nuestros registros.',
                ])
                ->with('captcha_question', $captcha['question'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->forget('login_captcha_answer');

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    protected function generateCaptcha(Request $request): array
    {
        $left = random_int(2, 9);
        $right = random_int(1, 9);
        $answer = $left + $right;

        $request->session()->put('login_captcha_answer', (string) $answer);

        return [
            'question' => "¿Cuánto es {$left} + {$right}?",
            'answer' => (string) $answer,
        ];
    }
}
