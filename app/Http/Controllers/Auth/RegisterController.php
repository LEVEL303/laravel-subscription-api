<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Auth\Events\Registered;

class RegisterController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'max:191', 'email', 'unique:users'],
            'password' => [
                'required', 
                'confirmed', 
                'string', 
                'min:8', 
                'max:30', 
                'regex:/[a-zA-Z]/',
                'regex:/[0-9]/',
            ]
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'user',
            'status' => 'inactive',
        ]);

        event(new Registered($user));

        return response()->json([
            'message' => 'Usuário registrado com sucesso. Por favor verifique o seu e-mail.',
            'data' => $user,
        ], 201);
    }
}
