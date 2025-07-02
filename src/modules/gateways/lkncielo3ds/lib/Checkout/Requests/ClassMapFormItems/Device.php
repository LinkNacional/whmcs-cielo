<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems;

final class Device
{
    public function __construct(
        public readonly string $ipAddress,
    ) {
        //
    }
}
