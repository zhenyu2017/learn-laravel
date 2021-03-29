<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminConfig extends Model
{
    use HasFactory;
    protected $table = 'admin_config';

    protected $fillable = ['name', 'value', 'description'];

    public static function group($prefix)
    {
        return static::where('name', 'like', "{$prefix}.%")->get()->toArray();
    }
    //todo:: 用config($key) 方法不能取出配置值

  
}
