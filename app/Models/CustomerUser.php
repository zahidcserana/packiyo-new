<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Carbon;

/**
 * App\Models\CustomerUser
 *
 * @property int $id
 * @property int $customer_id
 * @property int $user_id
 * @property int $role_id
 * @property int|null $warehouse_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read CustomerUserRole $role
 * @property-read Warehouse|null $warehouse
 * @method static Builder|CustomerUser newModelQuery()
 * @method static Builder|CustomerUser newQuery()
 * @method static Builder|CustomerUser query()
 * @method static Builder|CustomerUser whereCreatedAt($value)
 * @method static Builder|CustomerUser whereCustomerId($value)
 * @method static Builder|CustomerUser whereId($value)
 * @method static Builder|CustomerUser whereRoleId($value)
 * @method static Builder|CustomerUser whereUpdatedAt($value)
 * @method static Builder|CustomerUser whereUserId($value)
 * @method static Builder|CustomerUser whereWarehouseId($value)
 * @mixin \Eloquent
 */
class CustomerUser extends Pivot
{
    /**
     * @return BelongsTo
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(CustomerUserRole::class);
    }

    /**
     * @return BelongsTo
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
