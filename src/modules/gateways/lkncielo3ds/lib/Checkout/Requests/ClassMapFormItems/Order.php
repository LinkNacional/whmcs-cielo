<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems;

final class Order
{
    public function __construct(
        public readonly string $transactionMode,
        public readonly string $merchantUrl,
        public readonly string $recurrence,
        public readonly string $productCode,
        public readonly string $last24HourCount,
        public readonly string $last6MonthCount,
        public readonly string $lastYearCount,
        public readonly string $cardAttemptsOnLast24Hours,
        public readonly string $marketingOptin,
        public readonly string $marketingSource
    ) {
        //
    }
}
