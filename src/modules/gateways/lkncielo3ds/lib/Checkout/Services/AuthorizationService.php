<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Checkout\Services;

use Throwable;
use WHMCS\Config\Setting;
use WHMCS\Database\Capsule;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Api\EcommerceApi;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Entities\TransactionId;
use WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequest;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\CieloAmountFormatter;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Config;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Invoice;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Logger;

/**
 * @since 1.0.0
 */
final class AuthorizationService
{
    /**
     * @since 1.0.0
     * @var \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Abstract\EcommerceApi
     */
    private readonly EcommerceApi $ecommerceApi;

    /**
     * @since 1.0.0
     * @var CardBinService
     */
    private readonly CardBinService $cardBinService;

    /**
     * @since 1.1.0
     * @var \WHMCS\Module\Gateway\lkncielo3ds\Checkout\Requests\AuthorizationRequest
     */
    private readonly AuthorizationRequest $request;

    private bool $mustApplyDiscount = false;

    private ?float $discountAmount = null;
    private CalculateDiscountForInvoice $discountService;

    /**
     * @since 1.0.0
     */
    public function __construct(AuthorizationRequest $request)
    {
        $this->request = $request;

        $this->discountService = new CalculateDiscountForInvoice(
            $this->request->invoiceId,
            $this->request->amount,
            $this->request->card->type
        );

        $this->cardBinService = new CardBinService();
        $this->ecommerceApi = new EcommerceApi(
            Config::env('apiTransUrl'),
            Config::env('apiQueryUrl'),
            Config::setting('api_merchant_id'),
            Config::setting('api_merchant_key')
        );
    }

    /**
     * @since 1.0.0
     *
     * @return void
     */
    public function run(): array
    {
        $isLoggedUserValid = $this->isLoggedUserValidForAuthorization($this->request);

        if (!$isLoggedUserValid['isValid']) {
            Logger::log('Validar cliente pagador', $this->request, $isLoggedUserValid);

            return ['success' => false, 'reason' => $isLoggedUserValid['reason']];
        }

        $cardBin = $this->cardBinService->run($this->request->card->number);

        if ($cardBin['success'] === false) {
            return ['success' => false, 'reasonBin' => $cardBin];
        }

        $this->request->card->setBrand($cardBin['brand']);

        if (isset($cardBin['type'])) {
            $this->request->card->setBinType($cardBin['type']);
        }

        $isPaymentDataValid = $this->isPaymentDataValid();

        if (!$isPaymentDataValid['isValid']) {
            Logger::log('Validar dados do pagamento', $this->request, $isPaymentDataValid);

            return ['success' => false, 'errors' => $isPaymentDataValid['errors']];
        }

        // Requesting to the API and adding the proper transaction ID to WHMCS.
        $requestBody = $this->buildRequestBody($this->request);

        $response = $this->ecommerceApi->requestPayment($requestBody);

        $cardType = $this->request->card->type === 'credit' ? TransactionId::CARD_TYPE_CREDIT : TransactionId::CARD_TYPE_DEBIT;
        $installments = $this->request->card->type === 'credit' ? $this->request->installments : null;

        if (
            !isset($response->Payment->Status)
            || !in_array($response->Payment->Status, [1, 2], true)
        ) {
            // In case the $cieloReturnCode is the same, transId will also be the same and WHMCS
            // will not allow its registration. So add random_bytes() to garantee it will be unique.
            $alternativePaymentId = str_replace('x', '', bin2hex(random_bytes(3)));
            $cieloPaymentId = $response->Payment->PaymentId ?? $alternativePaymentId;
            $cieloReturnCode = $response->Payment->ReturnCode ?? $response[0]->Code;

            $whmcsTransId = TransactionId::makeFromError(
                $this->request->card->brand,
                $cardType,
                $cieloPaymentId,
                $cieloReturnCode,
                $installments
            );

            Invoice::addTrans(
                $this->request->clientId,
                $this->request->invoiceId,
                $whmcsTransId
            );

            return ['success' => false, 'reasonEcommerce' => $response];
        }

        $paymentAmountResponse = CieloAmountFormatter::fromCieloAmount($response->Payment->CapturedAmount);

        if ($this->mustApplyDiscount) {
            $addDiscountResponse = Invoice::addDiscount(
                $this->request->invoiceId,
                $this->discountService->discountAmount,
                "Aplicação de {$this->discountService->discountPercentage}% de desconto"
            );

            if (!$addDiscountResponse) {
                Invoice::addNoteToInvoice(
                    $this->request->invoiceId,
                    "Cielo 3DS: erro ao adicionar desconto de R\${$this->discountAmount} à fatura"
                );
            }
        }

        // exit;

        $whmcsTransId = TransactionId::make(
            $this->request->card->brand,
            $cardType,
            $response->Payment->PaymentId,
            $installments
        );

        $brandTaxesService = (new CalculateBrandTaxes(
            $this->request->card->type,
            $this->request->card->brand,
            $this->request->installments,
            $paymentAmountResponse
        ))->run();

        Invoice::addTrans(
            $this->request->clientId,
            $this->request->invoiceId,
            $whmcsTransId,
            $paymentAmountResponse,
            $brandTaxesService['fees'] ?? 0.0
        );

        return ['success' => true];
    }

    private function isLoggedUserValidForAuthorization(): array
    {
        // Checks if there is an authenticated client.
        if ($this->request->clientId === null) {
            return ['isValid' => false, 'reason' => 'Client is not logged in'];
        }

        $isValidInvoiceOwnwerAndStatus = Capsule::table('tblinvoices')
            ->where('id', $this->request->invoiceId)
            ->where('userid', $this->request->clientId)
            ->where('status', 'Unpaid')
            ->exists();

        if (!$isValidInvoiceOwnwerAndStatus) {
            return [
                'isValid' => false,
                'reason' => 'Logged client is not the invoice owner or invoice status is not Unpaid to be Paid.'
            ];
        }

        return ['isValid' => true];
    }

    private function isPaymentDataValid(): array
    {
        $errors = [];

        try {
            $paymentAmount = $this->request->amount;

            if (strlen($this->request->merchantOrderId) < 3) {
                $errors[] = 'Merchant Order ID inválido.';
            }

            $explodedMerchantOrderId = explode('x', $this->request->merchantOrderId);

            if (count($explodedMerchantOrderId) !== 2) {
                $errors[] = 'Merchant Order ID inválido.';
            }

            $invoiceId = (int) ($explodedMerchantOrderId[0]);
            $clientId = (int) ($explodedMerchantOrderId[1]);

            if ($clientId !== $this->request->clientId) {
                $errors[] = 'Merchant Order ID inválido.';
            }

            if ($invoiceId !== $this->request->invoiceId) {
                $errors[] = 'Merchant Order ID inválido.';
            }

            // Validates if the client exists, if the invoice exists and if client owns the invoice.
            $doesInvoiceExistsForClient = Capsule::table('tblinvoices')
                ->where('id', $invoiceId)
                ->where('userid', $clientId)
                ->exists();

            if (!$doesInvoiceExistsForClient) {
                $errors[] = 'Merchant Order ID inválido.';
            }

            if (!in_array($this->request->card->type, ['credit', 'debit'], true)) {
                $errors[] = 'Tipo de pagamento inválido.';
            }

            $isInvoiceUnpaid = Capsule::table('tblinvoices')
                ->where('id', $invoiceId)
                ->where('status', 'Unpaid')
                ->exists();

            if (!$isInvoiceUnpaid) {
                http_response_code(400);

                exit;
            }

            if (
                $this->request->card->type === 'credit' &&
                 (
                     empty($this->request->installments) ||
                     $this->request->installments < 1 ||
                     $this->request->installments > 12
                 )
            ) {
                $errors[] = 'O parcelamento deve ser de 1 a 12.';
            }

            if ($this->request->card->type === 'credit') {
                $installmentValue = $paymentAmount / $this->request->installments;
                $minInstallmentValue = Config::setting('min_installment_value');

                if ($installmentValue < $minInstallmentValue) {
                    $errors[] = 'Valor da parcela inválido.';
                }
            }

            $invoice = localAPI('GetInvoice', ['invoiceid' => $this->request->invoiceId]);

            $invoiceBalance = (float) ($invoice['balance']);

            $isPartialPaymentEnabled = Config::setting('enable_partial_payment');
            $minPartialPaymentAmount = Config::setting('partial_payment_min_amount');
            $maxPaymentValueOnPartialPayment = $invoiceBalance - $minPartialPaymentAmount;

            if ($maxPaymentValueOnPartialPayment < $minPartialPaymentAmount) {
                $maxPaymentValueOnPartialPayment = $invoiceBalance;
            }

            $debitDiscountPercentage = Config::setting('debit_discount');
            $creditDiscountPercentage = Config::setting('credit_discount');

            // Means the actual payment amount for considerating the gateway settings (considerating discounts, partial payment).
            $maxPaymentAmountMinusDiscount = 0.0;

            if ($this->request->card->type === 'debit' && $debitDiscountPercentage > 0.0) {
                $maxPaymentAmountMinusDiscount = round($invoiceBalance - ($invoiceBalance * $debitDiscountPercentage), 2);
            } elseif ($creditDiscountPercentage > 0.0) {
                $maxPaymentAmountMinusDiscount = round($invoiceBalance - ($invoiceBalance * $creditDiscountPercentage), 2);
            }

            // Make checks when partial payment is enabled and when is not.
            if ($isPartialPaymentEnabled && $this->request->clientEnabledPartialPayment) {
                if ($paymentAmount < $minPartialPaymentAmount) {
                    $errors[] = "O valor do pagamento não deve ser menor que R$ {$minPartialPaymentAmount}.";
                }

                if ($paymentAmount > $maxPaymentValueOnPartialPayment) {
                    $errors[] = "O valor do pagamento não deve ser maior que R$ {$maxPaymentValueOnPartialPayment}.";
                }

                if (
                    $invoiceBalance - $paymentAmount !== 0.0
                    && $invoiceBalance - $paymentAmount < $minPartialPaymentAmount
                ) {
                    $errors[] = "O saldo da fatura não pode ser menor que R$ $minPartialPaymentAmount.";
                }
            } else {
                if ($maxPaymentAmountMinusDiscount > 0.0) {
                    if ($this->discountService->canInvoiceReceiveDiscount()) {
                        if ($paymentAmount !== $maxPaymentAmountMinusDiscount) {
                            $errors[] = "O valor do pagamento deve ser de R$ $maxPaymentAmountMinusDiscount, pagamento com desconto incluso.";
                        } else {
                            $this->mustApplyDiscount = true;
                        }
                    } elseif ($paymentAmount !== $invoiceBalance) {
                        $errors[] = "O valor do pagamento deve ser de R$ {$invoiceBalance}, pois o desconto não é aplicável.";
                    }
                } elseif ($paymentAmount !== $invoiceBalance) {
                    $errors[] = "O valor do pagamento deve ser de R$ {$invoiceBalance}.";
                }
            }

            return ['isValid' => count($errors) === 0, 'errors' => $errors];
        } catch (Throwable $th) {
            Logger::log(
                'Dados de pagamento inválidos: ' . $th->getMessage(),
                ['request' => $this->request],
                ['msg' => $th->getMessage(), 'errors' => $errors]
            );

            return ['isValid' => false, 'errors' => $errors];
        }
    }

    /**
     * @since 1.0.0
     *
     * @return array
     */
    private function buildRequestBody(): array
    {
        $paymentType = $this->request->card->type === 'credit' ? 'CreditCard' : 'DebitCard';

        $body = [];

        $body['MerchantOrderId'] = $this->request->merchantOrderId;

        // $body['Payment']['ReturnUrl'] = Setting::getValue('SystemURL');
        $body['Payment']['ExternalAuthentication']['Cavv'] = $this->request->cavv;
        $body['Payment']['ExternalAuthentication']['Eci'] = $this->request->eci;
        $body['Payment']['ExternalAuthentication']['Version'] = $this->request->version;
        $body['Payment']['ExternalAuthentication']['ReferenceId'] = $this->request->referenceId;

        $body['Payment'][$paymentType]['CardNumber'] = $this->request->card->number;
        $body['Payment'][$paymentType]['Holder'] = $this->request->card->holder;
        $body['Payment'][$paymentType]['ExpirationDate'] = $this->request->card->expirationDate;
        $body['Payment'][$paymentType]['Brand'] = $this->request->card->brand;
        $body['Payment'][$paymentType]['SaveCard'] = $this->request->card->saveCard;
        $body['Payment'][$paymentType]['SecurityCode'] = $this->request->card->cvv;

        if ($paymentType === 'CreditCard') {
            $body['Payment']['Installments'] = $this->request->installments;
        }

        $body['Payment']['Currency'] = $this->request->currency;
        $body['Payment']['Capture'] = $this->request->capture;
        $body['Payment']['Authenticate'] = $this->request->authenticate;

        if (strlen($this->request->softDescriptor) > 0) {
            $body['Payment']['SoftDescriptor'] = $this->request->softDescriptor;
        }

        $body['Payment']['Type'] = $paymentType;

        $paymentAmount = $this->request->amount;

        $body['Payment']['Amount'] = CieloAmountFormatter::toCieloFormat($paymentAmount);

        return $body;
    }
}
