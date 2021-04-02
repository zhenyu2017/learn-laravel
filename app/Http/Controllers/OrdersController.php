<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Requests\OrderRequest;
use App\Models\Order;
use App\Models\UserAddress;
use App\Services\OrderService;
use App\Exceptions\InvalidRequestException;
use Carbon\Exceptions\InvalidIntervalException;
use App\Http\Requests\SendReviewRequest;
use Carbon\Carbon;
use App\Events\OrderReviewed;

class OrdersController extends Controller
{

    public function index(Request $request)
    {
        $orders = Order::query()
        ->with(['items.product', 'items.productSku'])
        ->where('user_id', $request->user()->id)
        ->orderBy('created_at', 'desc')
        ->paginate();

        return view('orders.index', ['orders' => $orders]);
    }

    public function store(OrderRequest $request, OrderService $orderService)
    {
        $user = $request->user();
        $address = UserAddress::find($request->input('address_id'));
        return $orderService->store($user, $address, $request->input('remark'), $request->input('items'));

    }

    public function show (Order $order)
    {
        $this->authorize('own', $order);
        return view('orders.show', ['order' => $order->load(['items.productSku', 'items.product'])]);
    }

    public function received(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->ship_status !== Order::SHIP_STATUS_DELIVERED){
            throw new InvalidIntervalException('订单状态不正确');
        }

        $order->update([
            'ship_status' => Order::SHIP_STATUS_RECEIVED,
        ]);

        return $order;
    }

    public function review(Order $order)
    {
        $this->authorize('own', $order);

        if (!$order->paid_at) {
            throw new InvalidIntervalException('订单未完成，还不能评价');
        }

        return view('orders.review', ['order' => $order->load(['items.productSku', 'items.product'])]);
        
    }

    public function sendReview(Order $order, SendReviewRequest $request)
    {
        $this->authorize('own', $order);

        if(!$order->paid_at) {
            throw new InvalidIntervalException('订单未完成，不能评价');
        }

        if ($order->review) {
            throw new InvalidIntervalException('该订单已评价，请不要重复评价');
        }

        $reviews = $request->input('reviews');
         \DB::transaction (function() use ($order, $reviews){
            foreach ($reviews as $review){
                $orderItem = $order->items()->find($review['id']);
                $orderItem->update([
                    'rating' => $review['rating'],
                    'review' => $review['review'],
                    'reviewed_at' => Carbon::now(),
                ]);
            }
            $order->update(['reviewed' => true]);
         });

         event(new OrderReviewed($order));

         return redirect()->back();
    }
}
