<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Feature;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Feature::create(['code' => 'limited_users', 'description' => 'Limite de 5 usuários']);
        Feature::create(['code' => 'unlimited_users', 'description' => 'Usuários ilimitados']);
        Feature::create(['code' => 'api_access', 'description' => 'Acesso à API']);
        Feature::create(['code' => 'priority_support', 'description' => 'Suporte Prioritário']);
    }
}
