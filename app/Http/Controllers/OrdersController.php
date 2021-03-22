<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use Illuminate\Http\Request;
use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Models\ProductSku;
use App\Models\UserAddress;
use Carbon\Carbon;
use App\Jobs\CloseOrder;


class OrdersController extends Controller
{
    public function store(OrderRequest $request)
    {
        $user = $request->user();

        $order = \DB::transaction(function () use ($user, $request){
            //更新地址最后使用时间 
            $address = UserAddress::find($request->input('address_id'));
            $address->update(['last_used_at', Carbon::now()]);

            //创建订单 
            $order = new Order([
                'address' => [
                    'address' => $address->full_address,
                    'zip'  => $address->zip,
                    'contact_name' => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark' => $request->input('remark'),
                'total_amount' =>0,
            ]);

            $order->user()->associate($user);
            $order->save();

            $totalAmount = 0;
            $items =  $request->input('items');
            //创建订单明细
            foreach ($items as $data){
                $sku = ProductSku::find($data['sku_id']);
                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price' => $sku->price,
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $sku->price * $data['amount'];
                //更新库存数量
                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('商品库存不足');
                }
            }
            //更新订单总价
            $order->update(['total_amount' => $totalAmount]);
             $skuIds = collect($items)->pluck('sku_id');
             //更新购物车
             $user->cartItems()->whereIn('product_sku_id', $skuIds)->delete();
            
             $this->dispatch(new CloseOrder($order, config('app.order_ttl')));
             return $order; //返回执行成功的结果
        });

        return $order; //返回执行失败的结果
    }
}
