<?php

namespace App\Components;

use App\Mail\Export;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CSVComponent extends BaseComponent
{
    private string $filename;

    public function export(
        Request $request,
        $data,
        array $columns,
        string $csvFileName,
        string $exportResourceClass
    ): StreamedResponse {
        $callback = function () use ($data, $exportResourceClass, $columns, $request) {
            $file = fopen('php://output', 'wb');
            fputcsv($file, $columns);

            foreach ($data as $obj) {
                $row = new $exportResourceClass($obj);

                $resource = $row->toArray($request);

                if (Arr::exists($resource, 0) && is_array($resource[0])) {
                    foreach ($resource as $value) {
                        fputcsv($file, $value);
                    }
                } else if ($resource) {
                    fputcsv($file, $resource);
                }
            }

            fclose($file);
        };

        $export = response()->streamDownload($callback, $csvFileName, $this->setHeaders($csvFileName));

        if (request_time() > config('send_exported_file_after_seconds')) {
            try {
                $this->email($request, $data, $columns, $csvFileName, $exportResourceClass);
            } catch (\Throwable $th) {
                report($th);
            }

            if (File::exists($this->filename)) {
                File::delete($this->filename);
            }
        }

        return $export;
    }

    public function unsetCsvHeader(&$data, $condition): array
    {
        $header = array_map('strtolower', $data[0]);

        if (in_array(strtolower($condition), $header, true)) {
            unset($data[0]);

            return $header;
        }

        return [];
    }

    private function setHeaders($csvFilename): array
    {
        return [
            'Content-type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename=file.csv',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
            'X-Export-Filename' => $csvFilename
        ];
    }

    public function getCsvData($inputCsv): array
    {
        $data = [];

        if (($open = fopen($inputCsv, 'rb')) !== false) {
            while (($items = fgetcsv($open, 1000)) !== false) {
                $data[] = $items;
            }

            fclose($open);
        }

        return $data;
    }

    public function email(Request $request, $data, array $columns, string $csvFileName, string $exportResourceClass)
    {
        $this->filename = $this->getFileName($csvFileName, Auth::user()->email, 'exports');

        $file = fopen($this->filename, 'wb');
        fputcsv($file, $columns);

        foreach ($data as $obj) {
            $row = new $exportResourceClass($obj);

            $resource = $row->toArray($request);

            if (Arr::exists($resource, 0) && is_array($resource[0])) {
                foreach ($resource as $value) {
                    fputcsv($file, $value);
                }
            } else if ($resource) {
                fputcsv($file, $resource);
            }
        }

        fclose($file);

        Mail::to(Auth::user()->email)->send(new Export($this->filename));
    }

    public function getFileName($filename, $email, $directory): string
    {
        $exportFilePath = storage_path('app/public/' . $directory);

        if (!File::exists($exportFilePath)) {
            File::makeDirectory($exportFilePath);
        }

        $exportFilePath .= '/' . $email;

        if (!File::exists($exportFilePath)) {
            File::makeDirectory($exportFilePath);
        }

        return $exportFilePath . '/' . $filename;
    }
}
