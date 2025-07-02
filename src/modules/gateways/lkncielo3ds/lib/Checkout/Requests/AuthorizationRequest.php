<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests;

use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequestItems\Address;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequestItems\CreditCard;

/**
 * @since 1.0.0
 */
final class AuthorizationRequest
{
    public readonly string $baseUrl;
    public readonly string $merchantId;
    public readonly string $merchantKe;

    /**
     * @since 1.0.0
     *
     * @param int                                                                                      $clientId                    safe client ID of the current signed in client.              The id of the currently signed in client.
     * @param int                                                                                      $invoiceId
     * @param string                                                                                   $cavv
     * @param string                                                                                   $eci
     * @param string                                                                                   $version
     * @param string                                                                                   $referenceId
     * @param string                                                                                   $merchantOrderId
     * @param string                                                                                   $clientName
     * @param string                                                                                   $customerEmail
     * @param string                                                                                   $currency
     * @param int                                                                                      $installments
     * @param bool                                                                                     $capture
     * @param bool                                                                                     $authenticate
     * @param string                                                                                   $softDescriptor
     * @param float                                                                                    $amount
     * @param bool                                                                                     $clientEnabledPartialPayment
     * @param \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequestItems\Address    $address
     * @param \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequestItems\CreditCard $card
     */
    public function __construct(
        public readonly int $clientId,
        public readonly int $invoiceId,
        public readonly string $cavv,
        public readonly string $eci,
        public readonly string $version,
        public readonly string $referenceId,
        public readonly string $merchantOrderId,
        public readonly ?string $clientName,
        public readonly ?string $customerEmail,
        public readonly string $currency,
        public readonly int $installments,
        public readonly bool $capture,
        public readonly bool $authenticate,
        public readonly string $softDescriptor,
        public readonly float $amount,
        public readonly bool $clientEnabledPartialPayment,
        public readonly ?Address $address,
        public readonly CreditCard $card
    ) {
        //
    }

    public function setApiCredentials(
        string $baseUrl,
        string $merchantId,
        string $merchantKe
    ): void {
        $this->baseUrl = $baseUrl;
        $this->merchantId = $merchantId;
        $this->merchantKe = $merchantKe;
    }
}
