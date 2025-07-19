<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use LaravelJsonApi\Core\Exceptions\JsonApiException;
use Request;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * @param Throwable $exception
     * @throws Throwable
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    protected function context()
    {
        try {
            return array_filter([
                'user_id' => \Auth::id(),
                'token' => \Auth::user()->currentAccessToken() ?? null,
                'full_url' => Request::fullUrl(),
                'payload' => request()->all()
            ]);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Throwable $exception
     * @return JsonResponse|Response|\Symfony\Component\HttpFoundation\Response
     * @throws Throwable
     */
    public function render($request, Throwable $exception)
    {
        if ($exception instanceof TokenMismatchException) {
            return redirect()->back();
        }

        return parent::render($request, $exception);
    }

    public function register()
    {
        $this->renderable(\LaravelJsonApi\Exceptions\ExceptionParser::make()->renderable());

        $this->reportable(function (JsonApiException $exception) {
            if (!$exception->is5xx()) {
                return false;
            }
        });
    }
}
