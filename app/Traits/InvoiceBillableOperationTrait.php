<?php

namespace App\Traits;

use App\Models\BillingRate;
use Carbon\Carbon;
use Illuminate\Support\Collection;

trait InvoiceBillableOperationTrait
{
    /**
     * @param array $billingRateInScope
     * @param BillingRate $billingRate
     * @return bool
     */
    public function isBillingRateUpdated(array $billingRateInScope, BillingRate $billingRate): bool
    {
        $calculatedAt = Carbon::parse($billingRateInScope['calculated_at']);
        $updatedAt = Carbon::parse($billingRate->updated_at);
        return $updatedAt->gt($calculatedAt); // if billingRate has updated_at greater
    }

    /**
     * @param array $chargeDocs
     * @param BillingRate $billingRate
     * @return Collection
     */
    public function getChargesByBillingRate(array $chargeDocs, BillingRate $billingRate): Collection
    {
        return $chargeDocs[$billingRate->type]->filter(fn($charge) => $charge->billing_rate['id'] == $billingRate->id);
    }

    /**
     * @param array $billingRates
     * @param array $chargeDocs
     * @param array $chargesToDelete
     * @return void
     */
    public function removeChargesByBillingRates(array $billingRates, array &$chargeDocs, array &$chargesToDelete): void
    {
        foreach ($billingRates as $billingRate) {
            if($chargeDocs[$billingRate->type]->isEmpty()){
                continue;
            }
            //remove charges from memory
            $chargeDocs[$billingRate->type] = $chargeDocs[$billingRate->type]->reject(function ($element) use ($billingRate, &$chargesToDelete) {

                if ($element->getBillingRate()['id'] == $billingRate->id) {
                    $chargesToDelete[] = $element;
                    return true;
                }
                return false;
            });
        }

    }

    /**
     * @param array $array
     * @return array
     */
    public function billableOperationFlattenArray(array $array): array
    {
        $result = [];
        foreach ($array as $value) {
            if (is_array($value) && isset($value[0]) && is_array($value[0])) {
                // If the current element is an array of arrays, flatten it
                $result = array_merge($result, $this->billableOperationFlattenArray($value));
            } else {
                // Otherwise, add the element to the result
                $result[] = $value;
            }
        }
        return $result;
    }

    public function filterEmptyCharges(array $charges): array
    {
        return collect($charges)->filter(function ($collection) {
            return $collection->isNotEmpty();
        })->all();
    }
}
