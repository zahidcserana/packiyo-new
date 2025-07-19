<?php

use App\Models\Product;

trait HasProductInScope
{
    protected ?Product $product = null;

    public function getProductInScope(): ?Product
    {
        return $this->product;
    }

    public function setProductInScope(?Product $product): void
    {
        $this->product = $product;
    }


}
