<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureAccess
{
    public function handle(Request $request, Closure $next, string $featureCode): Response
    {
        $user = $request->user();

        $subscription = $user->subscriptions()
            ->where('status', 'active')
            ->where('ends_at', '>=', now())
            ->with('plan.features')
            ->first();

        if (!$subscription) {
            return response()->json(['message', 'Você não possui uma assinatura ativa.'], 403);
        }

        $hasFeature = $subscription->plan->features->contains('code', $featureCode);

        if (!$hasFeature) {
            return response()->json(['message' => 'Seu plano atual não permite acessar este recurso.'], 403);
        }

        return $next($request);
    }
}
