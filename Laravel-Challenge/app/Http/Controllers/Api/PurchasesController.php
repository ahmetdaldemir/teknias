<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Register;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

class PurchasesController extends Controller
{
    public function index(Request $request)
    {
        $params = $request->all();
        $key = "ps_" . $params["client_token"];
        $response = Http::post('http://your_domain/api/v1/ios', ['receipt' => $params["receipt"]]);
        $result = json_decode($response->body(), true);
        if ($result["status"] == "false") {
            return response()->json(array("result" => "false", "message" => "purchase failed"), 200);
        }
        $result["expire_date"] = Carbon::createFromTimestamp(strtotime($result["expire_date"]), new \DateTimeZone("GMT+6"));
        $result["expire_date"] = $result["expire_date"]->toDateTimeString();
        $result["status"] = ($result["status"] == "true") ? 1 : 0;
        $result["receipt"] = $params["receipt"];
        $u_id = Helpers::getCacheValue("u_id_" . $params["client_token"]);
        if (!$u_id) {
            $u_id = Register::checkByClientToken($params["client_token"])->u_id;
        }
        $result["u_id"] = $u_id;
        if ($result["status"] &&$this->store($result)->id) {
            Helpers::removeCache($key);
            return response()->json(array("result" => "true", "message" => "purchase successful"), 200);
        }
        return response()->json(array("result" => "false", "message" => "purchase failed"), 200);
    }

    public function store($data)
    {
        return Purchase::create($data);
    }

    public static function getSubscriptions(Request $request)
    {
        $datetime = new Carbon('now', new \DateTimeZone("UTC"));
        $key = "ps_" . $request->get("client_token");
        $list = Helpers::getCacheValue($key);
        if (!$list || $list->count() <= 0) {
            $list = Purchase::getSubscriptions($request->get("client_token"), $datetime->toDateTimeString());
        }
        foreach ($list as $index => $item) {
            if ($item->expire_date < $datetime->toDateTimeString()) {
                unset($list[$index]);
            }
        }
        Helpers::setCacheValue($key, $list, 120);
        return response()->json(array("result" => "true", "subscriptions_list" => $list));
    }
}
