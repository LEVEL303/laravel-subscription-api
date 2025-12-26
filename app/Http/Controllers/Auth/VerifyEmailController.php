<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;

class VerifyEmailController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = User::find($request->route('id'));

        if (!$user || !$request->hasValidSignature()) {
            return response()->json(['message' => 'Link inválido ou expirado.']);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'E-mail já verificado.']);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
            $user->update(['status' => 'active']);
        }

        return response()->json(['message' => 'E-mail verificado com sucesso!']);
    }
}
