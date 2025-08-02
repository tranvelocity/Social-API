<?php

namespace Modules\Core\app\Http\Controllers;

use Illuminate\Support\Facades\Config;

class Controller
{
    protected function getAuthorizedAdminId()
    {
        return \request()->get(Config::get('auth.authorized_admin_id'));
    }

    protected function getAuthorizedMerchantId()
    {
        return \request()->get(Config::get('auth.authorized_merchant_id'));
    }

    protected function getAuthorizedUserId()
    {
        return \request()->get(Config::get('auth.authorized_user_id'));
    }
}
