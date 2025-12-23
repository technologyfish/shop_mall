<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    /**
     * 购物车列表
     */
    public function index()
    {
        $user = auth()->user();
        $carts = Cart::where('user_id', $user->id)
            ->with('product')
            ->get();

        return $this->success($carts);
    }

    /**
     * 添加到购物车
     */
    public function add(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $product = Product::find($request->product_id);

        // 检查商品状态和库存
        if ($product->status != 1) {
            return $this->error('Product is not available', 400);
        }

        if (!$product->hasStock($request->quantity)) {
            return $this->error('Insufficient stock', 400);
        }

        // 检查是否已在购物车
        $cart = Cart::where('user_id', $user->id)
            ->where('product_id', $request->product_id)
            ->first();

        if ($cart) {
            $cart->quantity += $request->quantity;
            $cart->save();
        } else {
            $cart = Cart::create([
                'user_id' => $user->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }

        return $this->success($cart->load('product'), 'Added to cart successfully');
    }

    /**
     * 更新购物车
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        $cart = Cart::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$cart) {
            return $this->error('Cart item not found', 404);
        }

        // 检查库存
        if (!$cart->product->hasStock($request->quantity)) {
            return $this->error('Insufficient stock', 400);
        }

        $cart->quantity = $request->quantity;
        $cart->save();

        return $this->success($cart->load('product'), 'Cart updated successfully');
    }

    /**
     * 删除购物车商品
     */
    public function remove($id)
    {
        $user = auth()->user();
        $cart = Cart::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$cart) {
            return $this->error('Cart item not found', 404);
        }

        $cart->delete();

        return $this->success(null, 'Cart item deleted successfully');
    }

    /**
     * 清空购物车
     */
    public function clear()
    {
        $user = auth()->user();
        Cart::where('user_id', $user->id)->delete();

        return $this->success(null, 'Cart cleared successfully');
    }

    /**
     * 批量删除选中的购物车商品
     */
    public function deleteSelected(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation failed', 422, $validator->errors());
        }

        $user = auth()->user();
        Cart::where('user_id', $user->id)
            ->whereIn('id', $request->ids)
            ->delete();

        return $this->success(null, 'Selected items deleted successfully');
    }

    /**
     * 切换购物车商品选中状态
     */
    public function toggleSelect(Request $request, $id)
    {
        $user = auth()->user();
        $cart = Cart::where('id', $id)
            ->where('user_id', $user->id)
            ->first();

        if (!$cart) {
            return $this->error('Cart item not found', 404);
        }

        $cart->selected = $request->input('selected', !$cart->selected);
        $cart->save();

        return $this->success($cart->load('product'), 'Selection updated successfully');
    }

    /**
     * 全选/取消全选
     */
    public function selectAll(Request $request)
    {
        $user = auth()->user();
        $selected = $request->input('selected', true);

        Cart::where('user_id', $user->id)->update(['selected' => $selected]);

        return $this->success(null, 'All items selection updated successfully');
    }
}







