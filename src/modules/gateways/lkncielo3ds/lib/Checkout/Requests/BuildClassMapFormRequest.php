<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests;

use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Address;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Device;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Discount;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Order;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\User;

/**
 * @since 1.0.0
 */
final class BuildClassMapFormRequest
{
    /**
     * @since 1.0.0
     *
     * @param string                                                                           $authEnabled
     * @param string                                                                           $authEnabledNotifyOnly
     * @param string                                                                           $authSupressChallange
     * @param string                                                                           $accessToken
     * @param string                                                                           $orderNumber
     * @param bool                                                                             $sendClientAddressDetailsTo3ds
     * @param bool                                                                             $enablePartialPayment
     * @param float                                                                            $partialPaymentMinAmount
     * @param \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Discount   $discount
     * @param \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Address    $address
     * @param \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Device     $device
     * @param \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Order      $order
     * @param \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\User       $user
     * @param \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\CartItem[] $cartItem
     */
    public function __construct(
        public readonly string $env,
        public readonly string $minInstallmentValue,
        public readonly string $authEnabled,
        public readonly string $authEnabledNotifyOnly,
        public readonly string $authSupressChallange,
        public readonly string $accessToken,
        public readonly string $orderNumber,
        public readonly float $paymentAmount,
        public readonly int $cieloPaymentAmountFormat,
        public readonly bool $sendClientAddressDetailsTo3ds,
        public readonly bool $enablePartialPayment,
        public readonly float $partialPaymentMinAmount,
        public readonly Discount $discount,
        public readonly Address $address,
        public readonly Device $device,
        public readonly Order $order,
        public readonly User $user,
        public readonly array $cartItems
    ) {
        //
    }
}
