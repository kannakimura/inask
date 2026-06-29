<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Policyによる認可チェックを全Controllerで使えるようにする
    use AuthorizesRequests;
}
