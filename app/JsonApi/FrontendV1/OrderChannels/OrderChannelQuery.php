<?php

namespace App\JsonApi\FrontendV1\OrderChannels;

use http\Client\Request;
use LaravelJsonApi\Laravel\Http\Requests\ResourceQuery;

class OrderChannelQuery extends ResourceQuery
{

    public function available(Request $request): static
    {
        return $this->where('is_available', true);
    }

}
