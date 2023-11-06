<?php

namespace App\Http\Controllers\Ecommerce;

use Carbon\Carbon;
use App\Models\Gift;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Plan;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return DB::table('orders')
            ->where('orders.user_id', auth()->user()->id)
            ->join('products', 'products.id', '=', 'orders.product_id')
            ->select('products.name as product_name', 'orders.*')
            ->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $allowed_values = ['full', 'partial'];
        $request->validate([
            'intent' => ['required', Rule::in($allowed_values)],
            'productId' => 'required|exists:products,id',
        ]);

        $product = Product::find($request['productId']);

        if ($product->minimum_payable_amount > 0) {
            $request->validate([
                'paymentId' => 'required'
            ]);
        }

        if ($request->intent == 'full') {
            $health_points = 0;
            $amount = $product->price;
            $atp_stars = 0;
            $ad_points = 0;
            if (!empty($request['giftCard']) || !is_null($request['giftCard'])) {
                $this->useGift($request['giftCard']);
            }
        } else {
            $health_points = $product->health_point;
            $atp_stars = $product->atp_point;
            $ad_points = $product->ad_point;
            $amount = $product->minimum_payable_amount;
            $transaction_id = $request['paymentId'];
            $this->deductPoints($health_points, $atp_stars, $ad_points, $transaction_id, $amount);
        }
        $order_id = uniqid('ORD');
        $this->gatewayPayment($request['paymentId'] ?? $order_id, $amount, 'ecommerce-product');

        return Order::create([
            'user_id' => auth()->user()->id,
            // 'order_id' => $request['orderId'],
            'amount' => $amount,
            'intent' => $request->intent,
            // 'razorpay_payment_signature' => $request['signature'],
            'razorpay_payment_id' => $request['paymentId'],
            'status' => 'ordered',
            'receipt' => $order_id,
            'purpose' => 'ecommerce-product',
            'metadata' => null,
            'health_points' => $health_points,
            'atp_stars' => $atp_stars,
            'ad_points' => $ad_points,
            'product_id' => $product->id
        ]);
    }

    public function storeTest(array $metadata, string $order_id, array $razorpay)
    {
        if ($metadata['intent'] == 'full') {
            if (!empty($metadata['gift_card']) || !is_null($metadata['gift_card'])) {
                $this->useGift($metadata['gift_card']);
            }
        } else {
            $this->deductPoints($metadata['health_points'], $metadata['atp_stars'], $metadata['ad_points'], $razorpay['razorpay_payment_id'], $metadata['amount'], $metadata['user_id']);
        }

        return Order::create([
            'user_id' => $metadata['user_id'],
            'order_id' => $order_id,
            'amount' => $metadata['amount'],
            'razorpay_signature' => $razorpay['razorpay_signature'],
            'razorpay_payment_id' => $razorpay['razorpay_payment_id'],
            'status' => 'ordered',
            'receipt' => uniqid('ORD'),
            'purpose' => 'ecommerce-product',
            'metadata' => null,
            'health_points' => $metadata['health_points'],
            'atp_stars' => $metadata['atp_stars'],
            'ad_points' => $metadata['ad_points'],
            'product_id' => $metadata['product_id']
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function deductPoints($health_points, $atp_stars, $ad_points, $transaction_id, $amount, $user_id = null)
    {
        $user = User::find(auth()->user()->id ?? $user_id);
        $final_ad_points = $user->ad_points - $ad_points;
        if ($health_points > 0) {
            $debit = DB::table('point_distribution')->insert([
                'user_id' => $user->id,
                'beneficiary_id' => $user->id,
                'points' => -$health_points,
                'expiry_at' => Carbon::now()->addYears(10),
                'transaction_id' => $transaction_id,
                'created_at' => now(),
                'purpose' => 'ecommerce',
                'updated_at' => now(),
                'approved' => 1
                // 'expiry_at' => Carbon::now()->addMonth()
            ]);
        }
        if ($ad_points > 0) {
            $user->update([
                'ad_points' => $final_ad_points,
            ]);
        }

        if ($atp_stars > 0) {

            DB::table('virolife_donation')->insert([
                'transaction_id' => $transaction_id,
                'user_id' => $user->id,
                'amount' => $amount,
                'points' => -$atp_stars,
                'created_at' => now(),
                'purpose' => 'ecommerce',
                'updated_at' => now()
            ]);
        }
    }

    public function adminIndex()
    {
        return DB::table('orders')
            ->join('users', 'users.id', '=', 'orders.user_id')
            ->join('products', 'products.id', '=', 'orders.product_id')
            ->select('users.name as user_name', 'products.name as product_name', 'orders.*')
            ->get();
    }

    public function useGift($code)
    {
        $gift = Gift::where(['code' => $code, 'user_id' => auth()->user()->id, 'redeemed' => 0])->first();
        if (!$gift) {
            return response('Gift card not found.', 404);
        }
        Gift::where(['user_id' => auth()->user()->id, 'code' => $code])->update(['redeemed' => 1]);
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required'
        ]);
        return Order::where('id', $id)->update([
            'status' => $request['status']
        ]);
    }

    public function generateOrderId(Request $request)
    {
        $allowed_orders = ['primary_group', 'secondary_group', 'ecommerce', 'viro-team'];
        $request->validate([
            'orderType' => ['required', Rule::in($allowed_orders)],
        ]);

        $user = auth()->user();

        $order_type = $request['orderType'];
        switch ($order_type) {
            case 'primary_group':
                $request->validate([
                    'id' => 'required|exists:users,id'
                ]);
                $id = $request['id'];
                $count = DB::table('users')->where('parent_id', $id)->count();
                if ($count >= 4) {
                    return response("Senior has already enough members.", 400);
                }
                $metadata = [
                    'group_id' => $id,
                    'user_id' => $user->id
                ];
                return $this->createOrder(250, $order_type, json_encode($metadata));
                break;

            case 'secondary_group':
                if (!is_null(auth()->user()->secondary_parent_id)) {
                    return response()->json(["message" => "You can not join more than two groups"], 400);
                }
                $metadata = [
                    'user_id' => $user->id
                ];
                return $this->createOrder(500, $order_type, json_encode($metadata));
                break;

            case 'ecommerce':
                $allowed_values = ['full', 'partial'];
                $request->validate([
                    'intent' => ['required', Rule::in($allowed_values)],
                    'productId' => 'required|exists:products,id',
                ]);

                $product = Product::find($request['productId']);

                if ($request->intent == 'full') {
                    $health_points = 0;
                    $amount = $product->price + $product->delivery_charges;
                    $atp_stars = 0;
                    $ad_points = 0;
                    $gift = $request['giftCard'] ?? null;
                } else {
                    $health_points = $product->health_point;
                    $atp_stars = $product->atp_point;
                    $ad_points = $product->ad_point;
                    $amount = $product->minimum_payable_amount + $product->delivery_charges;
                    $gift = null;
                }
                $metadata = [
                    'user_id' => $user->id,
                    'product_id' => $request['productId'],
                    'intent' => $request['intent'],
                    'atp_stars' => $atp_stars,
                    'health_points' => $health_points,
                    'ad_points' => $ad_points,
                    'amount' => $amount,
                    'gift' => $gift,
                ];

                return $this->createOrder($amount, $order_type, json_encode($metadata));
                break;

            case 'viro-team':
                $request->validate([
                    'planId' => 'required|exists:plans,id',
                    'parentId' => 'required|exists:users,id',
                    'referralId' => 'nullable|exists:users,id'
                ]);
                $plan = Plan::find($request['planId']);
                $metadata = [
                    'user_id' => auth()->user()->id,
                    'parent_id' => $request['parentId'],
                    'referral_id' => $request['referralId'] ?? null,
                    'plan_id' => $request['planId'],
                ];
                $amount = $plan->price;
                return $this->createOrder($amount, $order_type, json_encode($metadata));
                break;

            default:
                return response("Bad Request", 400);
                break;
        }
    }
}
