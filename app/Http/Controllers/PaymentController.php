<?php

namespace App\Http\Controllers;

use App\Events\OrderPaid;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Exceptions\InvalidRequestException;
use Carbon\Carbon;
use Endroid\QrCode\QrCode;


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
        
        $this->afterPaid($order);
        return app('alipay')->success();
       
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
        $this->afterPaid($order);
        return app('wechat_pay')->success();
    }

    protected function afterPaid(Order $order)
    {
        event(new OrderPaid($order));
    }

    public function wechatRefundNotify(Request $request)
    {
        $failXml = '<xml>
            <return_code><![CDATA[FAIL]]></return_code>
            <return_msg><![CDATA[FAIL]]></return_msg>
            </xml>';
            $data = app('wechat_pay')->verify(null,true);

            if (!$order = Order::where('no', $data['out_trade_no'])->first()){
                return $failXml;
            }

            if ($data['refund_status'] === 'SUCCESS') {
                $order->update([
                    'refund_status' => Order::REFUND_STATUS_SUCCESS
                ]);
            } else {
                $extra = $order->extra;
                $extra['refund_failed_code'] = $data['refund_status'];
                $order->update([
                    'refund_status' => Order::REFUND_STATUS_FAILED,
                    'extra' => $extra,
                ]);
            }
            return app('wechat_pay')->success();
    }
}
