<?php

namespace App\Listeners;

use DB;
use App\Models\OrderItem;
use App\Events\OrderReviewed;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class UpdateProductRating
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  OrderReviewed  $event
     * @return void
     */
    public function handle(OrderReviewed $event)
    {
        $order = $event->getOrder();

        $items = $order->items()->with(['product'])->get();
        foreach ($items as $item) {
            $result = OrderItem::query()
            ->where('product_id', $item->product_id)
            ->whereNotNull('reviewed_at')
            ->whereHas('order', function($query){
                $query->whereNotNull('paid_at');
            })
            ->first([
                DB::raw('count(*) as review_count'),
                DB::raw('avg(rating) as rating')
            ]);
            $item->product->update([
                'rating' => $result->rating,
                'review_count' => $result->review_count,
            ]);
        }
    }
}
