<?php

namespace App\Http\Controllers\Api;

use App\Models\Device;
use App\Helpers\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Register;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RegistersController extends Controller
{
    public function index(Request $request)
    {
        $params = $request->all();
        $key = "uid_" . $params["u_id"];
        $client_token = Helpers::getCacheValue($key);
        if (!$client_token) {
            $client_token = Register::check($params["u_id"]);
            if (!$client_token) {
                $result = $this->storeRegister($params);
                Helpers::setCacheValue($key, $result->client_token, 604800);
                Helpers::setCacheValue("uid_" . $result->client_token, $params["u_id"], 604800);
                return response()->json(
                    array(
                        "result" => "true",
                        "message" => "account created",
                        "client_token" => $result->client_token
                    ), 201
                );
            }
        }
        $this->storeDevice($params);
        return response()->json(
            array(
                "result" => "true",
                "message" => "account already exists",
                "client_token" => $client_token
            ), 200
        );
    }

    public function storeRegister($params)
    {
        $register["u_id"] = $params["u_id"];
        $register["client_token"] = Helpers::getHash($params);
        return Register::create($register);
    }

    public function storeDevice($params)
    {
        $apps_key = "apps_". $params["u_id"];
        $appList = Helpers::getCacheValue($apps_key);
        if (!$appList) {
            Helpers::setCacheValue($apps_key, [$params["app_id"]]);
            return Device::create($params);
        } else {
            if (!in_array($params["app_id"], $appList)) {
                $appList[] = $params["app_id"];
                Helpers::setCacheValue($apps_key, $appList);
                return Device::create($params);
            }
        }
    }

    public function indexDevices(Request $request)
    {
        $datetime = new Carbon('now', new \DateTimeZone("UTC"));
        $params = $request->all();
        $char = ((int)substr($params["receipt"], -1));
        if ($char != 0 && $char % 2 > 0) {
            $result = array(
                "status" => "true",
                "expire_date" => $datetime->toDateTimeString());
        } else {
            $result = array("status" => "false");
        }
        return response()->json($result, 200);
    }
}
