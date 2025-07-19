<?php

namespace App\Models\Scopes;

use App\Models\Automation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PublicApiOrderChannelScope implements Scope
{

    /**
     * @param Builder $builder
     * @param Model $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $user = request()->user();

        // Should not be applied for Co-Pilot user
        if ($user->email != Automation::AUTOMATION_USER_EMAIL) {
            $accessToken = request()->user()->currentAccessToken();

            if ($accessToken && $accessToken->order_channel_id) {
                $builder->whereHas(
                    'orderChannel',
                    fn (Builder $builder) => $builder->where('order_channels.id', $accessToken->order_channel_id)
                );
            }
        }
    }
}
