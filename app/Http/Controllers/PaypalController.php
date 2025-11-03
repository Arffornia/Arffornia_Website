<?php

namespace App\Http\Controllers;

use App\Models\ShopItem;
use Illuminate\Http\Request;
use Srmklive\PayPal\Services\PayPal as PayPalClient;
use Illuminate\Support\Facades\Log;
use App\Models\PaypalTransaction;
use App\Models\User;

class PaypalController extends Controller
{
    public function create(Request $request)
    {
        $request->validate(['shop_item_id' => 'required|exists:shop_items,id']);

        $item = ShopItem::findOrFail($request->shop_item_id);

        if ($item->payment_type !== 'real_money' || !$item->price) {
            return redirect()->route('shop.view')->with('error', 'This item is not available for purchase with real money.');
        }

        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();


        $response = $provider->createOrder([
            "intent" => "CAPTURE",
            "application_context" => [
                "return_url" => route('paypal.success'),
                "cancel_url" => route('paypal.cancel'),
            ],
            "purchase_units" => [[
                "amount" => [
                    "currency_code" => $item->currency,
                    "value" => $item->real_money_price
                ],
                "description" => $item->name . ' for Arffornia Server.'
            ]]
        ]);

        if (isset($response['id']) && $response['id'] != null) {
            PaypalTransaction::create([
                'user_id' => auth()->user()->id,
                'paypal_order_id' => $response['id'],
                'status' => 'created',
                'amount' => $item->real_money_price,
                'currency' => $item->currency,
                'coins_purchased' => $item->coins_awarded,
            ]);

            session()->put('paypal_shop_item_id', $item->id);

            foreach ($response['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return redirect()->away($link['href']);
                }
            }
        }

        Log::error('PayPal order creation failed', ['response' => $response]);
        return redirect()->route('shop.view')->with('error', 'Could not connect to PayPal. Please try again.');
    }

    public function success(Request $request)
    {
        $provider = new PayPalClient;
        $provider->setApiCredentials(config('paypal'));
        $provider->getAccessToken();
        $response = $provider->capturePaymentOrder($request['token']);

        $transaction = PaypalTransaction::where('paypal_order_id', $request['token'])->firstOrFail();

        if (isset($response['status']) && $response['status'] == 'COMPLETED') {
            $user = auth()->user();
            $item = ShopItem::find(session()->pull('paypal_shop_item_id'));

            $transaction->update(['status' => 'completed', 'paypal_response' => $response]);

            $user->money += $item->coins_awarded;
            $user->save();

            Log::info('Successful coin purchase', [
                'user_id' => $user->id,
                'order_id' => $request['token'],
                'item' => $item->name
            ]);

            return redirect()->route('shop.view')->with('message', $item->coins_awarded . ' coins have been added to your account!');
        }

        $transaction->update(['status' => 'failed', 'paypal_response' => $response]);
        Log::error('PayPal payment capture failed', ['response' => $response]);
        return redirect()->route('shop.view')->with('error', 'Payment could not be validated.');
    }

    public function cancel(Request $request)
    {
        $transaction = PaypalTransaction::where('paypal_order_id', $request['token'])->first();
        if ($transaction) {
            $transaction->update(['status' => 'canceled']);
        }
        session()->forget('paypal_shop_item_id');

        return redirect()->route('shop.view')->with('error', 'You have canceled the payment.');
    }
}
