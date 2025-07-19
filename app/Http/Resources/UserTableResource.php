<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class UserTableResource extends JsonResource
{
    /**
     * Transform the resource collection into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        unset($resource);

        $resource['id'] = $this->id;
        $resource['name'] = $this->contactInformation->name;
        $resource['email'] = $this->email;
        $resource['clients'] = $this->clients();
        $resource['last_login_at'] = $this->last_login_at ? Carbon::parse($this->last_login_at)->format('d.m.Y - H:i') : '';
        $resource['disabled_at'] = $this->disabled_at ? user_date_time($this->disabled_at, true) : null;
        $resource['show_delete_button'] = auth()->user()->isAdmin();
        $resource['show_edit_button'] = auth()->user()->isAdmin();
        $resource['link_edit'] = route('user.edit', ['user' => $this]);
        $resource['link_delete'] = ['token' => csrf_token(), 'url' => route('user.destroy', ['id' => $this->id, 'user' => $this])];

        return $resource;
    }
}
