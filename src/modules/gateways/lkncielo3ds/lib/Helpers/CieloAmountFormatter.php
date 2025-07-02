<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Helpers;

/**
 * @since 1.0.0
 */
final class CieloAmountFormatter
{
    /**
     * @since 1.0.0
     *
     * @param string|float $amount
     *
     * @return int
     */
    public static function toCieloFormat(float $amount): int
    {
        return number_format($amount, 2, '', '');
    }

    /**
     * @since 1.0.0
     *
     * @param int $cieloAmount
     *
     * @return float
     */
    public static function fromCieloAmount(int $cieloAmount): float
    {
        $invoiceAmountCents = substr($cieloAmount, -2);
        $invoiceAmountReal = substr_replace($cieloAmount, '', -2);
        $invoiceAmountFormatted = $invoiceAmountReal . '.' . $invoiceAmountCents;
        $formattedAmount = number_format($invoiceAmountFormatted, 2, '.', '');

        return $formattedAmount;
    }
}
