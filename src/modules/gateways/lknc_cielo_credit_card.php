<?php

//use Smarty;
use WHMCS\Database\Capsule;

/**
 * This file is the main plugin file and has functions
 * for plugin configuration and form rendering.
 *
 * @link      https://github.com/LinkNacional/whmcs-cielo
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @since     1.0.0
 */

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

define('ROOTDIR', dirname(dirname(dirname(__FILE__))));
require_once ROOTDIR . '/includes/gatewayfunctions.php';
require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/gateway_functions.php';
require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/license_functions.php';
require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/token_gateway_functions.php';

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
function lknc_cielo_credit_card_MetaData()
{
    return [
        'DisplayName' => 'Cielo Cartão de Crédito',
        'APIVersion' => '1.0.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false
    ];
}

function lknc_get_json_fees_form()
{
    $callbackUrl = lkn_cielo_credit_card_whmcs_installation_path() . '/modules/gateways/lknc_cielo_credit_card/actions.php';
    $jsonUrlPath = lkn_cielo_credit_card_whmcs_installation_path() . '/modules/gateways/lknc_cielo_credit_card/inc/';

    // Checks if custom JSON taxes exists.
    $jsonPath = __DIR__ . '/lknc_cielo_credit_card/inc/custom_taxes.json';
    $jsonUrlPath .= file_exists($jsonPath) ? 'custom_taxes.json' : 'lknc_cielo_credit_card_taxes.json';

    return <<<HTML
    <div style="display: flex; flex-wrap: wrap; align-items: flex-start;">
                <form style="visibility: hidden;"></form> <!-- Solves the WHMCS bug of hiding the form tag below. -->
                <div style="display: flex; margin-right: 20px;">
                    <input id="new-json" class="form-control" style="max-width: 250px; min-height: 34px; margin-right: 5px;" name="new-json" type="file" accept=".json">
                    <button id="send-json-btn" class="btn btn-default" type="button" name="send-json-btn" value="send-json">Enviar JSON</button>
                </div>
                <a class="btn btn-default"  style="max-width: 250px; min-height: 34px; margin-right: 5px;" role="button" target="_blank" href="$jsonUrlPath">Visualizar JSON</a>

                <p style="display: block; width: 50%; padding: 8px; color: red;">
                    <b>ATENÇÃO:</b> tenha certeza de enviar o arquivo com os dados e formatação correta.
                    Qualquer alteração incorreta pode resultar na parada do recebimento de pagamentos.
                </p>
            </div>
            <script type="text/javascript">
                // const downloadJsonBtn = document.getElementById('download-json-btn')
                const sendJsonBtn = document.getElementById('send-json-btn')
                const newJsonInput = document.getElementById('new-json')

                sendJsonBtn.addEventListener('click', () => {
                    const data = new FormData()
                    data.append('json', newJsonInput.files[0])
                    data.append('action', 'update-json-fees')

                    fetch('{$callbackUrl}', { method: 'POST', body: data })
                        .then(res => res.json())
                        .then(res => {
                            alert(res.message)
                            if (res.success)
                                location.reload()
                        })
                        .catch(res => {
                            alert('Ocorreu um erro na sua conexão com o gateway. O arquivo não foi atualizado.')
                        })
                })
            </script>
HTML;
}

/**
 * @param array $params
 *
 * @return void
 */
function lknc_run_updates($params)
{
    // Looks for the old JSON fees.
    $oldJsonFeesPath = __DIR__ . '/callback/lknc_cielo_credit_card_taxes.json';

    if (file_exists($oldJsonFeesPath)) {
        rename($oldJsonFeesPath, __DIR__ . '/lknc_cielo_credit_card/inc/lknc_cielo_credit_card_taxes.json');
    }

    // Changes cards saved with the old name "cieloApiCartaoCredito" and "lknc_cielo_credit_card"
    // to "lknc_cielo_credit_card_token".
    Capsule::table('tblpaymethods')
        ->where('gateway_name', 'cieloApiCartaoCredito')
        ->orWhere('gateway_name', 'lknc_cielo_credit_card')
        ->update(['gateway_name' => lknc_token_gateway_name()]);
}

/**
 * Define gateway configuration options.
 *
 * @return array
 */
function lknc_cielo_credit_card_config($params)
{
    $moduleVersion = '2.10.0';
    lknc_run_updates($params);

    $saveCardModeDefault = $params['activateSaveCard'] === 'on' ? 'Opcional' : 'Desabilitado';
    $systemUrl = rtrim($params['systemurl'], '/');

    $smarty = new Smarty();

    $smarty->setTemplateDir(__DIR__ . '/lknc_cielo_credit_card/inc/templates/');
    $smarty->assign('moduleVersion', $moduleVersion);
    $smarty->assign('moduleUrl', "$systemUrl/modules/gateways/lknc_cielo_credit_card");
    $smarty->assign('moduleName', 'Cielo Cartão de Crédito');
    $header = $smarty->fetch('config_header.tpl');

    $configs = [
        '' => ['Description' => $header],
        'lknLicense' => [
            'FriendlyName' => 'Licença da Link Nacional',
            'Description' => '
                <br>Para utilizar esse meio de pagamento adquira uma licença em
                <a href="https://www.linknacional.com.br" target="_blank">linknacional.com.br</a>.',
            'Type' => 'password',
            'Size' => '25'
        ],
        'warnings' => [
            'FriendlyName' => '',
            'Description' => '',
        ],
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'Cielo Cartão de Crédito'
        ],
        'activateTestMode' => [
            'FriendlyName' => 'Ativar o modo SandBox',
            'Description' => '
                Transações realizadas no ambiente de SANDBOX da CIELO API, para funcionar informe as MerchantKey e ID da <a href="https://cadastrosandbox.cieloecommerce.cielo.com.br/" target="_blank">CIELO Sandbox</a></b>.',
            'Type' => 'yesno'
        ],
        'debitMerchandId' => [
            'FriendlyName' => 'Cielo Merchant ID',
            'Description' => 'Código Merchant ID enviado pela CIELO.',
            'Type' => 'password',
            'Size' => '25'
        ],
        'debitMerchantKey' => [
            'FriendlyName' => 'Cielo Merchant Key',
            'Description' => 'Código Merchant Key enviado pela CIELO.',
            'Type' => 'password',
            'Size' => '25',
            'Default' => ''
        ],
        'invoiceCustomDescription' => [
            'FriendlyName' => 'Descrição da fatura',
            'Description' => 'Informe um nome de até 12 caracteres para aparecer na fatura do cartão do comprador.',
            'Type' => 'text',
            'Size' => '12',
            'Default' => 'Link Nacional'
        ],
        'cardSaveMode' => [
            'FriendlyName' => 'Salvar cartão no perfil do cliente',
            'Type' => 'radio',
            'Options' => 'Obrigatório,Opcional,Desabilitado',
            'Description' => 'Como o módulo deve lidar com o salvamente de cartões no perfil do cliente.',
            'Default' => $saveCardModeDefault
        ],
        'activateDebug' => [
            'FriendlyName' => 'Ativar log do gateway',
            'Description' => '',
            'Description' => '
                Manter ativo apenas para verificação do módulo.
                <a href="logs/module-log" target="_blank">Ver logs</a>',
            'Type' => 'yesno'
        ],
        'partialPayment' => [
            'FriendlyName' => 'Habilitar pagamento parcial',
            'Description' => '
                O usuário poderá escolher quanto pagar do valor total da fatura.<br>
                Deste modo, o usuário pode parcelar o valor da fatura em mais de um cartão.',
            'Type' => 'yesno',
            'Default' => '0'
        ],
        'partialPaymentMinimumAmount' => [
            'FriendlyName' => 'Valor mínimo da fatura para<br>pagamento parcial',
            'Description' => '
                Utilize apenas números e vírgula.
                <br>O opção de pagamento parcial só irá ser exbida se o valor total da<br>
                fatura for maior ou igual ao valor informado aqui.',
            'Type' => 'text',
            'Size' => '12',
            'Default' => '50'
        ],
        'enableInstallment' => [
            'FriendlyName' => 'Habilitar parcelamento de faturas',
            'Type' => 'yesno',
            'Default' => '1',
            'Description' => 'Fatura poderá ser parcelada em até 12 vezes.'
        ],
        'minimumInstallmentAmount' => [
            'FriendlyName' => 'Valor mínimo da parcela',
            'Type' => 'text',
            'Size' => '12',
            'Default' => '5',
            'Description' => 'A parcela mínima que será exibida no formulário de pagamento.'
        ],
        'maximumPaymentAttempts' => [
            'FriendlyName' => 'Limitador de pagamento',
            'Type' => 'text',
            'Size' => '12',
            'Default' => '0',
            'Description' => '0 para ilimitado, esta opção visa bloquear usuários maliciosos testando números de cartão.'
        ],
        'maxAttemptsReachedFeedback' => [
            'FriendlyName' => 'Mensagem para usuários bloqueados',
            'Type' => 'textarea',
            'Size' => '50',
            'Default' => 'O meio de pagamento não esta mais disponível pois atingiu o limite de tentativas de pagamento.',
            'Description' => 'Mensagem exibida na tela da fatura aos usuários que foram barrados pelo Limitador de pagamentos'
        ],
        'allow_payment_only_for_brl' => [
            'FriendlyName' => 'Permitir apenas para faturas em moeda BRL',
            'Type' => 'yesno',
            'Description' => 'Permitir pagamentos utilizando o gateway apenas para faturas em moeda BRL.',
            'Default' => 'on'
        ],
        'allow_payment_only_for_brl_error_feedback' => [
            'FriendlyName' => 'Mensagens quando fatura não está em BRL',
            'Type' => 'textarea',
            'Description' => 'Mensagem para exibir quando fatura não está em BRL.',
            'Default' => 'Não é possível realizar o pagamento por esse meio, pois a fatura não está em Reais (R$).'
        ],
        'enableCalculateFees' => [
            'FriendlyName' => 'Habilitar cálculo de taxas',
            'Type' => 'yesno',
            'Default' => '0',
            'Description' => 'O gateway irá utilizar o arquivo JSON de taxas para calcular as taxas.'
        ],
        'jsonFees' => [
            'FriendlyName' => 'Arquivo da taxas da CIELO',
            'Type' => 'input',
            'Description' => lknc_get_json_fees_form()
        ],
        'enableCreditByDate' => [
            'FriendlyName' => 'Transação por data',
            'Type' => 'yesno',
            'Default' => '0',
            'Description' => 'O Gateway irá inserir a transação de acordo com a data de recebimento na CIELO.'
        ],
        'enableCieloZeroAuth' => [
            'FriendlyName' => 'Ativar validação avançada',
            'Type' => 'yesno',
            'Description' => '
                Oferece análise de segurança avançada de cartões com bandeira ELO, MasterCard ou Visa.<br>
                Para utilizar este serviço, solicite à CIELO a liberação do ZeroAuth para a sua licença.'
        ],
        'footer' => [
            'FriendlyName' => 'Recomendação',
            'Description' => "
            <div style='margin: 15px; display: flex; align-items: center;'>
                <p>
                Habilite a opção na
                <a href='configauto.php' target='_blank'>configuração de automação</a>
                 do WHMCS \"Tentar somente uma vez\".
                </p>
            </div>
            "
        ]
    ];

    require_once __DIR__ . '/lknc_cielo_credit_card/whmcs_module_updater.php';

    $modulePath = __DIR__;
    $whmcsRootPath = substr($modulePath, 0, strpos($modulePath, '/public_html'));
    $whmcsRootPath .= '/public_html';

    $tempPath = $whmcsRootPath;
    $zipFilename = 'whmcs-cielo';

    $moduleUpdater = new WhmcsModuleUpdater(
        $tempPath,
        $whmcsRootPath,
        'whmcs-cielo-credit-card',
        $zipFilename
    );

    $latestVersion = $moduleUpdater->getLatestVersion();

    if (version_compare($latestVersion, $moduleVersion, '>')) {
        $moduleName = 'whmcs_cielo_credit_card';

        $configs['warnings']['Description'] = <<<HTML
        <div style="margin: 10px;">
            <p class="text-success" style="font-size: 1.12em; font-weight: bold;">Há uma nova atualização para o gateway</p>
            <p class="text-success" style="font-size: 0.9em; font-weight: bold;">
                A nova versão é a $latestVersion, mas você está usando a $moduleVersion.<br>
                Verifique a área do cliente da Link Nacional.
            </p>
        </div>
HTML;
    }

    $oldModulesNames = ['cieloApiCartaoDebito', 'cieloApiCartaoCredito'];
    $hasOldModulesInstalled = Capsule::table('tblpaymentgateways')->whereIn('gateway', $oldModulesNames)->exists();

    if ($hasOldModulesInstalled) {
        $configs['warnings']['Description'] .= <<<HTML
            <p style="color:#cc0000; font-weight: bold; margin-bottom: 10px;">
                Recomendamos a desinstalação dos seguintes módulos, se estiverem instalados:
            </p>
            <ul>
                <li>Cielo API Cartão de Débito</li>
                <li>Cielo API Cartão de Crédito</li>
            </ul>
            Com isso, o módulo que você está visualizando terá um melhor funcionamento.
HTML;
    }

    $isLinkNacionalLicenseValid = lknc_check_license();

    if ($isLinkNacionalLicenseValid !== true) {
        $configs['warnings']['Description'] = <<<HTML
            <p style="color:#cc0000; font-weight: bold;">
            $isLinkNacionalLicenseValid
            </p>
HTML;
    }

    // Checks if any error was added to the warning config.
    // If not, remove from the confis array.
    if ($configs['warnings']['Description'] === '') {
        unset($configs['warnings']);
    }

    return $configs;
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @param  array  $params Payment Gateway Module Parameters
 * @return string
 */
function lknc_cielo_credit_card_link($params)
{
    $smarty = new Smarty();
    $templatesPath = __DIR__ . '/lknc_cielo_credit_card/inc/templates/';
    $smarty->setTemplateDir($templatesPath);

    $allowPaymentOnlyForBrl = isset($params['allow_payment_only_for_brl']) ? $params['allow_payment_only_for_brl'] : true;

    if ($allowPaymentOnlyForBrl && $params['currency'] !== 'BRL') {
        $msg = $params['allow_payment_only_for_brl_error_feedback'] ?? null;

        $smarty->assign(
            'allowPaymentOnlyForBrlErrorFeedback',
            $msg ? trim($msg) : ''
        );

        return $smarty->fetch('payment_form.tpl');
    }

    $maxAttemptsReachedFeedback = lkn_cielo_credit_card_get_config('maxAttemptsReachedFeedback');

    $smarty->assign('maxAttemptsReachedFeedback', $maxAttemptsReachedFeedback);

    if (lkn_cielo_credit_card_invoice_reached_max_attempts($params['invoiceid'])) {
        $smarty->assign('maxAttemptsReached', true);

        return $smarty->fetch('payment_form.tpl');
    }

    $isValidLicense = lknc_check_license();

    if ($isValidLicense !== true) {
        return <<<HTML
        <div style="margin: 35px 0px;">Licença do gateway inválida.</div>
HTML;
    }

    // Handles the case in which the process of payment is made through payment of a service.
    if (
        trim($_SERVER['PHP_SELF'], '/') === 'cart.php' ||
        $_SERVER['REQUEST_URI'] === '/clientarea.php?action=addfunds'
    ) {
        return 'Aguarde o redirecionamento.';
    }

    $formTarget = '/modules/gateways/callback/lknc_cielo_credit_card.php';

    $actionUrl = lknc_gateway_params('systemurl') . '/modules/gateways/lknc_cielo_credit_card/actions.php';

    $invoice = localAPI('GetInvoice', ['invoiceid' => $params['invoiceid']]);
    $invoiceBalance = $invoice['balance'];

    $paymentMethods = localAPI('GetPayMethods', [
        'clientid' => $params['clientdetails']['userid']
    ])['paymethods'];
    $paymentMethods = array_filter($paymentMethods, function ($payMethod) {
        return $payMethod['gateway_name'] === 'lknc_cielo_credit_card_token';
    });

    $paymentMethods = array_map(function ($paymentMethod) {
        $explodedRemoteToken = explode('|', $paymentMethod['remote_token']);
        $hasBrandInRemoteToken = count($explodedRemoteToken) > 1;

        if ($hasBrandInRemoteToken) {
            $cardBrand = ucwords(lkn_cielo_credit_brand_to_whmcs($explodedRemoteToken[0]));
            $token = $explodedRemoteToken[1];
        } else {
            $cardBrand = $paymentMethod['card_type'];
            $token = $paymentMethod['remote_token'];
        }

        return [
            'token' => $token,
            'brand' => $cardBrand,
            'type' => $paymentMethod['type'],
            'expDate' => $paymentMethod['expiry_date'],
            'lastFourDigits' => $paymentMethod['card_last_four']
        ];
    }, $paymentMethods);

    $jsonFees = lkn_cielo_credit_card_get_json_fees();

    $installmentsPerBrand = [];

    foreach ($jsonFees as $brand => $installments) {
        $installmentsPerBrand[$brand] = count($installments['credito']);
    }

    $partialPaymentMinimumAmount = lkn_cielo_credit_card_get_config('partialPaymentMinimumAmount');
    $minimumInstallmentAmount = lkn_cielo_credit_card_get_config('minimumInstallmentAmount');

    $smarty->assign('formTarget', $formTarget);
    $smarty->assign('actionUrl', $actionUrl);
    $smarty->assign('paymentMethods', $paymentMethods);
    $cardSaveMode = lknc_gateway_params('cardSaveMode');
    $cardSaveModes = [
        'Obrigatório' => 1,
        'Opcional' => 2,
        'Desabilitado' => 3
    ];
    $smarty->assign('cardSaveMode', $cardSaveModes[$cardSaveMode]);

    $smarty->assign('invoiceId', $params['invoiceid']);
    $smarty->assign('customerId', $params['clientdetails']['client_id']);
    $smarty->assign('invoiceBalance', $invoiceBalance);
    $smarty->assign('partialPayment', $params['partialPayment'] === 'on');
    $smarty->assign('partialPaymentMinimumAmount', $partialPaymentMinimumAmount);
    $smarty->assign('minimumInstallmentAmount', $minimumInstallmentAmount);
    $smarty->assign('enableInstallment', $params['enableInstallment']);
    $smarty->assign('installmentsPerBrand', $installmentsPerBrand);

    $isUsingLagomTheme = Capsule::table('tblconfiguration')
        ->where('setting', 'Template')
        ->where('value', 'lagom2')
        ->exists();

    $smarty->assign('isUsingLagomTheme', $isUsingLagomTheme);

    return $smarty->fetch('payment_form.tpl');
}

/**
 * Refund transaction.
 *
 * Called when a refund is requested for a previously successful transaction.
 *
 * @see https://developers.whmcs.com/payment-gateways/refunds/
 *
 * @param  array $params Payment Gateway Module Parameters
 * @return array Transaction response status
 */
function lknc_cielo_credit_card_refund($params)
{
    $transactionId = explode('.', $params['transid'])[2];

    $invoice = localAPI('GetInvoice', ['invoiceid' => $params['invoiceid']]);
    $invoiceAmount = number_format(floatval($invoice['subtotal']), 2);

    // Verify if the invoice must be completely refounded.
    $refundAmount = floatval($params['amount']);
    $refundTotal = $invoiceAmount === $refundAmount;

    $refundUrl = !$refundTotal ? '?amount=' . $refundAmount * 100 : '';

    $requestBody = ['PaymentId' => $transactionId];

    $cieloResource = '/1/sales/' . $transactionId . '/void' . $refundUrl;
    $responseArray = lknc_cielo_credit_card_cielo_api_request($cieloResource, $requestBody, 'PUT');

    $responseJson = json_encode($responseArray);

    $successCodes = [10, 11, 2];

    if (in_array($responseArray['Status'], $successCodes, true)) {
        return ['status' => 'success', 'rawdata' => $responseJson, 'transid' => 'REEMBOLSO.' . $transactionId];
    } else {
        return ['status' => 'failed', 'rawdata' => $responseJson];
    }
}
