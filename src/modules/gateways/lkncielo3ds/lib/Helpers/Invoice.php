<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Helpers;

/**
 * @since 1.0.0
 */
final class Invoice
{
    /**
     * @since 1.0.0
     *
     * @param int    $clientId
     * @param int    $invoiceId
     * @param string $transacId
     * @param float  $paymentValue
     * @param float  $fees
     * @param string $description
     *
     * @return array
     */
    public static function addTrans(
        int $clientId,
        int $invoiceId,
        string $transacId,
        float $paymentValue = 0.0,
        float $fees = 0.0,
        string $description = ''
    ): array {
        $date = date('d/m/Y');
        $paymentMethod = Config::constant('name');
        $data = [
            'paymentmethod' => $paymentMethod,
            'userid' => $clientId,
            'transid' => $transacId,
            'invoiceid' => $invoiceId,
            'date' => $date,
            'description' => $description,
            'fees' => $fees,
            'amountin' => $paymentValue
        ];

        $response = localAPI('AddTransaction', $data);

        Logger::log('Adicionar transação', $data, $response);

        return $response;
    }

    public static function addDiscount(
        int $invoiceId,
        float $value,
        string $description
    ): bool {
        $postData = [
            'invoiceid' => $invoiceId,
            'newitemdescription' => [$description],
            'newitemamount' => [$value * -1]
        ];

        $response = localAPI('UpdateInvoice', $postData);

        Logger::log('Adicionar desconto', $postData, $response);

        return $response['result'] === 'success';
    }

    public static function addNoteToInvoice(int $invoiceId, string $note): void
    {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);

        $notes = trim($invoice['notes'] . "\n" . $note);

        $updateInvoiceResponse = localAPI(
            'UpdateInvoice',
            ['invoiceid' => $invoiceId, 'notes' => $notes]
        );

        Logger::log(
            'Adicionar nota em fatura',
            ['invoiceId' => $invoiceId, 'note' => $note],
            ['GetInvoice' => $invoice, 'UpdateInvoice' => $updateInvoiceResponse]
        );
    }

    public static function getBalance(int $invoiceId): float
    {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);

        return $invoice['balance'];
    }

    public static function getTrans(int $invoiceId): array
    {
        $invoice = localAPI('GetInvoice', ['invoiceid' => $invoiceId]);

        return is_array($invoice['transactions']) ? $invoice['transactions'] : [];
    }

    /**
     * @since 1.1.0
     *
     * @param int $invoiceId
     *
     * @return array Returns the invoice.
     */
    public static function get(int $invoiceId): array
    {
        return localAPI('GetInvoice', ['invoiceid' => $invoiceId]);
    }

    /**
     * Checks if the invoice has reached the maximum number of payment attempts for 3DS.
     *
     * @param int $invoiceId
     *
     * @return bool
     */
    public static function hasReachedMaxAttempts3ds(int $invoiceId): bool
    {
        $maximumPaymentAttempts = Config::setting('maximumPaymentAttempts3ds') ?? 0;

        if ($maximumPaymentAttempts === 0 || $maximumPaymentAttempts === '0') {
            return false;
        }

        $invoiceTransactions = localAPI('GetTransactions', ['invoiceid' => $invoiceId]);

        if (!isset($invoiceTransactions['transactions']['transaction'])) {
            return false;
        }

        $transactions = $invoiceTransactions['transactions']['transaction'];

        // Ensure it's an array
        if (!is_array($transactions)) {
            $transactions = [$transactions];
        }

        $gatewayName = Config::constant('name');
        $invoiceTransactionsForGateway = array_filter($transactions, function ($transaction) use ($gatewayName) {
            return $transaction['gateway'] === $gatewayName;
        });

        $invoiceTransactionsForGateway = array_reverse($invoiceTransactionsForGateway);

        $invalidTransactions = 0;

        for ($index = 0; $index < $maximumPaymentAttempts; $index++) {
            if (!isset($invoiceTransactionsForGateway[$index])) {
                break;
            }

            $transaction = $invoiceTransactionsForGateway[$index];
            $transactionIdParts = explode('x', $transaction['transid']);

            // Failed transactions have 'ERRO' in the transid
            if (in_array('ERRO', $transactionIdParts)) {
                $invalidTransactions++;
            }
        }

        return $invalidTransactions >= $maximumPaymentAttempts;
    }

    /**
     * Checks if the payment attempt should be blocked for 3DS.
     *
     * @param int $invoiceId
     *
     * @return bool
     */
    public static function mustBlockAttempt3ds(int $invoiceId): bool
    {
        if (self::hasReachedMaxAttempts3ds($invoiceId)) {
            return true;
        }

        // Additional checks can be added here if needed

        return false;
    }
}
