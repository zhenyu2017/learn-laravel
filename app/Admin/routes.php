<?php

use Illuminate\Routing\Router;

Admin::routes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
    'as'            => config('admin.route.prefix') . '.',
], function (Router $router) {

    $router->get('/', 'HomeController@index')->name('home');
    $router->get('users', 'UsersController@index');
    $router->get('products', 'ProductsController@index');
    $router->get('products/create', 'ProductsController@create');
    $router->post('products', 'ProductsController@store');
    $router->get('products/{id}/edit', 'ProductsController@edit');
    $router->put('products/{id}', 'ProductsController@update');
    $router->get('orders', 'OrdersController@index')->name('admin.orders.index');
    $router->get('orders/{order}', 'OrdersController@show')->name('admin.orders.show');
    $router->post('orders/{order}/ship', 'OrdersController@ship')->name('admin.orders.ship');
    $router->post('orders/{order}/refund', 'OrdersController@handleRefund')->name('admin.orders.handle_refund');
    $router->get('coupon_code', 'CouponCodeController@index')->name('admin.coupon_code');
    $router->post('coupon_code', 'CouponCodeController@store')->name('admin.coupon_code.store');
    $router->get('coupon_code/create', 'CouponCodeController@create');
    $router->get('coupon_code/{id}/edit', 'CouponCodeController@edit');
    $router->put('coupon_code/{id}', 'CouponCodeController@update')->name('admin.coupon_code.update');
    $router->delete('coupon_code/{id}', 'CouponCodeController@distroy');
});
