<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Product;
use App\Models\Image;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Arr;

class ProductPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view the product.
     *
     * @param User $user
     * @param Product $product
     * @return mixed
     */
    public function view(User $user, Product $product)
    {
        return $user->isAdmin() || $user->canAccessCustomer($product->customer_id);
    }
    /**
     * Determine whether the user can view.
     *
     * @param  User  $user
     * @return bool
     */
    public function viewAny(User $user)
    {
        return true;
    }

    /**
     * Determine whether the user can create products.
     *
     * @param User $user
     * @param null $data
     * @return mixed
     */
    public function create(User $user, $data = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $customerId = Arr::get($data, 'customer_id');

        if (!$customerId) {
            $customerId = request()->input('customer_id');
        }

        if (!$customerId) {
            $customerId = request()->input('data.relationships.customer.data.id');
        }

        return $user->canAccessCustomer($customerId);
    }

    public function batchStore(User $user)
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $data) {
            if (!$this->create($user, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can update the product.
     *
     * @param User $user
     * @param Product $product
     * @return mixed
     */
    public function update(User $user, Product $product)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($product) {
            return $user->canAccessCustomer($product->customer_id);
        }

        return true;
    }

    public function batchUpdate(User $user)
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $data) {
            if (!$this->update($user, $data)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether the user can delete the product.
     *
     * @param User $user
     * @param  $data
     * @return mixed
     */
    public function delete(User $user, $data = null)
    {
        if ($user->isAdmin()) {
            return true;
        }

        $data = $data ?: app('request')->input();

        if (isset($data['sku']) && $product = Product::where('sku', $data['sku'])->first()) {
            return $user->canAccessCustomer($product->customer_id);
        }

        return true;
    }

    public function batchDelete(User $user)
    {
        $dataArr = app('request')->input();

        foreach ($dataArr as $data) {
            if (!$this->delete($user, $data)) {
                return false;
            }
        }

        return true;
    }

    public function history(User $user, Product $product)
    {
        if ($user->isAdmin()) {
            return true;
        }

        if (!$user->canAccessCustomer($product->customer_id)) {
            return false;
        }

        return true;
    }

    public function changeLocationQuantity(User $user, Product $product)
    {
        return $user->isAdmin() || app('user')->getCustomers()->contains('id', $product->customer_id);
    }

    public function changeLocationLot(User $user, Product $product)
    {
        return $this->view($user, $product);
    }

    public function deleteImage(User $user, Image $image)
    {
        return $user->isAdmin() || $user->canAccessCustomer($image->object->customer_id);
    }

    public function viewCustomer(User $user, Product $product)
    {
        return $this->view($user, $product);
    }

    public function viewLocations(User $user, Product $product)
    {
        return $this->view($user, $product);
    }

    public function viewBarcodes(User $user, Product $product)
    {
        return $this->view($user, $product);
    }
}
