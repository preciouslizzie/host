<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yabacon\Paystack;
use App\Models\Transaction;
use App\Models\Event;

class PaymentController extends Controller
{
    protected $paystack;

    public function __construct()
    {
        // ✅ Use Laravel config helper (never env() directly)
        $this->paystack = new Paystack(config('paystack.secretKey'));
    }

    /**
     * Initialize payment for a specific event
     */
    public function initialize(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'amount' => 'required|numeric|min:1',
            'event_id' => 'required|exists:events,id',
        ]);

        $amount = $request->amount * 100; // convert to kobo
        $reference = 'EVT-' . uniqid();

        // Store transaction before redirect
        Transaction::create([
            'event_id' => $request->event_id,
            'email' => $request->email,
            'amount' => $request->amount,
            'reference' => $reference,
            'status' => 'pending',
        ]);

        try {
            $tranx = $this->paystack->transaction->initialize([
                'amount' => $amount,
                'email' => $request->email,
                'reference' => $reference,
                'callback_url' => route('payment.callback'),
            ]);

            return response()->json([
                'authorization_url' => $tranx->data->authorization_url,
                'reference' => $tranx->data->reference,
            ]);
        } catch (\Yabacon\Paystack\Exception\ApiException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle Paystack callback
     */
    public function callback(Request $request)
    {
        $reference = $request->query('reference');

        if (!$reference) {
            return response()->json(['error' => 'No transaction reference supplied'], 400);
        }

        try {
            $tranx = $this->paystack->transaction->verify(['reference' => $reference]);

            if ($tranx->data->status === 'success') {
                // Update transaction as successful
                Transaction::where('reference', $reference)->update(['status' => 'success']);

                return response()->json([
                    'message' => 'Payment successful',
                    'data' => $tranx->data,
                ]);
            } else {
                Transaction::where('reference', $reference)->update(['status' => 'failed']);

                return response()->json([
                    'message' => 'Payment failed',
                    'data' => $tranx->data,
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
