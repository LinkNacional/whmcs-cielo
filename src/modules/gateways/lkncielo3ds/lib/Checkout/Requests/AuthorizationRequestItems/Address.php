<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequestItems;

/**
 * @since 1.0.0
 */
final class Address
{
    public function __construct(
        public readonly string $street,
        public readonly string $number,
        public readonly string $complement,
        public readonly string $neighborhood,
        public readonly string $zipCode,
        public readonly string $city,
        public readonly string $state,
        public readonly string $country
    ) {
        //
    }
}
