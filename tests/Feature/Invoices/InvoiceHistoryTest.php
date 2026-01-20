<?php

namespace Tests\Feature\Invoices;

use App\Models\User;
use App\Models\Invoice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InvoiceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanListOwnInvoices()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user, ['*']);

        Invoice::factory()->create([
            'user_id' => $user->id,
            'transaction_id' => 'inv_meu',
            'amount' => 5000,
        ]);

        Invoice::factory()->create([
            'transaction_id' => 'inv_outro',
            'amount' => 9000,
        ]);

        $response = $this->getJson(route('invoices.index'));

        $response->assertStatus(200);
        $response->assertJsonCount(1);
        $response->assertJsonFragment([
            'transaction_id' => 'inv_meu',
            'amount' => 5000,
        ]);
    }
}