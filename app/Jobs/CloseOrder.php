<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Order;

class CloseOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $order;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(Order $order, $delay)
    {
        $this->order = $order; //传入操作对象
        $this->delay($delay); //设置延迟时间
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->order->paid_at) {
            return ; //订单如果已支付则直接返回，不需后续操作。
        }

        
        \DB::transaction(function(){
            $this->order->update(['closed' => true]);
            foreach ($this->order->items as $item) {
                $item->productSku->addStock($item->amount); // 增加商品库存
            }
            if ($this->order->couponCode) {
                $this->order->couponCode->changeUsed(false);
            }
        });
    }
}
