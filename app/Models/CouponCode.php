<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Traits\DefaultDatetimeFormat;
use Illuminate\Support\Str;
use App\Exceptions\CouponCodeUnavailableException;
use Carbon\Carbon;
use App\Models\User;

class CouponCode extends Model
{
    use HasFactory;
    USE DefaultDatetimeFormat;
    
    const TYPE_FIXED = 'fixed';
    const TYPE_PERCENT = 'percent';

    public static $typeMap = [
        self::TYPE_FIXED => '固定额',
        self::TYPE_PERCENT => '比例',
    ];

    protected $fillable = [
        'name',
        'code',
        'type',
        'value',
        'total',
        'used',
        'min_amount',
        'not_before',
        'not_after',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    protected $dates = ['not_before', 'not_after'];

    protected $appends = ['description'];

    public static function findAvailableCode($length = 16)
    {
        do {
            $code = strtoupper(Str::random($length));
            // 如果生成的码已存在就继续循环
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }

    public function getDescriptionAttribute()
    {
        $str='';
        if ($this->min_amount > 0) {
            $str = '满'.str_replace('.00', '', $this->min_amount);
        }
        if ($this->type === self::TYPE_PERCENT) {
            return $str.'优惠'.str_replace('.00', '', $this->value).'%';
        }

        return $str.'减'.str_replace('.00', '', $this->value);
    }

    public function getAdjustedPrice($orderAmount)
    {
        if ($this->type === self::TYPE_FIXED) {
            return max(0.01, $orderAmount - $this->value);
        }

        return number_format($orderAmount * (100 - $this->value) / 100, 2, '.','');
    }

    public function changUsed($increase = true)
    {
        if ($increase){
            return $this->where('id', $this->id)->where('used', '<', $this->total)->increment('used');
   
        } else {
            return $this->decrement('used');
        }
    }

    public function checkAvailable(User $user, $orderAmount = null)
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

        $used = Order::where('user_id', $user->id)
        ->where('coupon_code_id', $this->id)
        ->where(function($query){
            $query->where(function($query){
                $query->whereNull('paid_at')
                ->where('closed', false);
            })->orWhere(function($query){
                $query->whereNotNull('paid_at')
                ->where('refund_status', '!=', Order::REFUND_STATUS_SUCCESS);
            });
        })->exists();

        if ($used) {
            throw new CouponCodeUnavailableException('你已经使用过这张优惠卷');
        }
    }

}
