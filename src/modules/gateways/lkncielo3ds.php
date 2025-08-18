<?php

use WHMCS\Config\Setting;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Api\AuthorizationApi;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Entities\TransactionId;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\BuildClassMapFormRequest;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Address;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\CartItem;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Device;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Discount;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\Order;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\ClassMapFormItems\User;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\RefundRequest;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Services\BuildClassMapFormService;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Services\RefundService;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\CieloAmountFormatter;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Config;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Discount as DiscountHelper;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Invoice;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Logger;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\View;

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

define('ROOTDIR', dirname(dirname(dirname(__FILE__))));
require_once ROOTDIR . '/modules/gateways/lkncielo3ds/lib/utils/utils.php';
require_once ROOTDIR . '/modules/gateways/lkncielo3ds/license.php';

/**
 * Define module related meta data.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function lkncielo3ds_MetaData()
{
    return [
        'DisplayName' => 'Cielo 3DS',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    ];
}

/**
 * Define gateway configuration options.
 *
 * @see https://developers.whmcs.com/payment-gateways/configuration/
 *
 * @return array
 */
function lkncielo3ds_config()
{
    $systemURL = rtrim(Setting::getValue('SystemURL'), '/');

    $header = View::render(
        'config_header',
        [
            'logoUrl' => $systemURL . '/modules/gateways/lkncielo3ds/logo.png',
            'moduleVersion' => Config::constant('version')
        ]
    );

    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => Config::constant('friendlyName'),
        ],

        '' => ['Description' => $header],

        'lkn_license' => [
            'FriendlyName' => 'Licença da Link Nacional',
            'Description' => 'Licença Link Nacional.',
            'Type' => 'password',
            'Size' => '25'
        ],

        'credentials' => [
            'Description' => <<<HTML
            <div style="margin: 20px 0px 10px; font-weight: bold; font-size: 1.1em;">
                Credenciais da API
            </div>
HTML
        ],

        'env' => [
            'FriendlyName' => 'Ambiente',
            'Type' => 'dropdown',
            'Options' => [
                'prod' => 'Produção',
                'dev' => 'Desenvolvimento',
            ],
            'Description' => 'Define se o gateway irá operar em modo produção ou desenvolvimento. Lembre-se de atualizar as credenciais abaixo de acordo com o ambiente que você definiu aqui.',
            'Default' => 'dev'
        ],

        'api_client_id' => [
            'FriendlyName' => 'Client ID',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => '',
        ],

        'api_client_secret' => [
            'FriendlyName' => 'Client Secret',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => '',
        ],

        'api_merchant_id' => [
            'FriendlyName' => 'Merchant ID',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => '',
        ],

        'api_merchant_key' => [
            'FriendlyName' => 'Merchant Key',
            'Type' => 'password',
            'Size' => '25',
            'Default' => '',
            'Description' => '',
        ],

        'api_merchant_name' => [
            'FriendlyName' => 'Nome do estabelecimento',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Nome do estabelecimento registrado na Cielo. Tamanho máximo: 25 caracteres.',
        ],

        'api_establishment_code' => [
            'FriendlyName' => 'Código do estabelecimento',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Tamanho: 10 caracteres.',
        ],

        'api_mcc' => [
            'FriendlyName' => 'Código de categoria',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Tamanho: 4 caracteres.',
        ],

        'api_soft_descriptor' => [
            'FriendlyName' => 'Descrição da fatura',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Informe um nome de até 12 caracteres para aparecer na fatura do cartão do comprador. Não permite caracteres especiais.',
        ],

        'customization' => [
            'Description' => <<<HTML
            <div style="margin: 20px 0px 10px; font-weight: bold; font-size: 1.1em;">
                Personalização
            </div>
HTML
        ],

        'send_client_address_details_to_3ds' => [
            'FriendlyName' => 'Enviar endereço do cliente à validação 3DS',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'O formulário de pagamento exibirá campos de endereço que o cliente deverá preencher. Com isso, a validação do 3DS será mais precisa.'
        ],

        'min_installment_value' => [
            'FriendlyName' => 'Valor mínimo da parcela',
            'Type' => 'text',
            'Size' => '12',
            'Default' => '5',
            'Description' => 'Utilize apenas números e vírgula. Valor mínimo que o parcelamento pode alcançar. Ex: 10,55 | 150'
        ],

        'credit_discount' => [
            'FriendlyName' => 'Desconto por pagamento por crédito',
            'Type' => 'text',
            'Size' => '12',
            'Default' => '0',
            'Description' => 'Utilize apenas números e vírgula. Ex.: como 9,50 | 0,55 | 50. Apenas duas casas decimais são consideradas. Deixe vazio para não habilitar. Apenas é aplicado caso o cliente pague o valor total. Não é aplicado em pagamentos sobre parciais, ou quando já constam transações na fatura.'
        ],

        'debit_discount' => [
            'FriendlyName' => 'Desconto por pagamento por débito',
            'Type' => 'text',
            'Size' => '12',
            'Default' => '0',
            'Description' => 'Utilize apenas números e vírgula. Ex.: como 9,50 | 0,55 | 50. Apenas duas casas decimais são consideradas. Deixe vazio para não habilitar. Apenas é aplicado caso o cliente pague o valor total. Não é aplicado em pagamentos sobre parciais, ou quando já constam transações na fatura.'
        ],

        'enable_partial_payment' => [
            'FriendlyName' => 'Permitir pagamento parcial',
            'Description' => '
                O usuário poderá escolher quanto pagar do valor total da fatura.
                Deste modo, o usuário pode parcelar o valor da fatura em mais de um cartão.
                Aplica-se para débito e crédito.',
            'Type' => 'yesno',
            'Default' => '0'
        ],
        'enable_credit_card_installments' => [
            'FriendlyName' => 'Permitir pagamento parcelado',
            'Type' => 'yesno',
            'Default' => 'yes',
            'Description' => 'O formulário de pagamento exibirá campos de parcelamento que o cliente deverá preencher.'
        ],

        'partial_payment_min_amount' => [
            'FriendlyName' => 'Valor mínimo da fatura para pagamento parcial',
            'Description' => '
                Utilize apenas números e vírgula.
                O opção de pagamento parcial só irá ser exbida se o valor total da
                fatura for maior ou igual ao valor informado aqui.',
            'Type' => 'text',
            'Size' => '12',
            'Default' => '50'
        ],

        'calculate_brand_taxes' => [
            'FriendlyName' => 'Calcular taxas por bandeira e por parcelamento',
            'Type' => 'yesno',
            'Description' => 'Caso você não tenha enviado o seu próprio arquivo, as taxas padrões serão utilizadas.'
        ],

        'brand_taxes_file' => [
            'FriendlyName' => '',
            'Description' => View::render(
                'brand_taxes_form',
                [
                    'systemUrl' => $systemURL,
                ]
            )
        ],
    ];
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
function lkncielo3ds_link($params): string
{
    try {
        $isLicenseValid = lkncielo3ds_check_license();

        if ($isLicenseValid !== true) {
            return View::render(
                'form.form',
                ['errorMsg' => $isLicenseValid]
            );
        }

        // Solves a bug of showing the form while WHMCS is redirectin the client
        // to the invoice page but still show the form.
        if (
            trim($_SERVER['PHP_SELF'], '/') === 'cart.php' ||
            $_SERVER['REQUEST_URI'] === '/clientarea.php?action=addfunds'
        ) {
            return 'Aguarde o redirecionamento.';
        }

        $accessToken = (new AuthorizationApi(
            $params['api_client_id'],
            $params['api_client_secret']
        ))->requestAccessToken(
            $params['api_establishment_code'],
            $params['api_merchant_name'],
            $params['api_mcc']
        );

        $authEnabled = 'true';
        $authEnabledNotifyOnly = 'true';
        $authSupressChallange = 'false';

        $paymentAmount = (float) ($params['amount']);
        $cieloPaymentAmountFormat = CieloAmountFormatter::toCieloFormat($paymentAmount);
        $minInstallmentValue = (float) (str_replace(',', '.', $params['min_installment_value']));

        $orderNumber = "{$params['invoiceid']}x{$params['clientdetails']['client_id']}";

        $deviceIpAddress = $params['clientdetails']['model']->ip;

        $orderTransMode = '';
        $orderMechantUrl = $params['systemurl'];
        $oderReccurence = '';
        $orderProductCode = 'PHY';
        $orderLast24HourCount = '';
        $orderLast6MonthCount = '';
        $orderLastYearCount = '';
        $orderCardAttemptsOnLast24Hours = '';
        $orderMerketingOptin = ((bool) ($params['clientdetails']['model']->marketing_emails_opt_in)) ? 'true' : 'false';
        $orderMarketinSource = '';

        $userAccountGuest = false;
        $userCreatedDate = $params['clientdetails']['model']->created_at;
        $userChangedDate = $params['clientdetails']['model']->updated_at;
        $userPasswordChangedDate = '';
        $userAuthenticationMethod = '02';
        $userAuthenticationProtocol = '';

        $lastLogin = $params['clientdetails']['model']->lastlogin;
        $userAuthenticationTimestamp = (DateTime::createFromFormat('Y-m-d H:i:s', $lastLogin))->getTimestamp();

        $addressCustomerId = '';
        $addressNewCustomer = 'false';
        $addressBillToName = $params['clientdetails']['fullname'];
        $addressPhoneNumber = preg_replace('/[^0-9+]+/', '', $params['clientdetails']['model']->phonenumber);
        $addressEmail = $params['clientdetails']['email'];
        $addressStreet1 = $params['clientdetails']['address1'];
        $addressNumber = '';

        $addressStreet1Explode = explode(',', $addressStreet1);

        if (count($addressStreet1Explode) > 1) {
            $addressStreet1 = trim($addressStreet1Explode[0]);
            $addressNumber = trim($addressStreet1Explode[1]);
        }

        $addressStreet2 = $params['clientdetails']['address2'];
        $addressCity = $params['clientdetails']['city'];
        $addressState = $params['clientdetails']['state'];
        $addressCountry = $params['clientdetails']['country'];
        $addressZipcode = $params['clientdetails']['postcode'];
        $addressIsDeliveryAddressSameAsBilling = 'true';

        $cartItems = array_map(function ($item) {
            return new CartItem(
                $item->name,
                '',
                '',
                $item->qty,
                null
            );
        }, $params['cart']->items->toArray());

        $service = new BuildClassMapFormService();

        $env = Config::setting('env');

        $debitDiscount = DiscountHelper::calculateDiscount('debit', $paymentAmount);
        $creditDiscount = DiscountHelper::calculateDiscount('credit', $paymentAmount);

        $partialPaymentMinAmount = round((float) (str_replace(',', '.', $params['partial_payment_min_amount'])), 2);

        $enableDiscount = count(Invoice::getTrans($params['invoiceid'])) === 0;

        return $service->run(
            new BuildClassMapFormRequest(
                $env,
                $minInstallmentValue,
                $authEnabled,
                $authEnabledNotifyOnly,
                $authSupressChallange,
                $accessToken,
                $orderNumber,
                $paymentAmount,
                $cieloPaymentAmountFormat,
                $params['send_client_address_details_to_3ds'],
                ($params['enable_partial_payment'] ?? false),
                $partialPaymentMinAmount,
                new Discount(
                    $enableDiscount,
                    $debitDiscount['discountAmount'],
                    $debitDiscount['discountPercentage'],
                    $debitDiscount['paymentAmountWithDiscount'],
                    $creditDiscount['discountAmount'],
                    $creditDiscount['discountPercentage'],
                    $creditDiscount['paymentAmountWithDiscount']
                ),
                new Address(
                    $addressCustomerId,
                    $addressNewCustomer,
                    $addressBillToName,
                    $addressPhoneNumber,
                    $addressEmail,
                    $addressStreet1,
                    $addressStreet2,
                    $addressNumber,
                    $addressCity,
                    $addressState,
                    $addressCountry,
                    $addressZipcode,
                    $addressIsDeliveryAddressSameAsBilling,
                ),
                new Device(
                    $deviceIpAddress
                ),
                new Order(
                    $orderTransMode,
                    $orderMechantUrl,
                    $oderReccurence,
                    $orderProductCode,
                    $orderLast24HourCount,
                    $orderLast6MonthCount,
                    $orderLastYearCount,
                    $orderCardAttemptsOnLast24Hours,
                    $orderMerketingOptin,
                    $orderMarketinSource
                ),
                new User(
                    $userAccountGuest,
                    $userCreatedDate,
                    $userChangedDate,
                    $userPasswordChangedDate,
                    $userAuthenticationMethod,
                    $userAuthenticationProtocol,
                    $userAuthenticationTimestamp
                ),
                $cartItems
            )
        );
    } catch (Throwable $e) {
        Logger::log('Erro', $params, [$e->getMessage()]);

        return View::render(
            'form.form',
            ['errorMsg' => 'Ocorreu um erro. Acesse os logs dos portais para mais informações.']
        );
    }
}

/**
 * Refund transaction.
 *
 * Called when a refund is requested for a previously successful transaction.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/refunds/
 *
 * @return array Transaction response status
 */
function lkncielo3ds_refund($params): array
{
    $isLicenseValid = lkncielo3ds_check_license();

    if ($isLicenseValid !== true) {
        return [
            'status' => 'error',
            'rawdata' => $isLicenseValid
        ];
    }

    $invoiceId = $params['invoiceid'];
    $transId = TransactionId::fromWhmcsTransId($params['transid']);
    $paymentId = $transId['paymentId'];
    $paymentAmount = number_format($params['amount'], 2, '', '');

    $request = new RefundRequest(
        $invoiceId,
        $paymentId,
        $paymentAmount
    );

    $response = (new RefundService(
        $params['api_merchant_id'],
        $params['api_merchant_key']
    ))->run($request);

    return $response['data'];
}
