<?php

namespace App\Http\Controllers;

use App\Exceptions\CouponCodeUnavailableException;
use App\Models\CouponCode;
use Carbon\Carbon;

use Illuminate\Http\Request;

class CouponCodesController extends Controller
{
    public function show($code)
    {
        if (!$recode = CouponCode::where('code', $code)->first()){
            abort(404);
        }

        $this->checkAvailable();

        return $recode;
    }

    public function checkAvailable($orderAmount = null)
    {
        if (!$this->enabled) {
            throw new CouponCodeUnavailableException('优惠卷不存在');
        }

        if ($this->total - $this->used <= 0) {
            throw new CouponCodeUnavailableException('该优惠卷已被兑完');
        }

        if ($this->not_before && $this->not_before->gt(Carbon::now())) {
            throw new CouponCodeUnavailableException('该优惠卷还不能使用');
        }

        if ($this->not_after && $this->not_after->lt(Carbon::now())) {
            throw new CouponCodeUnavailableException('优惠卷已过期');
        }

        if (!is_null($orderAmount) && $orderAmount < $this->min_amount) {
            throw new CouponCodeUnavailableException('订单金额不满足优惠条件');
        }
    }
}
