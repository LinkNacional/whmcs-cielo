<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems;

final class PaymentMethod
{
    public function __construct(
        public readonly string $currency,
        public readonly string $amount,
        public readonly string $installments,
        public readonly string $paymentMethod,
        public readonly string $cardNumber,
        public readonly string $expirationMonth,
        public readonly string $expirationYear
    ) {
        //
    }
}
