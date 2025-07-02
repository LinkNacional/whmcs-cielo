<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests;

/**
 * @since 1.0.0
 */
final class RefundRequest
{
    /**
     * @since 1.0.0
     *
     * @param string $paymentId
     * @param int    $amount
     */
    public function __construct(
        public readonly string $invoiceId,
        public readonly string $paymentId,
        public readonly int $amount
    ) {
        //
    }
}
