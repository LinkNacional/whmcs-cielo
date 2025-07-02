<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Services;

use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Config;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Invoice;

/**
 * This class takes the payment amount given by the client and checks if this
 * payment matches the invoice balance minus discounts.
 *
 * Starting from version 1.1.0, discounts are only applied on fully invoice payments.
 *
 * @since 1.1.0
 */
final class CalculateDiscountForInvoice
{
    public readonly float $invoiceBalanceMinusDiscount;
    public readonly float $discountAmount;

    /**
     * Human friendly representation of percentage. e.g: 5,00%.
     *
     * @since 1.0.0
     * @var string
     */
    public readonly string $discountPercentage;

    public function __construct(
        private readonly int $invoiceId,
        private readonly float $clientPaymentAmount,
        private readonly string $cardType
    ) {
        $invoice = Invoice::get($this->invoiceId);
        $invoiceBalance = (float) ($invoice['balance']);

        $discountPercentage = $this->cardType === 'debit' ? Config::setting('debit_discount') : Config::setting('credit_discount');

        $this->invoiceBalanceMinusDiscount = round($invoiceBalance - ($invoiceBalance * $discountPercentage), 2);
        $this->discountAmount = round($invoiceBalance - $this->invoiceBalanceMinusDiscount, 2);
        $this->discountPercentage = number_format($discountPercentage * 100, 2, ',', '.');
    }

    public function canInvoiceReceiveDiscount()
    {
        $discountPercentage = $this->cardType === 'debit' ? Config::setting('debit_discount') : Config::setting('credit_discount');

        if ($discountPercentage === 0.0) {
            return false;
        }

        $invoice = Invoice::get($this->invoiceId);

        $invoiceTotal = (float) ($invoice['total']);
        $invoiceBalance = (float) ($invoice['balance']);

        if ($invoiceBalance !== $invoiceTotal) {
            return false;
        }

        return true;
    }
}
