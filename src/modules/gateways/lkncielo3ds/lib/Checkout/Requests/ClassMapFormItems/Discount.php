<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems;

final class Discount
{
    public function __construct(
        public readonly bool $enableDiscount,
        public readonly ?float $debitDiscountAmount,
        public readonly ?string $debitDiscountPercentage,
        public readonly ?float $debitPaymentAmountWithDiscount,
        public readonly ?float $creditDiscountAmount,
        public readonly ?string $creditDiscountPercentage,
        public readonly ?float $creditPaymentAmountWithDiscount,
    ) {
        //
    }
}
