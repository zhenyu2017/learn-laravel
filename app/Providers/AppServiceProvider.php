<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;
use Monolog\Logger;
use Yansongda\Pay\Pay;




class AppServiceProvider extends ServiceProvider
{
    
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
      
    //    dd(config('alipay.appid'));
      $this->app->singleton('alipay', function(){
        $config = config('pay.alipay');
        $config['notify_url'] = route('payment.alipay.notify');
        $config['return_url'] =  route('payment.alipay.return');
        if (app()->environment() !== 'production') {
            $config['mode'] = 'dev';
            $config['log']['level'] = Logger::DEBUG;
        } else {
            $config['log']['level'] = Logger::WARNING;
        }

        return Pay::alipay($config);
      });

      $this->app->singleton('wechat_pay', function(){
        $config = config('pay.wechat');
        $config['nofity_url'] = route('payment.wechat.notify');
        if (app()->environment() !== 'production') {
            $config['log']['level'] = Logger::DEBUG;
        } else {
            $config['log']['level'] = Logger::WARNING;
        }
        return Pay::wechat($config);
      });
     
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
        \Illuminate\Pagination\Paginator::useBootstrap();
   
               
        
    }
}
