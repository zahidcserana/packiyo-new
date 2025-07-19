<?php

namespace App\Http\Controllers\Api;

use App\Events\NewPrintJobEvent;
use App\Http\Controllers\ApiController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Ably\AblyRest as Ably;
use Illuminate\Support\Facades\Log;

/**
 * Class AblyController
 * @package App\Http\Controllers\Api
 * @group Ably
 */
class AblyController extends ApiController
{
    public function __construct()
    {
        //$this->middleware('auth:api');
    }

    /**
     * Generate an Ably auth token
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function generateToken(Request $request): JsonResponse
    {
        try {
            $ably = new Ably(config('broadcasting.connections.ably.sub_key'));

            $tokenParams = [
                'capability' => [
                    'public:' . NewPrintJobEvent::channelName()  => ['subscribe']
                ]
            ];

            $tokenRequest = $ably->auth->createTokenRequest($tokenParams);

            return response()->json($tokenRequest->toArray());
        } catch (\Exception $exception) {
            return response()->json(['message' => $exception->getMessage()], 500);
        }
    }
}
