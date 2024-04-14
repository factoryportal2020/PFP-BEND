<?php

namespace App\Http\Controllers\Api\Website\Auth;

use App\Http\Controllers\BaseController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Http\Request;
use App\Models\Website;

class AuthController extends BaseController
{
    protected $usercontroller;

    public function __construct()
    {
        $this->usercontroller = new UserController();
    }

    public function getAdmin(Request $request)
    {
        try {
            $response = [];
            //$admin_id = 3;
            $admin_name = $request->site_url;
            $website = Website::where("site_url", $admin_name)->where('status', 1)->first();

            if(!$website){
                return $this->responseAPI(false, "Website not launched", 200, []);
            }

            unset($website->aboutImages);
            unset($website->featureImages);
            unset($website->bannerImages);
            unset($website->logoImages);

            $adminInfo = [
                'company_name' => $website->company_name,
                'site_url' => $website->site_url,
            ];

            $data = [
                'adminToken' => encryptID($website->admin_id, 'e'),
                'adminInfo' => $adminInfo,
            ];

            return $this->responseAPI(true, "Get admin successfully", 200, $data);
        } catch (\Exception $e) {
            return $this->responseAPI(false, $e->getMessage(), 200);
        }
    }
}
