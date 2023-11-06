<?php

namespace App\Http\Controllers\Ecommerce\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function __construct()
    {
        $this->middleware('role:admin', ['except' => ['index', 'show']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request['search'];
        if (!is_null($search) || !empty($search)) {
            return Product::with('category')->where('name', 'like', '%' . $search . '%')->get();
        }
        return Product::with('category')->get();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string'],
            'description' => ['required', 'string'],
            'categoryId' => ['required', 'exists:categories,id'],
            'longDescription' => ['required', 'string'],
            'quantity' => ['integer'],
            'price' => ['required', 'numeric', 'min:1'],
            'discount' => ['nullable', 'numeric'],
            'minimumPayableAmount' => ['required', 'integer'],
            'healthPoint' => ['required'],
            'atpPoint' => ['required'],
            'adPoint' => ['required'],
            'giftCardStatus' => ['required', 'boolean'],
        ]);

        if ($request->hasFile('files')) {
            $files = [];
            foreach ($request->file('files') as $file) {
                $file = $file->store('products');
                array_push($files, $file);
            }
        }

        return Product::create([
            'category_id' => $request['categoryId'],
            'name' => $request['name'],
            'status' => $request['status'],
            'images' => json_encode($files) ?? null,
            'description' => $request['description'],
            'long_description' => $request['longDescription'],
            'delivery_charges' => $request['deliveryCharges'],
            'quantity' => $request['quantity'],
            'price' => $request['price'],
            'discount' => $request['discount'],
            'striked_price' => $request['strikedPrice'],
            'minimum_payable_amount' => $request['minimumPayableAmount'],
            'health_point' => $request['healthPoint'],
            'atp_point' => $request['atpPoint'],
            'ad_point' => $request['adPoint'],
            'gift_card_status' => $request['giftCardStatus'],
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return Product::with('category')->where('id', $id)->first();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        return Product::where('id', $id)->update([
            'category_id' => $request['categoryId'] ?? $product->category_id,
            'name' => $request['name'] ?? $product->name,
            'status' => $request['status'] ?? $product->status,
            'description' => $request['description'] ?? $product->description,
            'long_description' => $request['longDescription'] ?? $product->long_description,
            'quantity' => $request['quantity'] ?? $product->quantity,
            'price' => $request['price'] ?? $product->price,
            'discount' => $request['discount'] ?? $product->discount,
            'delivery_charges' => $request['deliveryCharges'] ?? $product->delivery_charges,
            'striked_price' => $request['strikedPrice'] ?? $product->striked_price,
            'minimum_payable_amount' => $request['minimumPayableAmount'] ?? $product->minimum_payable_amount,
            'health_point' => $request['healthPoint'] ?? $product->health_point,
            'atp_point' => $request['atpPoint'] ?? $product->atp_point,
            'ad_point' => $request['adPoint'] ?? $product->ad_point,
            'gift_card_status' => $request['giftCardStatus'] ?? $product->gift_card_status
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        return $product->delete();
    }
    
    public function updateAttachment(Request $request, $id)
    {
        $request->validate([
            'filePath' => 'required',
        ]);
        $data = DB::table('products')->where(['id' => $id])->update([
            'images' => $request['filePath'],
            'updated_at' => now()
        ]);

        return $data;
    }
}
