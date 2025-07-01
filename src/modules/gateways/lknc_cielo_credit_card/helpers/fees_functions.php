<?php

/**
 * @link      https://github.com/LinkNacional/whmcs-cielo-credit-card
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @author    Bruno Ferreira <ferreira.bruno@linknacional.com>
 * @since     2.0.0
 */

require_once __DIR__ . '/gateway_functions.php';

/**
 * Updates the JSON that contains the fees.
 *
 * @return void
 */
function lknc_update_json_fees()
{
    try {
        $json = $_FILES['json'];
        $savePath = __DIR__ . '/../inc/custom_taxes.json';

        $jsonContent = file_get_contents($json['tmp_name']);
        $isValid = json_decode($jsonContent, true) && $json['size'] > 0;

        if ($isValid) {
            if (move_uploaded_file($json['tmp_name'], $savePath)) {
                $response = ['success' => true, 'message' => 'Arquivo atualizado.'];
                $logMessage = 'JSON alterado.';
            } else {
                $response = ['success' => false, 'message' => 'Erro ao enviar arquivo para o servidor. JSON não atualizado.'];
                $logMessage = 'Erro ao enviar arquivo para o servidor. JSON não atualizado.';
            }
        } else {
            $response = ['success' => false, 'message' => 'O arquivo não é valido.'];
            $logMessage = 'Erro ao tentar atualizar JSON. JSON enviado é inválido.';
        }
    } catch (Exception $e) {
        $response = ['success' => false, 'message' => $e];
        $logMessage = 'Erro ao tentar atualizar JSON. JSON enviado é inválido. Erro: ' . $e;
    }

    lkn_cielo_credit_card_log(
        'lknc_cielo_credit_card',
        'Atualizar JSON de taxas',
        'Atualizar JSON de taxas',
        $json,
        [$logMessage]
    );

    header('Content-Type: application/json');
    echo json_encode($response);
}

/**
 * Calculates the fees of a transaction using the JSON that stores the taxes by installment.
 *
 * @param string $gateway
 * @param array  $amount
 * @param array, $installments,
 * @param string $brand     - card brand in CIELO standard.
 * @param string $operation
 *
 * @return float
 */
function lkn_cielo_credit_card_get_fees($gateway, $amount, $installments, $brand, $operation = 'credito')
{
    $fees = lkn_cielo_credit_card_get_json_fees();
    $parsedFees = [];

    // Ensures that the JSON has all brand in their CIELO formats.
    array_walk($fees, function ($content, $brand) use (&$parsedFees) {
        $parsedFees[lkn_cielo_credit_brand_to_cielo($brand)] = $content;
    });

    $originalBrand = $brand;
    $brand = str_replace(' ', '', strtolower($brand));
    $brand = isset($parsedFees[$brand]) ? $brand : 'outras';

    $amount = floatval($amount);

    $installments = intval($installments);

    $feeForInstallment = $parsedFees[$brand]['credito'][$installments] ?? null;

    if ($feeForInstallment === null) {
        lkn_cielo_credit_card_log(
            $gateway,
            'Calcular taxas do pagamento',
            'Calcular taxas do pagamento',
            [
                'Valor' => $amount,
                'Parcelamento' => $installments,
                'Bandeira' => $brand,
                'Bandeira original' => $originalBrand,
                'fees' => $parsedFees
            ],
            ['Sem taxa para a parcela.']
        );

        return 0;
    }

    $feePercantage = floatval($feeForInstallment) / 100;

    if ($installments === 1) {
        $fee = $amount * $feePercantage;
    } else {
        $parcelValue = $amount / $installments;
        $fee = ($parcelValue * $feePercantage) * $installments;
    }

    $fee = floatval(number_format($fee, 2, ".", ""));

    lkn_cielo_credit_card_log(
        $gateway,
        'Calcular taxas do pagamento',
        'Calcular taxas do pagamento',
        [
            'Valor' => $amount,
            'Parcelamento' => $installments,
            'Bandeira' => $brand,
            'Bandeira original' => $originalBrand,
            'fees' => $parsedFees
        ],
        ['Taxa' => "$fee"]
    );

    return $fee;
}
