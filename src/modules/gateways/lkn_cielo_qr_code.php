<?php
/**
 * @link      https://github.com/LinkNacional/whmcs-cielo-qrcode
 * @link      https://developers.whmcs.com/payment-gateways/third-party-gateway/
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @author    Bruno Ferreira <ferreira.bruno@linknacional.com>
 * @since     1.0.0
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

require_once __DIR__ . '/lkn_cielo_qr_code/func/gateway_functions.php';
require_once __DIR__ . '/lkn_cielo_qr_code/func/license_functions.php';

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function lkn_cielo_qr_code_MetaData() {
    return [
        'DisplayName' => 'QR Code CIELO',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

/**
 * @return array
 */
function lkn_cielo_qr_code_config($vars) {
    $systemUrl = rtrim($vars['systemurl'], '/');
    $moduleLogsUrl = $systemUrl . str_replace('/configgateways.php', '/index.php/admin/logs/module-log', $_SERVER['PHP_SELF']);
    $moduleUrl = "$systemUrl/modules/gateways/lkn_cielo_qr_code/assets";

    $settings = [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'QR Code CIELO',
        ],

        'header' => [
            'Description' => "
        <div style='margin: 20px;'>
            <img src='{$moduleUrl}/logo-linknacional-small.png' style='max-width: 180px;'>
        </div>",
        ],

        'warnings' => [
            'FriendlyName' => '',
            'Description' => '',
        ],

        'lknLicense' => [
            'FriendlyName' => 'Licença da Link Nacional',
            'Description' => '
                <br>Para utilizar esse meio de pagamento adquira uma licença em
                <a href="https://www.linknacional.com.br" target="_blank">linknacional.com.br</a>.',
            'Type' => 'text',
            'Size' => '25',
        ],

        'merchantId' => [
            'FriendlyName' => 'Cielo Merchant ID',
            'Description' => 'Código Merchant ID enviado pela CIELO.',
            'Type' => 'text',
            'Size' => '25',
        ],

        'merchantKey' => [
            'FriendlyName' => 'Cielo Merchant Key',
            'Description' => 'Código Merchant Key enviado pela CIELO.',
            'Type' => 'text',
            'Size' => '25',
        ],

        'feeRate' => [
            'FriendlyName' => 'Taxa',
            'Description' => '
                Taxa em porcentagem cobrada sobre o valor total do QR Code.
                <br>Utlize apenas números e utilize ponto (.) para delimitar casas decimais. Ex.: 2.5, 1.2, 20.5.
                <br>Serão consideradas até 2 casas decimais.',
            'Type' => 'text',
            'Size' => '3',
            'Default' => '0'
        ],

        'enableDebug' => [
            'FriendlyName' => 'Ativar log do gateway',
            'Description' => '
                Manter ativo apenas para verificação do módulo.
                <a href="' . $moduleLogsUrl . '" target="_blank">Ver logs</a>',
            'Type' => 'yesno',
        ],

        'enableTestMode' => [
            'FriendlyName' => 'Ativar o modo teste',
            'Description' => '
                Operações realizadas serão realizadas pela API de sandbox da CIELO, <b>lembre-se de<br>
                alterar as MerchantKey e ID da CIELO para as de sandbox</b>.',
            'Type' => 'yesno',
            'Default' => 'no',
        ],
    ];

    try {
        $isLknLicenseValid = lkn_cielo_qr_code_check_license();

        if ($isLknLicenseValid !== true) {
            $settings['warnings']['Description'] = <<<HTML
            <div class="d-flex justify-content-center align-items-center">
                <p class="lead my-5 text-danger">{$isLknLicenseValid}</p>
            </div>
HTML;
        }
    } catch (Exception $e) {
        // echo $e;
    }

    // Checks if any error was added to the warning config.
    // If not, remove from the confis array.
    if ('' === $settings['warnings']['Description']) {
        unset($settings['warnings']);
    }

    return $settings;
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
function lkn_cielo_qr_code_link($params) {
    $isLknLicenseValid = lkn_cielo_qr_code_check_license();

    if ($isLknLicenseValid !== true) {
        $smarty = new Smarty();

        $path = __DIR__ . '/lkn_cielo_qr_code/templates/components/';
        $smarty->setTemplateDir($path);
        $smarty->assign(['licenseErrorMsg' => $isLknLicenseValid]);

        return $smarty->fetch('lkn_license_error.tpl');
    }

    $paymentAmount = lkn_cielo_qr_code_format_amount_to_cielo_pattern($params['amount']);
    $cieloRequestBody = [
        'MerchantOrderId' => $params['invoicenum'] . '_' . uniqid(),
        'Payment' => [
            'Type' => 'qrcode',
            'Amount' => $paymentAmount,
            'Installments' => 1,
            'Capture' => true,
        ],
    ];

    $cieloRequest = lkn_cielo_qr_code_make_cielo_request('1/sales', $cieloRequestBody);

    $cieloResponse = json_decode(curl_exec($cieloRequest), true);
    curl_close($cieloRequest);

    $requestSuccessful = isset($cieloResponse['Payment']['QrCodeBase64Image']);

    if ($requestSuccessful) {
        $clientId = $params['clientdetails']['client_id'];
        $invoiceId = $params['invoiceid'];
        $paymentId = $cieloResponse['Payment']['PaymentId'];

        lkn_cielo_qr_code_register_qr_code_generation($paymentId, $clientId, $invoiceId);
        lkn_cielo_qr_code_log_transac('QR Code gerado para a fatura #' . $invoiceId, $cieloResponse);

        $qrCodeBase64 = $cieloResponse['Payment']['QrCodeBase64Image'];

        $checkQrCodePayment = file_get_contents(__DIR__ . '/lkn_cielo_qr_code/assets/check_invoice_payment.js');
        $systemUrl = rtrim($params['systemurl'], '/');

        return <<<HTML
<script type="text/javascript">
  const invoiceId = $invoiceId
  const systemUrl = '$systemUrl'
  const checkerUrl = systemUrl + '/modules/gateways/lkn_cielo_qr_code/check_invoice_payment.php'

  var qrCodeCheckTimeout = Function
  let attemptsCount = 1

  const requestQrCodePaymentConfimration = () => {
    if (attemptsCount === 5) {
      clearInterval(qrCodeCheckTimeout)
    }

    const requestBody = {
      invoiceId: invoiceId
    }

    fetch(checkerUrl, { method: 'POST', body: JSON.stringify(requestBody) })
      .then(res => res.json())
      .then(res => {
        if (res.paid === true) {
            window.location.reload()
            clearInterval(qrCodeCheckTimeout)
        } else {
            attemptsCount++
        }
      })
      .catch(err => {
        //
      })
    return false
  }

  // Checks after 1 minute
  qrCodeCheckTimeout = setInterval(requestQrCodePaymentConfimration, 90000)
</script>
<div class="d-flex row justify-content-center align-items-center">
  <div class="d-flex justify-content-center align-items-center">
    <p class="text-center">
      <i
        class="fal fa-shield-check"
        style="color: #22de54;"
        title="Transação segura por certificado SSL 256bits"
      ></i>
      Pagamento seguro: PicPay, Mercado Pago, Cielo Pay, Banco do Brasil, Bradesco, AME, Banco Original, Next, AgiBank,
      Payly, Bitfy, BANQI, Banestes, UZZO, Alyment, Moeda.
    </p>
  </div>
  <img src="data:image/png;base64, {$qrCodeBase64}">

  <p>Confirmamos automaticamente em até 5 minutos.</p>
  <style scoped>
    img {
      width: 100%;
      shape-rendering: geometricPrecision;
      image-rendering: optimizeQuality;
      text-rendering: optimizeLegibility;
    }
  </style>
  </img>
</div>

HTML;
    }

    lkn_cielo_qr_code_log_transac('Erro ao gerar QR Code para a fatura #' . $params['invoiceid'], $cieloResponse);

    return <<<HTML
    <div class="d-flex justify-content-center align-items-center">
        <p class="lead my-5">Ocorreu um erro e não foi possível gerar o QR Code.</p>
    </div>
HTML;
}
