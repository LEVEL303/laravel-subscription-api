<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PlanController extends Controller
{
    public function index()
    {  
        $user = Auth::guard('sanctum')->user();

        if ($user && $user->role === 'admin') {
            return response()->json(Plan::all(), 200);
        }

        $plans = Plan::where('status', 'active')
            ->select('id', 'name', 'slug', 'description', 'price', 'period')
            ->get();

        return response()->json($plans, 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191', 'unique:plans,name'],
            'description' => ['required', 'string'],
            'price' => ['required', 'integer'],
            'period' => ['required', 'string', 'in:monthly,yearly'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Plan::create($validated);

        return response()->json([
            'message' => 'Plano cadastrado com sucesso!'
        ], 201);
    }

    public function update(Request $request, Plan $plan)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:191', 'unique:plans,name,' . $plan->id],
            'description' => ['required', 'string'],
            'price' => ['required', 'integer'],
            'period' => ['required', 'string', 'in:monthly,yearly'],
            'status' => ['required', 'string', 'in:active,inactive'],
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $plan->update($validated);

        return response()->json([
            'message' => 'Plano atualizado com sucesso!'
        ], 200);
    }

    public function destroy(Plan $plan)
    {
        if ($plan->subscriptions()->exists()) {
            $plan->update(['status' => 'inactive']);

            return response()->json([
                'message' => 'Plano inativado pois possui assinaturas vinculadas.'
            ], 200);
        }

        $plan->delete();

        return response()->json([
            'message' => 'Plano excluído com sucesso!'
        ], 200);
    }

    public function show(Plan $plan)
    {
        $user = Auth::guard('sanctum')->user();

        if ($user && $user->role === 'admin') {
            return response()->json($plan, 200);
        }

        if ($plan->status === "active") {
            return response()->json(
                $plan->only(['id', 'name', 'slug', 'description', 'price', 'period']),
                200
            );
        }

        abort(404);
    }
}
