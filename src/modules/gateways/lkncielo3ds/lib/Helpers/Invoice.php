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
     * @see https://developers.whmcs.com/api-reference/getinvoice/
     *
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
}
