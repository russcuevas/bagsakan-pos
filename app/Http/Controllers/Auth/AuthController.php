<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->isPurchasing()) {
                return redirect()->route('staff.dashboard');
            } elseif ($user->isCashier()) {
                return redirect()->route('cashier.pos');
            }
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email_or_username' => 'required|string',
            'password' => 'required|string',
        ]);

        $input = $request->input('email_or_username');
        $fieldType = filter_var($input, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        $attempt = Auth::attempt([
            $fieldType => $input,
            'password' => $request->password,
        ]);

        if ($attempt) {
            $request->session()->regenerate();
            $user = Auth::user();

            if ($user->status !== 'active') {
                Auth::logout();
                return back()->withErrors([
                    'email_or_username' => 'Your account is currently inactive. Contact your supervisor.',
                ])->onlyInput('email_or_username');
            }

            if ($request->ajax()) {
                $redirectUrl = route('admin.dashboard');
                if ($user->isPurchasing()) {
                    $redirectUrl = route('staff.dashboard');
                } elseif ($user->isCashier()) {
                    $redirectUrl = route('cashier.pos');
                }

                return response()->json([
                    'success' => true,
                    'message' => "Welcome back, {$user->name}!",
                    'redirect' => $redirectUrl,
                ]);
            }

            if ($user->isAdmin()) {
                return redirect()->intended(route('admin.dashboard'))->with('success', "Welcome back, {$user->name}! Logged in successfully.");
            } elseif ($user->isPurchasing()) {
                return redirect()->intended(route('staff.dashboard'))->with('success', "Welcome back, {$user->name}! Logged in successfully.");
            } elseif ($user->isCashier()) {
                return redirect()->intended(route('cashier.pos'))->with('success', "Welcome back, {$user->name}! POS Terminal ready.");
            }

            return redirect('/')->with('success', "Welcome back, {$user->name}!");
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => 'The provided credentials do not match our records.',
                'errors' => [
                    'email_or_username' => ['The provided credentials do not match our records.']
                ]
            ], 422);
        }

        return back()->withErrors([
            'email_or_username' => 'The provided credentials do not match our records.',
        ])->onlyInput('email_or_username');
    }

    public function showForgotPassword()
    {
        if (Auth::check()) {
            return redirect('/');
        }
        return view('auth.forgot-password');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $user = User::where('email', $request->email)->first();
        if ($user && $user->status !== 'active') {
            return back()->withErrors([
                'email' => 'This account is currently deactivated. Please contact your system administrator.'
            ]);
        }

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, $token = null)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->email,
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));

                AuditLog::log('password_reset', User::class, $user->id, null, ['email' => $user->email], 'User reset their password via email link');
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Your password has been reset successfully! You can now log in with your new password.');
        }

        return back()->withErrors(['email' => __($status)]);
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:50|unique:users,username,' . $user->id,
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:50',
            'current_password' => 'nullable|required_with:password|string',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if (!empty($validated['password'])) {
            if (!empty($validated['current_password']) && !\Illuminate\Support\Facades\Hash::check($validated['current_password'], $user->password)) {
                if ($request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The current password provided is incorrect.',
                        'errors' => ['current_password' => ['The current password provided is incorrect.']],
                    ], 422);
                }
                return back()->withErrors(['current_password' => 'The current password provided is incorrect.']);
            }
            $user->password = \Illuminate\Support\Facades\Hash::make($validated['password']);
        }

        $old = ['name' => $user->name, 'username' => $user->username, 'email' => $user->email];

        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->email = $validated['email'];
        $user->phone = $validated['phone'] ?? null;
        $user->save();

        \App\Models\AuditLog::log('profile_updated', \App\Models\User::class, $user->id, $old, ['name' => $user->name, 'username' => $user->username, 'email' => $user->email], 'User updated their personal profile');

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Your profile has been updated successfully!',
            ]);
        }

        return back()->with('success', 'Profile updated successfully!');
    }

    public function logout(Request $request)
    {
        $name = Auth::user() ? Auth::user()->name : null;
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $msg = $name ? "You have been signed out successfully. Goodbye, {$name}!" : "You have been signed out successfully.";
        return redirect()->route('login')->with('success', $msg);
    }
}
