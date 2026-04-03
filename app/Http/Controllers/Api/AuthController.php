<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        if (empty($user)) {
            return response()->json([
                'user' => null,
                'message' => 'Invalid registration'
            ], 401);
        }

        Auth::login($user);

        return response()->json([
            'user' => $user,
            'message' => 'Registered successfully'
        ]);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'user' => null,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $request->session()->regenerate();

        return response()->json([
            'user' => Auth::user(),
            'message' => 'Logged in'
        ]);
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'user' => null,
            'message' => 'Logged out'
        ]);
    }

    // Current user endpoint
    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user(),
            'message' => 'Current user data',
        ]);
    }

    public function login_return_token(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        $token = $user->createToken('API Token')->plainTextToken;

        return response()->json(['token' => $token], 200);
    }
}