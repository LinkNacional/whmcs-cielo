<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Services;

use WHMCS\Database\Capsule;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Api\EcommerceApi;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Entities\TransactionId;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\RefundRequest;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Config;

final class RefundService
{
    /**
     * @since 1.0.0
     * @var \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Api\EcommerceApi
     */
    private readonly EcommerceApi $ecommerceApi;

    /**
     * @since 1.0.0
     */
    public function __construct(
        string $apiMerchantId,
        string $apiMerchantKey
    ) {
        $this->ecommerceApi = new EcommerceApi(
            Config::env('apiTransUrl'),
            Config::env('apiQueryUrl'),
            $apiMerchantId,
            $apiMerchantKey
        );
    }

    /**
     * @since 1.0.0
     *
     * @param \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\RefundRequest $request
     *
     * @return array
     */
    public function run(RefundRequest $request): array
    {
        $invoiceAmount = Capsule::table('tblinvoices')
            ->where('id', $request->invoiceId)
            ->first('total')
            ->total;

        $isFullRefund = $request->amount === str_replace('.', '', $invoiceAmount);

        $response = $this->ecommerceApi->requestRefund(
            $request->paymentId,
            $request->amount,
            $isFullRefund
        );

        if (!isset($response->Status)) {
            return [
                'success' => false,
                'data' => [
                    'status' => 'error',
                    'rawdata' => $response
                ]
            ];
        }

        if (!in_array($response->Status, [2, 10, 11], true)) {
            return [
                'success' => false,
                'data' => [
                    'status' => 'declined',
                    'rawdata' => $response
                ]
            ];
        }

        $whmcsTransId = TransactionId::makeForRefund($request->paymentId);

        return [
            'success' => true,
            'data' => [
                'status' => 'success',
                'transid' => $whmcsTransId,
                'fees' => 0.0,
                'rawdata' => $response
            ]
        ];
    }
}
