<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrintJobResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray($request): array
    {
        unset($resource);

        $resource['id'] = $this->id;
        $resource['object_type'] = $this->object_type;
        $resource['object_id'] = $this->object_id;
        $resource['url'] = $this->url;
        $resource['type'] = $this->type;
        $resource['created_at'] = $this->created_at;
        $resource['updated_at'] = $this->updated_at;
        $resource['status'] = $this->status;
        $resource['job_start'] = $this->job_start;
        $resource['job_end'] = $this->job_end;
        $resource['job_id_system'] = $this->job_id_system;
        $resource['printer'] = $this->printer;
        $resource['user'] = new UserResource($this->user);

        return $resource;
    }
}
