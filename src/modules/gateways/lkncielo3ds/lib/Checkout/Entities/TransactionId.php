<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Entities;

final class TransactionId
{
    public const CARD_TYPE_CREDIT = 'CREDITO';
    public const CARD_TYPE_DEBIT = 'DEBITO';

    /**
     * @since 1.0.0
     *
     * @param string      $brand
     * @param string      $type
     * @param string      $paymentId
     * @param string|null $installment
     *
     * @return string BRANDxTYPExINSTALLMENTxPAYMENT_ID
     */
    public static function make(
        string $brand,
        string $type,
        string $paymentId,
        ?string $installment = null
    ) {
        $transId = strtoupper($brand) . 'x' . $type;

        if ($type === self::CARD_TYPE_CREDIT) {
            $transId .= "x$installment";
        }

        $transId .= 'x' . $paymentId;

        return $transId;
    }

    /**
     * @since 1.0.0
     *
     * @param string      $brand
     * @param string      $type
     * @param string      $paymentId
     * @param string|null $installment
     *
     * @return string BRANDxTYPExINSTALLMENTxRETURN_CODExPAYMENT_ID
     */
    public static function makeFromError(
        string $brand,
        string $type,
        string $paymentId,
        string $returnCode,
        ?string $installment = null
    ): string {
        $transId = strtoupper($brand) . 'x' . $type;

        if ($type === self::CARD_TYPE_CREDIT) {
            $transId .= "x$installment";
        }

        $transId .= 'xERROx' . $returnCode . 'x' . $paymentId;

        return $transId;
    }

    public static function fromWhmcsTransId(string $transId): array
    {
        $transId = explode('x', $transId);

        $return = [
            'brand' => $transId[0],
            'cardType' => $transId[1],
        ];

        if (count($transId) === 3) {
            $return['paymentId'] = $transId[2];
        } else {
            $return['installments'] = $transId[2];
            $return['paymentId'] = $transId[3];
        }

        return $return;
    }

    public static function makeForRefund(string $paymentId): string
    {
        $suffix = str_replace('x', '', bin2hex(random_bytes(3)));

        return "REEMBOLSOx{$paymentId}x" . $suffix;
    }
}
