<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Exceptions\InvalidRequestException;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;
use qrcode
class PaymentController extends Controller
{
    public function payByAlipay(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->paid_at || $order->closed) {
            throw new InvalidRequestException('订单状态不正确');
        }
         return app('alipay')->web([
             'out_trade_no' => $order->no,
             'total_amount' => $order->total_amount,
             'subject'   => '支付单号：'. $order->no,
         ]);
    }

    public function alipayReturn()
    {
        try {
            app('alipay')->verify();
        } catch (\Exception $e) {
            return view('pages.error', ['msg' => '数据不正确']);
        }

        return view('pages.success', ['msg'=>'付款成功']);
    
    }

    public function alipayNotify()
    {
        $data = app('alipay')->verify();

        if(!in_array($data->trade_status, ['TRADE_SUCCESS', 'TRADE_FINISHED'])) {
            return app('alipay')->success();
        }

        $order = Order::where('no', $data->out_trade_no)->first();
        
        if (!$order) {
            return 'fail';
        }
        if ($order->paid_at) {
            return app('alipay')->success();
        }

        $order->update([
            'paid_at' => Carbon::now(),
            'payment_method' => 'alipay',
            'payment_no' => $data->trade_no,
        ]);
       
    }

    public function payByWechat(Order $order, Request $request)
    {
        $this->authorize('own', $order);

        if ($order->paid_at || $order->closed)
        {
            throw new InvalidRequestException('订单状态不正确');
        }

        $wechatOrder =  app('wechat_pay')->scan([
            'out_trade_no' => $order->no,
            'total_fee' => $order->amount * 100,
            'body' => '订单：' . $order->no,
        ]);

        $qrCode = new QrCode($wechatOrder->code_url);
        return response($qrCode->writeString(), 200, ['Content-Type' => $qrCode->getContentType()]);

    }

    public function wechatNotify()
    {
        $data = app('wechat_pay')->verify();

        $order = Order::where('no', $data->out_trade_no)->first();
        if (!$order) {
            return 'fail';
        }

        if ($order->paid_at) {
            return app('wechat_pay')->success();
        }
        $order->update([
            'paid_at' => Carbon::now(),
            'payment_method' => 'wechat',
            'payment_no' => $data->transaction_id,
        ]);

        return app('wechat_pay')->success();
    }
}
