<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Services;

use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Api\EcommerceApi;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Config;

/**
 * @since 1.0.0
 */
final class CardBinService
{
    /**
     * @since 1.0.0
     * @var \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Api\EcommerceApi
     */
    private readonly EcommerceApi $ecommerceApi;

    /**
     * @since 1.0.0
     * @see https://github.com/LinkNacional/whmcs-cielo-credit-card/blob/main/src/modules/gateways/lknc_cielo_credit_card/helpers/card_functions.php#L311
     *
     * @var array
     */
    private array $brandsRegex = [
        'Elo' => '/(4011|431274|438935|451416|457393|4576|457631|457632|504175|627780|636297|636368|636369|(6503[1-3])|(6500(3[5-9]|4[0-9]|5[0-1]))|(6504(0[5-9]|1[0-9]|2[0-9]|3[0-9]))|(650(48[5-9]|49[0-9]|50[0-9]|51[1-9]|52[0-9]|53[0-7]))|(6505(4[0-9]|5[0-9]|6[0-9]|7[0-9]|8[0-9]|9[0-8]))|(6507(0[0-9]|1[0-8]))|(6507(2[0-7]))|(650(90[1-9]|91[0-9]|920))|(6516(5[2-9]|6[0-9]|7[0-9]))|(6550(0[0-9]|1[1-9]))|(6550(2[1-9]|3[0-9]|4[0-9]|5[0-8]))|(506(699|77[0-8]|7[1-6][0-9))|(509([0-9][0-9][0-9])))/',
        'Hipercard' => '/^(606282\d{10}(\d{3})?)|(3841\d{15})$/',
        'Diners' => '/^3(?:0[0-5]|[68][0-9])[0-9]{11}$/',
        'Discover' => '/^6(?:011|5[0-9]{2})[0-9]{12}$/',
        'JCB' => '/^(?:2131|1800|35\d{3})\d{11}$/',
        'Aura' => '/^50[0-9]{14,17}$/',
        'Amex' => '/^3[47][0-9]{13}$/',
        'Master' => '/^5[1-5]\d{14}$|^2(?:2(?:2[1-9]|[3-9]\d)|[3-6]\d\d|7(?:[01]\d|20))\d{12}$/',
        'Visa' => '/^4[0-9]{12}(?:[0-9]{3})?$/'
    ];

    /**
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->ecommerceApi = new EcommerceApi(
            Config::env('apiTransUrl'),
            Config::env('apiQueryUrl'),
            Config::setting('api_merchant_id'),
            Config::setting('api_merchant_key')
        );
    }

    /**
     * Tries to get the brand from the Cielo API. If unsuccessful, tries a
     * offline approach with regex.
     *
     * @since 1.0.0
     *
     * @param string $cardNumber
     *
     * @return array|null
     */
    public function run(string $cardNumber): ?array
    {
        $response = $this->ecommerceApi->requestBin($cardNumber);

        if ($response->Status === '00') {
            $brand = match ($response->Provider) {
                'MASTERCARD' => 'Master',
                default => ucfirst(strtolower($response->Provider))
            };

            return [
                'success' => true,
                'brand' => $brand,
                'type' => $response->CardType
            ];
        } elseif (in_array($response->Status, ['01', '02'], true)) {
            $success = $this->offlineFallback($cardNumber) !== null;

            return [
                'success' => $success,
                'brand' => $this->offlineFallback($cardNumber)
            ];
        }

        return ['success' => false];
    }

    /**
     * Tries to figure out the card brand with regex.
     *
     * @since 1.0.0
     *
     * @param string $cardNumber
     *
     * @return string|null
     */
    private function offlineFallback(string $cardNumber): ?string
    {
        foreach ($this->brandsRegex as $brand => $regex) {
            if (preg_match($regex, $cardNumber)) {
                return $brand;
            }
        }

        return null;
    }
}
