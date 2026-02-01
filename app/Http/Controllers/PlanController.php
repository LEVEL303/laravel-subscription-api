<?php

namespace App\Http\Controllers;

use App\Models\Plan;

class PlanController extends Controller
{
    public function index()
    {  
        $plans = Plan::with('features')
            ->where('status', 'active')
            ->select('id', 'name', 'slug', 'description', 'price', 'trial_days', 'period')
            ->get();

        return response()->json($plans, 200);
    }

    public function show(Plan $plan)
    {
        if ($plan->status === "active") {
            $plan->load('features');
            
            return response()->json(
                $plan->only(['id', 'name', 'slug', 'description', 'price', 'trial_days', 'period']),
                200
            );
        }

        abort(404);
    }
}
