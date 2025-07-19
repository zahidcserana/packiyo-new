<?php

use App\Models\Customer;
use Behat\Behat\Tester\Exception\PendingException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Helps check that test models belong to the correct customers.
 */
trait ChecksCustomerOwnership
{
    protected static function getStandaloneOr3pl(Model $model, $customerRelName = 'customer'): Customer
    {
        if (!isset($model->$customerRelName)) {
            throw new PendingException('TODO: ' . get_class($model) . ' does not belong to a customer.');
        } elseif (!is_a($model->$customerRelName, Customer::class)) {
            throw new PendingException('TODO: ' . get_class($model) . '::' . $customerRelName . ' is not a customer.');
        }

        if ($model->$customerRelName->isStandalone() || $model->$customerRelName->is3pl()) {
            return $model->$customerRelName;
        } elseif ($model->$customerRelName->is3plChild()) {
            return $model->$customerRelName->parent;
        } else {
            throw new LogicException('The customer is neither standalone, a 3PL, or a 3PL child.');
        }
    }

    protected static function getStandaloneOr3plClients(Model $model, $customerRelName = 'customer'): Collection
    {
        if (!isset($model->$customerRelName)) {
            throw new PendingException('TODO: ' . get_class($model) . ' does not belong to a customer.');
        } elseif (!is_a($model->$customerRelName, Customer::class)) {
            throw new PendingException('TODO: ' . get_class($model) . '::' . $customerRelName . ' is not a customer.');
        }

        if ($model->$customerRelName->isStandalone() || $model->$customerRelName->is3plChild()) {
            return collect([$model->$customerRelName]);
        } elseif ($model->$customerRelName->is3pl()) {
            return $model->$customerRelName->children;
        } else {
            throw new LogicException('The customer is neither standalone, a 3PL, or a 3PL child.');
        }
    }
}
