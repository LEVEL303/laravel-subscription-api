<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Feature;
use Illuminate\Http\Request;

class PlanFeatureController extends Controller
{
    public function index()
    {
        $features = Feature::all();
        return response()->json($features, 200);
    }

    public function store(Request $request, Plan $plan)
    {
        $request->validate(['feature_id' => ['required', 'exists:features,id']]);
        
        $plan->features()->syncWithoutDetaching([$request->feature_id]);

        return response()->json([
            'message' => 'Funcionalidade vinculada com sucesso!'
        ], 201);
    }

    public function destroy(Plan $plan, Feature $feature)
    {
        $plan->features()->detach($feature->id);

        return response()->json([
            'message' => 'Funcionalidade desvinculada com sucesso!'
        ], 200);
    }
}
