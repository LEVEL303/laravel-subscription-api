<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $invoices = $request->user()
            ->invoices()
            ->select(['id', 'transaction_id', 'amount', 'status', 'payment_method', 'paid_at', 'due_at'])
            ->latest('paid_at')
            ->get();

        return response()->json($invoices, 200);
    }
}
