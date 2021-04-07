<?php

namespace App\Http\Controllers;

use App\Models\CouponCode;
use Illuminate\Http\Request;

class CouponCodesController extends Controller
{
    public function show($code, Request $request)
    {
        if (!$recode = CouponCode::where('code', $code)->first()){
            abort(404);
        }

        $recode->checkAvailable($request->user());

        return $recode;
    }

    
}
