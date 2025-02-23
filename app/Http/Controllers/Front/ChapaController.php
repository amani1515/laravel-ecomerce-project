<?php

namespace App\Http\Controllers\Front;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Http;

class ChapaController extends Controller
{
    public function chapaPayment(Request $request)
    {
        $amount = $request->input('amount');

        $chapa_url = 'https://api.chapa.co/v1/transaction/initialize';
        $callback_url = route('chapa.callback');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer YOUR_CHAPA_SECRET_KEY',
            'Content-Type' => 'application/json',
        ])->post($chapa_url, [
            'amount' => $amount,
            'currency' => 'ETB',
            'email' => auth()->user()->email ?? 'guest@example.com',
            'first_name' => auth()->user()->name ?? 'Guest',
            'tx_ref' => 'txn_' . uniqid(),
            'callback_url' => $callback_url,
            'return_url' => route('chapa.success'),
            'customization' => [
                'title' => 'Habesha Tasks Payment',
                'description' => 'Payment for your order',
            ],
        ]);

        $responseBody = $response->json();

        if (isset($responseBody['status']) && $responseBody['status'] === 'success') {
            return redirect($responseBody['data']['checkout_url']);
        }

        return back()->with('error', 'Failed to initialize payment. Please try again.');
    }

    public function chapaCallback(Request $request)
    {
        return redirect()->route('chapa.success')->with('success', 'Payment successful!');
    }

    public function chapaSuccess()
    {
        return view('front.success')->with('message', 'Your payment was successful.');
    }
}
