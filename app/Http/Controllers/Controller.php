<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Illuminate\Support\Facades\DB;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;

    public function transaction(int $user_id, int $campaign_id = null, string $transaction_id, string $model, int $model_id, float $credit, float $debit, $opening_balance, $closing_balance, string $metadata)
    {
        Transaction::create([
            'user_id' => $user_id,
            'campaign_id' => $campaign_id,
            'transaction_id' => $transaction_id,
            'purchasable_type' => $model,
            'purchasable_id' => $model_id,
            'credit' => $credit,
            'debit' => $debit,
            'opening_balance' => $opening_balance,
            'closing_balance' => $closing_balance,
            'metadata' => $metadata
        ]);
    }

    public function gatewayPayment(string $payment_id, float $amount, string $purpose, string $metadata = null, $user_id = null)
    {
        DB::table('gateway_payments')->insert([
            'user_id' => auth()->user()->id??$user_id,
            'payment_id' => $payment_id,
            'purpose' => $purpose,
            'amount' => $amount,
            'metadata' => $metadata,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    public function createOrder(float $amount, string $purpose, string $metadata)
    {
        $data = [
            'receipt' => uniqid('VIRO-RP'),
            'amount' => $amount * 100,
            'currency' => 'INR'
        ];

        $api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));

        $response = $api->order->create($data);

        return Order::create([
            'user_id' => auth()->user()->id,
            'order_id' => $response['id'],
            'amount' => $amount,
            'status' => $response['status'],
            'receipt' => $response['receipt'],
            'razorpay_timesamp' => $response['created_at'],
            'purpose' => $purpose,
            'metadata' => $metadata,
        ]);
    }

    public function order(Request $request)
    {
        Log::info($request->all());
        $request->validate([
            'razorpay_payment_id' => 'required|string',
            'razorpay_order_id' => 'required|string|exists:orders,order_id',
            'razorpay_signature' => 'required|string'
        ]);

        // Authenticating Payment
        $generated_signature = hash_hmac('sha256', $request['razorpay_order_id'] . '|' . $request['razorpay_payment_id'], env('RAZORPAY_SECRET'));

        if ($generated_signature !== $request['razorpay_signature']) {
            return response("Payment was not successful.", 400);
        } else {
            $order = Order::where('order_id', $request['razorpay_order_id'])->first();
            $case = $order->purpose;

            switch ($case) {
                case 'primary_group':
                    $this->gatewayPayment($request['razorpay_payment_id'], $order->amount, 'primary-group', $order->user_id);
                    $metadata = (array)json_decode($order->metadata);
                    $group = new GroupController();
                    $group->joinGroupTest($metadata['user_id'], $metadata['group_id'], $request['razorpay_payment_id']);

                    return redirect('https://virolife.in/dashboard/groups');
                    break;

                case 'secondary_group':
                    $this->gatewayPayment($request['razorpay_payment_id'], $order->amount, 'secondary-group', $order->user_id);
                    $metadata = (array)json_decode($order->metadata);
                    $group = new SecondaryGroupController();
                    $group->joinGroupTest($metadata['user_id'], $request['razorpay_payment_id']);

                    return redirect('https://virolife.in/dashboard/groups');
                    break;

                case 'ecommerce':
                    $this->gatewayPayment($request['razorpay_payment_id'], $order->amount, 'ecommerce', $order->user_id);
                    $metadata = (array)json_decode($order->metadata);

                    $product = new Ecommerce\OrderController();
                    $product->storeTest($metadata, $order->order_id, $request->all());
                    return redirect('https://virolife.in/dashboard/orders');

                case 'atp':
                    $this->gatewayPayment($request['razorpay_payment_id'], $order->amount, 'atp', $order->user_id);
                    $metadata = (array)json_decode($order->metadata);

                    $subsription = new Subscription\SubscriptionController();
                    $subsription->purchaseSubscriptionGateway($metadata['plan_id'], $metadata['parent_id'], $metadata['user_id'], $metadata['referral_id']);

                    return redirect('https://virolife.in/dashboard/orders');
                    break;
                default:
                    # code...
                    break;
            }
        }
    }
}
