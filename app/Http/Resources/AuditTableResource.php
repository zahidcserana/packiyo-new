<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditTableResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        unset($resource);

        $resource['id'] = $this->id;
        $resource['created_at'] = user_date_time($this->created_at, true);
        $resource['user'] = $this->user->contactInformation->name ?? '';
        $resource['object'] = $this->object_name;
        $resource['event'] = $this->event;
        $resource['message'] = $this->message();

        return $resource;
    }

    private function message()
    {
        return $this->ranByAutomation
            ? "Rule {$this->ranByAutomation->name}: $this->custom_message"
            : $this->custom_message;
    }
}
