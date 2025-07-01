<?php

require_once __DIR__ . '/gateway_functions.php';

/**
 * @param string $expiration (m/y).
 *
 * @return bool
 */
function lknc_validate_expiration($expiration)
{
    $currentYear = intval(date('Y'));
    $expExplode = explode('/', $expiration);

    $month = $expExplode[0];
    $year = $expExplode[1];

    $return = ($month >= 1 && $month <= 12) && ($year >= $currentYear && $year <= $currentYear + 20);

    if (!$return) {
        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Validar expiração do cartão',
            'failure',
            ['Expiração do cartão' => $expiration],
            ['Expiração do cartão inválida']
        );
    }

    return $return;
}

/**
 * @param int $cvv
 *
 * @return bool
 */
function lknc_validate_cvv($cvv)
{
    $return = preg_match('/^[0-9]{3,4}$/', $cvv);

    if (!$return) {
        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Validar CVV do cartão',
            'failure',
            ['CVV do cartão' => $cvv],
            ['CVV inválido']
        );
    }

    return $return;
}

/**
 * @param string $holderName
 *
 * @return bool
 */
function lknc_validate_holder_name($holderName)
{
    $return = !empty($holderName) && strlen($holderName) >= 5;

    if (!$return) {
        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Validar nome do titular do cartão',
            'failure',
            ['Titular do cartão' => $holderName],
            ['Nome do titular inválido']
        );
    }

    return $return;
}

/**
 * @param int   $installment
 * @param float $paymentAmount
 *
 * @return bool
 */
function lknc_validate_installment($installment, $paymentAmount)
{
    $minimumInstallmentAmount = lkn_cielo_credit_card_get_config('minimumInstallmentAmount');

    $parcelValue = round($paymentAmount / $installment, 2);

    $return =
        $installment >= 1 && $installment <= 12;

    if (!$return) {
        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Validar parcelamento',
            'failure',
            ['Parcelamnto' => "$installment", 'Valor do pagemento' => $paymentAmount],
            ['Parcelamento inválido']
        );
    }

    return $return;
}

/**
 * @param int   $invoiceId
 * @param float $paymentAmount
 *
 * @return bool
 */
function lknc_validate_invoice($invoiceId, $paymentAmount)
{
    $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);

    $return = isset($invoice['result']) &&
            $invoice['result'] === 'success' &&
            $paymentAmount <= $invoice['balance'];

    if (!$return) {
        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Validar fatura do pagamento',
            'failure',
            ['ID da fatura' => "$invoiceId", 'Valor do pagemento' => $paymentAmount],
            ['Valor do pagamento inválido.']
        );
    }

    return $return;
}

/**
 * @param int $invoiceId
 *
 * @return array
 */
function lknc_get_invoice($invoiceId)
{
    $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);

    return [
        'id' => $invoice['id'],
        'amount' => $invoice['total'],
        'balance' => $invoice['balance']
    ];
}

/**
 * @param float $paymentAmount
 * @param float $invoiceAmount
 *
 * @return bool
 */
function lknc_validate_payment_amount($paymentAmount, $invoiceBalance)
{
    $return = $paymentAmount >= 0 && $paymentAmount <= $invoiceBalance;

    if (!$return) {
        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Validar valor do pagamento',
            'Validar valor do pagamento',
            [
                'Valor do pagamento' => $paymentAmount,
                'Balanço da fatura' => $invoiceBalance
            ],
            ['Valor do pagamento inválido.']
        );
    }

    return $return;
}

/**
 * @param int    $number
 * @param int    $cvv
 * @param string $expirationDate
 * @param string $brand
 * @param string $holderName
 *
 * @return bool
 */
function lknc_validate_card_number($number)
{
    $return = preg_match('/[^0-9]/', $number) === 0;

    if (!$return) {
        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Validar número do cartão',
            'Validar número do cartão',
            ['Número do cartão' => $number],
            ['Número do cartão inválido.']
        );
    }

    return $return;
}

/**
 * @param string $brand
 *
 * @return bool
 */
function lknc_validate_card_token($token)
{
    $preg = '/[^a-zA-Z0-9-]/'; // matches any non-letter or non-number or non hyphen (-).
    $return = strlen($token) === 36 && preg_match($preg, $token) === 0;

    if (!$return) {
        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Validar token do cartão',
            'Validar token do cartão',
            ['Token' => $token],
            ['Token do cartão de inválido']
        );
    }

    return $return;
}

/**
 * @param string $brand
 *
 * @return bool
 */
function lknc_validate_card_brand($brand)
{
    $preg = '/[^a-zA-Z ]/'; // matches any non-letter or non-space.
    $return = preg_match($preg, $brand) === 0 && strlen($brand) > 0;

    if (!$return) {
        lkn_cielo_credit_card_log(
            'lknc_cielo_credit_card',
            'Validar bandeira do cartão',
            'Validar bandeira do cartão',
            ['Bandeira do cartão' => $brand],
            ['Bandeira do cartão inválida']
        );
    }

    return $return;
}
