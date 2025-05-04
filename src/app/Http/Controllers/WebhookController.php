<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\Stripe;
use Stripe\Webhook;
use App\Models\Purchase;
use App\Models\Item;
use Log;

class WebhookController extends Controller
{
    public function handle(Request $request)
    {


        Log::info("Stripe Webhook received");
        
        Stripe::setApiKey(env('STRIPE_SECRET'));
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');

        $payload = $request->getContent();
        $sig_header = $request->header('Stripe-Signature');

        try {
            $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
        } catch (\Exception $e) {
            Log::error("Webhook signature verification failed: " . $e->getMessage());
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        // ✅ checkout.session.completed イベント
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $purchase_id = $session->metadata->purchase_id ?? null;

            if ($purchase_id) {
                $purchase = Purchase::find($purchase_id);
                if ($purchase) {
                    $purchase->update(['status' => 'completed']);
                    $item = Item::find($purchase->item_id);
                    if ($item) {
                        $item->update(['sold_flag' => 1]);
                    }
                    Log::info("Purchase confirmed via checkout.session.completed: $purchase_id");
                }
            } else {
                Log::warning("checkout.session.completed received without purchase_id");
            }
        }

        // ✅ payment_intent.succeeded イベント
        if ($event->type === 'payment_intent.succeeded') {
            $intent = $event->data->object;
            $purchase_id = $intent->metadata->purchase_id ?? null;

            if ($purchase_id) {
                $purchase = Purchase::find($purchase_id);
                if ($purchase) {
                    $purchase->update(['status' => 'completed']);
                    $item = Item::find($purchase->item_id);
                    if ($item) {
                        $item->update(['sold_flag' => 1]);
                    }
                    Log::info("Purchase completed via payment_intent.succeeded: $purchase_id");
                } else {
                    Log::warning("Purchase not found for ID: $purchase_id");
                }
            } else {
                Log::warning("payment_intent.succeeded received without purchase_id");
            }
        }

        // ✅ charge.succeeded イベント
        if ($event->type === 'charge.succeeded') {
            $charge = $event->data->object;
            $purchase_id = $charge->metadata->purchase_id ?? null;

            if ($purchase_id) {
                $purchase = Purchase::find($purchase_id);
                if ($purchase) {
                    $purchase->update(['status' => 'completed']);
                    $item = Item::find($purchase->item_id);
                    if ($item) {
                        $item->update(['sold_flag' => 1]);
                    }
                    Log::info("Purchase completed via charge.succeeded: $purchase_id");
                } else {
                    Log::warning("Purchase not found for ID: $purchase_id");
                }
            } else {
                Log::warning("charge.succeeded received without purchase_id");
            }
        }

        // ✅ 最後に共通のレスポンスを返す
        return response()->json(['status' => 'success']);
    }
}
