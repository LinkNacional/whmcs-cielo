<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Helpers;

final class Discount
{
    /**
     * Calculates the discount for the current transaction.
     *
     * @since 1.1.0
     *
     * @param string $cardType
     * @param float  $paymentAmount
     *
     * @return array Array return is [
     *               paymentAmountWithDiscount => 0.00
     *               discountAmount => 0.00
     *               discountPercentage => '0,00'
     *               ].
     *               An empty array is returned when there is no discount.
     */
    public static function calculateDiscount(
        string $cardType,
        float $paymentAmount
    ): array {
        $discountPercentage = $cardType === 'debit' ? Config::setting('debit_discount') : Config::setting('credit_discount');

        if ($discountPercentage > 0.0) {
            $paymentAmountWithDiscount = round($paymentAmount - ($paymentAmount * $discountPercentage), 2);
            $discountAmount = round($paymentAmount - $paymentAmountWithDiscount, 2);
            $discountPercentage = number_format($discountPercentage * 100, 2, ',', '.');

            return [
                'paymentAmountWithDiscount' => $paymentAmountWithDiscount,
                'discountAmount' => $discountAmount,
                'discountPercentage' => $discountPercentage
            ];
        }

        return [];
    }
}
