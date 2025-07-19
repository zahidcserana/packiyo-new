<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkPrintRequest;
use Illuminate\Http\Request;

class SiteController extends Controller
{
    public function filterCountries(Request $request)
    {
        return app('site')->filterCountries($request);
    }
}
