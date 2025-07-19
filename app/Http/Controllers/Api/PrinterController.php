<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\ApiController;
use App\Http\Requests\Printer\PrinterJobStartRequest;
use App\Http\Requests\Printer\PrinterJobStatusRequest;
use App\Models\PrintJob;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use App\JsonApi\V1\Printers\PrinterSchema;
use LaravelJsonApi\Core\Responses\DataResponse;
use LaravelJsonApi\Laravel\Http\Requests\AnonymousCollectionQuery;

class PrinterController extends ApiController
{
    public function import(Request $request)
    {
        $userId = auth()->user()->id;
        $printers = $request->printers;
        $customerId = $request->customer_id;

        foreach ($printers as $printer) {
            app('printer')->storeOrUpdate($printer, $userId, $customerId);
        }
    }

    public function userPrintersAndJobs(PrinterSchema $schema, AnonymousCollectionQuery $collectionQuery) : DataResponse
    {
        $tries = 0;
        $maxTries = config('printing.max_tries_per_request');
        $sleepDuration = config('printing.sleep_duration_after_try');

        do {
            $models = $schema
                ->repository()
                ->queryAll()
                ->withRequest($collectionQuery)
                ->query()
                ->where('user_id', auth()->user()->id)
                ->whereHas('printJobs', function (Builder $query) {
                    $query->where('created_at', '>', now()->subHours(12)->toDateTimeString())
                        ->where(function (Builder $query) {
                            $query->whereNull('status')
                                ->orWhereNotIn('status', ['PRINTED', 'CANCELLED']);
                        });
                });

            if ($collectionQuery->page()) {
                $models = $models->paginate($collectionQuery->page());
            } else {
                $models = $models->get();
            }

            if ($models->count()) {
                break;
            }

            sleep($sleepDuration);
        } while (++$tries < $maxTries);

        return new DataResponse($models);
    }

    public function jobStart(PrintJob $printJob, PrinterJobStartRequest $request)
    {
        app('printer')->setJobStart($printJob, $request);
    }

    public function jobStatus(PrintJob $printJob, PrinterJobStatusRequest $request)
    {
        app('printer')->setJobStatus($printJob, $request);
    }
}
