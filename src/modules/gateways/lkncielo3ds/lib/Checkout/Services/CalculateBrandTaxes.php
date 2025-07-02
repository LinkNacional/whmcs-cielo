<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Services;

use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Config;

/**
 * @since 1.0.0
 */
final class CalculateBrandTaxes
{
    /**
    * @since 1.1.0
    *
     * @param string $method       "debit" or "credit"
     * @param string $brand        the full name of the card brand.
     * @param int    $installments
     * @param float  $paymentValue
    */
    public function __construct(
        private readonly string $method,
        private readonly string $brand,
        private readonly int $installments,
        private readonly float $paymentValue
    ) {
        //
    }

    public function run()
    {
        if (!Config::setting('calculate_brand_taxes')) {
            return ['success' => true, 'fees' => 0.0];
        }

        $taxesFile = $this->getTaxesFile();

        if (empty($taxesFile)) {
            return ['success' => false, 'error' => 'invalid-json-taxes'];
        }

        $reducedBrandName = $this->getReducedBrandName();

        $brandTaxes = $taxesFile[$reducedBrandName] ?? $taxesFile['outras'];

        if (!$brandTaxes) {
            return ['success' => true, 'fees' => 0.0];
        }

        $fee = 0.0;

        if ($this->method === 'credit') {
            $tax = $brandTaxes['credito'][$this->installments];
            $taxPercentage = $tax / 100;

            if ($this->installments === 1) {
                $fee = $this->paymentValue * $taxPercentage;
            } else {
                $parcelValue = $this->paymentValue / $this->installments;
                $fee = ($parcelValue * $taxPercentage) * $this->installments;
            }
        } else {
            $tax = $brandTaxes['debito'];
            $taxPercentage = $tax / 100;

            $fee = $this->paymentValue * $taxPercentage;
        }

        return ['success' => true, 'fees' => round($fee, 2)];
    }

    private function getTaxesFile(): array
    {
        $customBrandTaxesJsonPath = __DIR__ . '/../../resources/json/custom_brand_taxes.json';
        $defaultBrandTaxesJsonPath = __DIR__ . '/../../resources/json/default_brand_taxes.json';

        if (file_exists($customBrandTaxesJsonPath)) {
            return json_decode(file_get_contents($customBrandTaxesJsonPath), true);
        } elseif (file_exists($defaultBrandTaxesJsonPath)) {
            return json_decode(file_get_contents($defaultBrandTaxesJsonPath), true);
        } else {
            return [];
        }
    }

    private function getReducedBrandName(): string
    {
        $internalBrand = str_replace(' ', '', strtolower($this->brand));

        switch ($internalBrand) {
            case 'americanexpress':
                return 'amex';
            case 'mastercard':
                return 'master';
            case 'dinersclub':
                return 'diners';
            default:
                return strtolower($this->brand);
        }
    }
}
