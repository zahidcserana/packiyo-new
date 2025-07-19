<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressBookTableResource extends JsonResource
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
        $resource['address_book_name'] = $this->name;
        $resource['name'] = $this->contactInformation->name;
        $resource['address'] = $this->contactInformation->address;
        $resource['address2'] = $this->contactInformation->address2;
        $resource['city'] = $this->contactInformation->city;
        $resource['state'] = $this->contactInformation->state;
        $resource['zip'] = $this->contactInformation->zip;
        $resource['country'] = $this->contactInformation->country->iso_3166_2 ?? null;
        $resource['link_delete'] = ['token' => csrf_token(), 'url' => route('address_book.destroy', ['id' => $this->id, 'address_book' => $this])];

        return $resource;
    }
}
