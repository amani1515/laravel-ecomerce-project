<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Cart;
use App\Models\Payment;
use App\Models\ProductsAttribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
    // Show the Chapa payment page
    public function chapaPayment()
    {
        return view('front.chapa_payment'); // Ensure this file exists in resources/views/front/
    }

    // Process Chapa payment
    public function processChapaPayment(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:1'
        ]);

        $tx_ref = 'TX-' . uniqid(); // Unique transaction reference
        $amount = $request->input('amount');
        $email = auth()->user()->email ?? 'guest@example.com'; // Use user's email or default
        $first_name = auth()->user()->name ?? 'Guest';
        $callback_url = route('chapa.callback'); // Ensure this matches the route name in web.php

        $secretKey = env('CHAPA_SECRET_KEY'); // Fetching from .env

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey,
            'Content-Type' => 'application/json'
        ])->post('https://api.chapa.co/v1/transaction/initialize', [
            'amount' => $amount,
            'currency' => 'ETB',
            'email' => $email,
            'first_name' => $first_name,
            'tx_ref' => $tx_ref,
            'callback_url' => $callback_url
        ]);

        $responseData = $response->json();

        if ($responseData['status'] === 'success') {
            Cart::where('user_id', Auth::user()->id)->delete(); 
            return redirect()->away($responseData['data']['checkout_url']);


        } else {
            return back()->with('error', 'Failed to initiate Chapa payment.');
        }
    }


    
    public function chapaCallback(Request $request)
    {
        $tx_ref = $request->query('tx_ref');

        if (!$tx_ref) {
            return redirect()->route('chapa.payment')->with('error', 'Transaction reference missing.');
        }

        $secretKey = env('CHAPA_SECRET_KEY');

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $secretKey
        ])->get("https://api.chapa.co/v1/transaction/verify/{$tx_ref}");

        $paymentStatus = $response->json();

        if ($response->successful() && $paymentStatus['status'] === 'success') {
            $order = Order::where('tx_ref', $tx_ref)->first();

            if ($order) {
                $order->update(['order_status' => 'paid']);
            }

            Cart::where('user_id', auth()->id())->delete();
                // Update the `order_status` column in `orders` table with 'Paid'    
                $order_id = Session::get('order_id'); // Interacting With The Session: Retrieving Data: https://laravel.com/docs/9.x/session#retrieving-data
                Order::where('id', $order_id)->update(['order_status' => 'Paid']);
            return view('front.paypal.success');



            // return redirect()->route('order.success')->with('success_message', 'Payment successful! Your order is now paid.');
        } else {
            return redirect()->route('order.failed')->with('error_message', 'Payment failed!');
        }
    }
}