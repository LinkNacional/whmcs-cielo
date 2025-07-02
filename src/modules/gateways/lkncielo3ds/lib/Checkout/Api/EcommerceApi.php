<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Api;

use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Logger;
use stdClass;

/**
 * Holds methods to send requests to the E-commerce API.
 *
 * @since 1.0.0
 */
final class EcommerceApi extends AbstractHttpClient
{
    public function __construct(
        public readonly string $apiTransUrl,
        public readonly string $apiQueryUrl,
        public readonly string $merchantId,
        public readonly string $merchantKey
    ) {
        //
    }

    /**
     * Abstracts the logic required to send requests to the Cielo E-commerce API.
     *
     * @since 1.0.0
     *
     * @param string $method
     * @param string $baseUrl
     * @param string $endpont
     * @param array  $body
     * @param array  $header
     *
     * @return stdClass|array
     */
    private function request(
        string $method,
        string $baseUrl,
        string $endpont,
        array $body = [],
        array $header = []
    ): stdClass|array|null {
        $header = array_merge(
            $header,
            [
                "MerchantId: {$this->merchantId}",
                "MerchantKey: {$this->merchantKey}",
            ]
        );

        return $this->httpRequest($method, $baseUrl, $endpont, $body, $header);
    }

    /**
     * @since 1.0.0
     * @see https://developercielo.github.io/manual/'?shell#cart%C3%B5es-de-cr%C3%A9dito-e-d%C3%A9bito
     *
     * @param array $body
     *
     * @return void
     */
    public function requestPayment(array $body): stdClass|array|null
    {
        $response = $this->request('POST', $this->apiTransUrl, '1/sales', $body);

        Logger::log(
            'Solicitar nova autorização',
            ['body' => $body],
            $response
        );

        return $response;
    }

    /**
     * @since 1.0.0
     * @see https://developercielo.github.io/manual/'?shell#consulta-bin
     *
     * @param string $cardNumber
     *
     * @return stdClass
     */
    public function requestBin(string $cardNumber): stdClass|null
    {
        $requestUrl = "1/cardBin/$cardNumber";

        $response = $this->request('GET', $this->apiQueryUrl, $requestUrl);

        Logger::log(
            'Consulta BIN',
            [
                'cardNumber' => $cardNumber
            ],
            $response
        );

        return $response;
    }

    /**
     * @since 1.0.0
     * @see https://developercielo.github.io/manual/cielo-ecommerce#cancelamento
     *
     * @param array $body
     *
     * @return stdClass|array
     */
    public function requestRefund(
        string $paymentId,
        int $amount,
        bool $isTotalRefund
    ): stdClass|array|null {
        $url = "1/sales/$paymentId/void" . ($isTotalRefund ? '' : "?amount=$amount");

        $response = $this->request(
            'PUT',
            $this->apiTransUrl,
            $url
        );

        Logger::log(
            'Solicitar reembolso',
            [
                'request' => [
                    'url' => $url,
                    'paymentId' => $paymentId,
                    'amount' => $amount,
                    'isTotalRefund' => $isTotalRefund
                ]
            ],
            $response
        );

        return $response;
    }
}
