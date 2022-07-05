<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    public $guarded = [];

    static function appList($u_id)
    {
        $record = \App\Models\Device::where("u_id", "=", $u_id)->get("app_id");
        if ($record) {
            return array_column($record->toArray(), "app_id");
        }
        return false;
    }
}
