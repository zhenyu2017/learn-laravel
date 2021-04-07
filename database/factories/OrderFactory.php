<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\CouponCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class OrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Order::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::query()->inRandomOrder()->first();

        $address = $user->addresses()->inRandomOrder()->first();

        $refund = random_int(0, 10) < 1;

        $ship = $this->faker->randomElement(array_keys(Order::$shipStatusMap));

        $coupon = null;
        
        if (random_int(0, 10) < 3) {
            $coupon = CouponCode::query()->where('min_amount', 0)->inRandomOrder()->first();
            $coupon->changUsed();
        }
        return [
            'address'        => [
                'address'       => $address->full_address,
                'zip'           => $address->zip,
                'contact_name'  => $address->contact_name,
                'contact_phone' => $address->contact_phone,
            ],
            'total_amount'   => 0,
            'remark'         => $this->faker->sentence,
            'paid_at'        => $this->faker->dateTimeBetween('-30 days'), // 30天前到现在任意时间点
            'payment_method' => $this->faker->randomElement(['wechat', 'alipay']),
            'payment_no'     => $this->faker->uuid,
            'refund_status'  => $refund ? Order::REFUND_STATUS_SUCCESS : Order::REFUND_STATUS_PENDING,
            'refund_no'      => $refund ? Order::getAvailableRefundNo() : null,
            'closed'         => false,
            'reviewed'       => random_int(0, 10) > 2,
            'ship_status'    => $ship,
            'ship_data'      => $ship === Order::SHIP_STATUS_PENDING ? null : [
                'express_company' => $this->faker->company,
                'express_no'      => $this->faker->uuid,
            ],
            'extra'          => $refund ? ['refund_reason' => $this->faker->sentence] : [],
            'user_id'        => $user->id,
            'coupon_code_id' => $coupon ? $coupon->id : null,
        ];
    }
}
