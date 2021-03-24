<?php

use App\Models\Configx;

 
    function route_class()
    {
        return str_replace('.', '-', Route::currentRouteName());
    }

 
