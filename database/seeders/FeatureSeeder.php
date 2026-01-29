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
        Feature::create(['name' => 'Limited users', 'code' => 'limited-users', 'description' => 'Limite de 5 usuários']);
        Feature::create(['name' => 'Unlimited users', 'code' => 'unlimited-users', 'description' => 'Usuários ilimitados']);
        Feature::create(['name' => 'Api access', 'code' => 'api-access', 'description' => 'Acesso à API']);
        Feature::create(['name' => 'Priority support', 'code' => 'priority-support', 'description' => 'Suporte Prioritário']);
    }
}
