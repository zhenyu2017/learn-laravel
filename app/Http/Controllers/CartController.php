<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\AddCartRequest;
use App\Models\CartItem;
use App\Models\ProductSku;
use App\Services\CartService;

class CartController extends Controller
{
   public function add(AddCartRequest $request, CartService $cartService)
   {
        
       $skuId = $request->input('sku_id');
       $amount = $request->input('amount');
    
       $cartService->add($skuId, $amount);

       return [];
   }

   public function index(Request $request, CartService $cartService)
   {
        $cartItems = $cartService->get();

        $addresses = $request->user()->addresses()->orderBy('last_used_at', 'desc')->get();

        return view('cart.index', ['cartItems' => $cartItems, 'addresses' => $addresses]);
   }

   public function remove(ProductSku $sku, CartService $cartService)
   {
       $cartService->remove($sku->id);

       return [];
   }
}
