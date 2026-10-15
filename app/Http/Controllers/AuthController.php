<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6|confirmed',
            'role' => 'required|in:team_manager,player',
        ]);

        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        return redirect('/login')->with(
            'success',
            'Account created successfully! You can now log in.'
        );
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (auth()->attempt($credentials)) {
            $request->session()->regenerate();

            $user = auth()->user();

            $welcome = 'Welcome back, ' . $user->name . '!';

            switch ($user->role) {
                case 'admin':
                    return redirect()->route('admin.dashboard')->with('success', $welcome);

                case 'team_manager':
                    return redirect()->route('tournaments.index')->with('success', $welcome);

                case 'player':
                    return redirect()->route('player.teams.index')->with('success', $welcome);

                default:
                    auth()->logout();

                    $request->session()->invalidate();
                    $request->session()->regenerateToken();

                    return redirect('/login')->withErrors([
                        'email' => 'Your account does not have a valid role.',
                    ]);
            }
        }

        return back()->withErrors([
            'email' => 'The email or password is incorrect.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')->with(
            'success',
            'You have been logged out successfully.'
        );
    }
}