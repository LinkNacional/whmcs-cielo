<?php
/**
 * @link      https://github.com/LinkNacional/whmcs-cielo-qrcode
 * @link      https://developers.whmcs.com/payment-gateways/third-party-gateway/
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @author    Bruno Ferreira <ferreira.bruno@linknacional.com>
 * @since     1.0.0
 */

use Smarty;

/**
 * @return string
 */
function lkn_cielo_qr_code_gateway_name() {
    return 'lkn_cielo_qr_code';
}

/**
 * @param  null|string $config
 *
 * @return string|array
 */
function lkn_cielo_qr_code_get_config($config = null) {
    $gatewayParams = getGatewayVariables(lkn_cielo_qr_code_gateway_name());

    if ($gatewayParams) {
        return $config
            ? $gatewayParams[$config] ?? null
            : $gatewayParams;
    } else {
        return null;
    }
}

/**
 * @param  string $cieloResource CIELO resource without / at start.
 * @param  array $requestBody
 * @param  string $url "api" for CIELO API URL or "query" for CIELO QUERY URL. If false, the URL will be $cieloResource.
 * @param  string $method HTTP method, default is "POST".
 *
 * @return \CurlHandle|false
 */
function lkn_cielo_qr_code_make_cielo_request($cieloResource, $requestBody, $url = 'api', $method = 'POST') {
    if ($url !== false) {
        $cieloUrl = $url === 'query'
            ? lkn_cielo_qr_code_cielo_api_query_url()
            : lkn_cielo_qr_code_cielo_api_url();
        $requestUrl = "$cieloUrl/$cieloResource";
    } else {
        $requestUrl = $cieloResource;
    }

    $request = curl_init();

    curl_setopt_array($request, [
        CURLOPT_URL => $requestUrl,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => lkn_cielo_qr_code_cielo_request_headers(),
        CURLOPT_RETURNTRANSFER => true,
    ]);

    if ($requestBody !== []) {
        curl_setopt($request, CURLOPT_POSTFIELDS, json_encode($requestBody));
    }

    return $request;
}

/**
 * @return string
 */
function lkn_cielo_qr_code_cielo_api_url() {
    return lkn_cielo_qr_code_get_config('enableTestMode') === 'on'
        ? 'https://apisandbox.cieloecommerce.cielo.com.br'
        : 'https://api.cieloecommerce.cielo.com.br';
}

/**
 * @return string
 */
function lkn_cielo_qr_code_cielo_api_query_url() {
    return lkn_cielo_qr_code_get_config('enableTestMode') === 'on'
        ? 'https://apiquerysandbox.cieloecommerce.cielo.com.br'
        : 'https://apiquery.cieloecommerce.cielo.com.br';
}

/**
 * @return array
 */
function lkn_cielo_qr_code_cielo_request_headers() {
    $gatewayParams = lkn_cielo_qr_code_get_config();

    return [
        'Content-Type: application/json',
        'MerchantId:' . $gatewayParams['merchantId'],
        'MerchantKey:' . $gatewayParams['merchantKey'],
    ];
}

/**
 * @param  string $value
 *
 * @return string
 */
function lkn_cielo_qr_code_format_amount_to_cielo_pattern($value) {
    $formatted = number_format(floatval($value), 2, '.', '');
    $formatted = str_replace('.', '', $formatted);

    return $formatted;
}

/**
 * Formats CIELO paid amount pattern into WHMCS pattern.
 *
 * @param int $cieloPaidAmount the payment amount returned by CIELO.
 *
 * @return float|int
 */
function lkn_cielo_qr_code_format_amount_from_cielo_to_whmcs($cieloPaidAmount) {
    $invoiceAmountCents = substr($cieloPaidAmount, -2);
    $invoiceAmountReal = substr_replace($cieloPaidAmount, '', -2);
    $invoiceAmountFormatted = $invoiceAmountReal . '.' . $invoiceAmountCents;
    $formattedAmount = number_format($invoiceAmountFormatted, 2, '.', '');

    return $formattedAmount;
}

/**
 * @param  string $template template filename without ".tpl" at ending. May also have the subfolder like "'components'/qrcode".
 * @param  array $data
 *
 * @return void
 */
function lkn_cielo_qr_code_render_template($template, $data = []) {
    $smarty = new Smarty();

    $path = __DIR__ . '/../templates/';
    $smarty->setTemplateDir($path);
    $smarty->assign($data);

    return $smarty->fetch($template . '.tpl');
}

/**
 * @param  string $paymentId CIELO's payment ID.
 * @param  int $clientId
 * @param  string $invoiceId
 *
 * @return bool
 */
function lkn_cielo_qr_code_register_qr_code_generation($paymentId, $clientId, $invoiceId) {
    $requestBody = [
        'paymentmethod' => lkn_cielo_qr_code_gateway_name(),
        'invoiceid' => $invoiceId,
        'userid' => $clientId,
        'transid' => 'QRCODE.' . $paymentId,
        'date' => date('d/m/Y'),
        'description' => 'Boleto gerado',
        'amountin' => 0,
        'fees' => 0,
    ];

    $response = localAPI('AddTransaction', $requestBody);

    return $response['result'] === 'success';
}

/**
 * Checks if the gateway log is enabled. If so, log the paramters.
 *
 * @param  string $functionName
 * @param  int $fileLine
 * @param  array $request
 * @param  array|string $response
 * @param  string $processedData
 * @param  string $replaceVars
 *
 * @return void
 */
function lkn_cielo_qr_code_log(
    $action,
    $fileLine,
    $request = [],
    $response = [],
    $processedData = '',
    $replaceVars = ''
) {
    if (lkn_cielo_qr_code_get_config('enableDebug') === 'on') {
        $serializePrecision = ini_get('serialize_precision');
        ini_set('serialize_precision', -1);

        logModuleCall(
            lkn_cielo_qr_code_gateway_name(),
            "$action",
            json_encode($request, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT),
            json_encode($response, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT),
            $processedData,
            $replaceVars
        );

        ini_set('serialize_precision', $serializePrecision);
    }
}

/**
 * @param  string $status
 * @param  array  $cieloResponse
 *
 * @return void
 */
function lkn_cielo_qr_code_log_transac($status, $cieloResponse) {
    if (lkn_cielo_qr_code_get_config('enableDebug') === 'on') {
        $serializePrecision = ini_get('serialize_precision');
        ini_set('serialize_precision', -1);

        logTransaction(
            lkn_cielo_qr_code_gateway_name(),
            $cieloResponse,
            $status
        );
        ini_set('serialize_precision', $serializePrecision);
    }
}

/**
 * @param  string $paymentId
 *
 * @return bool
 */
function lkn_cielo_qr_code_is_qr_code_paid($paymentId) {
    $cieloRequest = lkn_cielo_qr_code_make_cielo_request('1/sales/' . $paymentId, [], 'query', 'GET');

    $cieloResponse = json_decode(curl_exec($cieloRequest), true);
    curl_close($cieloRequest);

    return [
        'isPaid' => in_array($cieloResponse['Payment']['Status'], [1, 2], true),
        'cieloResponse' => $cieloResponse,
    ];
}

/**
 * @param $paymentAmount
 *
 * @return float
 */
function lkn_cielo_qr_code_get_fees($paymentAmount, $invoiceId) {
    try {
        $feeRate = floatval(number_format(lkn_cielo_qr_code_get_config('feeRate'), 2, '.', ''));

        if ($feeRate > 100.0) {
            throw new Exception('A taxa não pode ser maior que 100%.');
        }

        $fee = ($feeRate / 100) * $paymentAmount;

        return $fee;
    } catch (Exception $e) {
        lkn_cielo_qr_code_log_transac('Não foi possível calcular a taxa para a fatura #' . $invoiceId, $e->getMessage());
    }
}

function lkn_cielo_qr_code_api_response($responseBody) {
    header('Content-Type: application/json');
    echo json_encode($responseBody);
}
