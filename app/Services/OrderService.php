<?php
namespace App\Services;

use App\Exceptions\InvalidRequestException;
use App\Jobs\CloseOrder;
use App\Models\User;
use App\Models\UserAddress;
use Carbon\Carbon;
use App\Models\Order;
use App\Models\ProductSku;
use App\Exceptions\CouponCodeUnavailableException;
use App\Models\CouponCode;

class OrderService
{
    public function store(User $user, UserAddress $address, $remark, $items, CouponCode $coupon = null)
    {
        if ($coupon) {
            $coupon->checkAvailable();
        }
        $order = \DB::transaction(function() use ($user, $address, $remark, $items, $coupon){
            $address->update(['last_used_at', Carbon::now()]);

            $order = new Order([
                'address' => [
                    'address' => $address->full_address,
                    'zip' => $address->zip,
                    'contact_name' => $address->contact_name,
                    'contact_phone' => $address->contact_phone,
                ],
                'remark' => $remark,
                'total_amount' => 0,
            ]);
          
            $order->user()->associate($user);
            $order->save();

            $totalAmount = 0;
            foreach ($items as $data){
                $sku = ProductSku::find($data['sku_id']);
                //使用make关联创建子项目
                $item = $order->items()->make([
                    'amount' => $data['amount'],
                    'price'  => $sku->price,
                ]);
                $item->product()->associate($sku->product_id);
                $item->productSku()->associate($sku);
                $item->save();
                $totalAmount += $data['amount'] * $sku->price;
                if ($sku->decreaseStock($data['amount']) <= 0) {
                    throw new InvalidRequestException('商品数量不足');
                }

            }

            if ($coupon) {
                $coupon->checkAvailable($totalAmount);
                $totalAmount = $coupon->getAdjustedPrice($totalAmount);
                $order->couponCode()->associate($coupon);
                if ($coupon->changUsed() <= 0) {
                    throw new CouponCodeUnavailableException('优惠卷已被兑完');
                }
            }

            $order->update(['total_amount' => $totalAmount]);

            $skuIds = collect($items)->pluck('sku_id')->all();
            app(CartService::class)->remove($skuIds);
            
            return $order;
        });

        dispatch(new CloseOrder($order, config('app.order_ttl')));

        return $order;

    }
}

