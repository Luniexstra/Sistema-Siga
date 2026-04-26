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
        $captchaQuestion = $request->session()->get('login_captcha_question');

        if (! is_string($captchaQuestion) || ! $request->session()->has('login_captcha_answer')) {
            $captcha = $this->generateCaptcha($request);
            $captchaQuestion = $captcha['question'];
        }

        return view('auth.login', [
            'captchaQuestion' => $captchaQuestion,
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
            $this->generateCaptcha($request);

            return back()
                ->withErrors([
                    'captcha' => 'La verificacion captcha no es correcta.',
                ])
                ->onlyInput('email');
        }

        $credentials = [
            'email' => $validated['email'],
            'password' => $validated['password'],
        ];

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            $this->generateCaptcha($request);

            return back()
                ->withErrors([
                    'email' => 'Las credenciales no coinciden con nuestros registros.',
                ])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $request->session()->forget('login_captcha_question');
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
        $question = "Cuanto es {$left} + {$right}?";

        $request->session()->put('login_captcha_question', $question);
        $request->session()->put('login_captcha_answer', (string) $answer);

        return [
            'question' => $question,
            'answer' => (string) $answer,
        ];
    }
}
