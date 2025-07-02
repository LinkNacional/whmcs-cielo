<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems;

final class User
{
    public function __construct(
        public readonly bool $accountGuest,
        public readonly string $createdDate,
        public readonly string $changedDate,
        public readonly string $passwordChangedDate,
        public readonly string $authenticationMethod,
        public readonly string $authenticationProtocol,
        public readonly string $authenticationTimestamp
    ) {
        //
    }
}
