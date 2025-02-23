<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ChapaPaymentController extends Controller
{
    public function processPayment(Request $request)
    {
        // Validate the request
        $request->validate([
            'address_id' => 'required',
            'payment_gateway' => 'required',
        ]);
        if ($request->isMethod('get')) {
            return response()->json(['error' => 'GET method not allowed! Use POST. pls'], 405);
        }

        if ($request->payment_gateway === 'Chapa') {
            $chapaSecretKey = env('CHASECK_TEST-P9nR658M3AHS8xTAAT3F76krn71l1AG5');
            $callbackUrl = route('chapa.callback'); // Define this route for callback handling

            $data = [
                'amount' => $request->grand_total, // Ensure you're passing the correct total amount
                'currency' => 'ETB',
                'email' => auth()->user()->email ?? 'guest@example.com',
                'first_name' => auth()->user()->name ?? 'Guest',
                'phone' => auth()->user()->phone ?? '0000000000',
                'tx_ref' => 'CHAPA_' . uniqid(), // Unique transaction reference
                'callback_url' => $callbackUrl,
                'return_url' => url('/checkout/success'),
                'customization' => [
                    'title' => 'Payment for Order',
                    'description' => 'Order Payment via Chapa',
                ],
            ];

            // Send request to Chapa
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $chapaSecretKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.chapa.co/v1/transaction/initialize', $data);

            $responseBody = $response->json();

            if ($response->successful() && isset($responseBody['data']['checkout_url'])) {
                return redirect()->away($responseBody['data']['checkout_url']);
            } else {
                return redirect()->back()->with('error_message', 'Failed to initialize Chapa payment.');
            }
        }

        return redirect()->back()->with('error_message', 'Invalid payment method.');
    }

    public function chapaCallback(Request $request)
    {

        
        // Handle Chapa response (e.g., update order status)
        return redirect('/checkout/success')->with('success_message', 'Payment successful!');
    }
}
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function chapaCallback(Request $request)
    {
        // Handle Chapa callback and update order status
        // You can verify the payment and update the order status accordingly

        return redirect('/order-success')->with('success_message', 'Payment successful!');
    }
}