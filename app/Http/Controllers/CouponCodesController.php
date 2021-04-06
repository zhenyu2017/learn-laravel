<?php

namespace App\Http\Controllers;
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

        if ($recode->enabled) {
            abort(404);
        }

        if ($recode->total = $recode->used <= 0) {
            return response()->json(['msg' => '该优惠卷已兑完'], 403);
        }

        if ($recode->not_before && $recode->not_before->gt(Carbon::now())) {
            return response()->json(['msg' => '该卷现在还不能使用'], 403);
        }

        if ($recode->not_after && $recode->not_after->lt(Carbon::now())) {
            return response()->json(['msg' => '该卷已过期'], 403);
        }

        return $recode;
    }
}
