<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems;

final class CartItem
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly string $sku,
        public readonly int $quantity,
        public readonly ?int $unitaryPrice
    ) {
        //
    }
}
