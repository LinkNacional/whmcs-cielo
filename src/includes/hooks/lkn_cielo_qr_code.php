<?php
/**
 * @link      https://github.com/LinkNacional/whmcs-cielo
 * @link      https://developers.whmcs.com/payment-gateways/third-party-gateway/
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @since     1.0.0
 */

define('ROOTDIR', dirname(dirname(dirname(__FILE__))));
require_once ROOTDIR . '/modules/gateways/lkn_cielo_qr_code/func/gateway_functions.php';

/**
 * @param  array $transac
 *
 * @return void
 */
function lkn_cielo_qr_code_check_payment_by_transac($transac) {
    if ($transac['gateway'] === lkn_cielo_qr_code_gateway_name()) {
        $paymentId = substr($transac['transid'], 7);
        $isTransactionPaid = lkn_cielo_qr_code_is_qr_code_paid($paymentId);

        if ($isTransactionPaid['isPaid']) {
            $cieloPaidAmount = $isTransactionPaid['cieloResponse']['Payment']['Amount'];
            $paidAmount = lkn_cielo_qr_code_format_amount_from_cielo_to_whmcs($cieloPaidAmount);

            $userId = $transac['userid'];
            $fees = lkn_cielo_qr_code_get_fees($paidAmount, $transac['invoiceid']);

            $apiResponse = localAPI('AddTransaction', [
                'userid' => $userId,
                'invoiceid' => $transac['invoiceid'],
                'transid' => 'PAGO.' . $paymentId,
                'amountin' => $paidAmount,
                'fees' => $fees,
                'date' => date('d/m/Y'),
                'paymentmethod' => lkn_cielo_qr_code_gateway_name(),
                'description' => 'QR Code CIELO',
            ]);

            lkn_cielo_qr_code_log_transac('QR Code pago para a fatura #' . $transac['invoiceid'], $isTransactionPaid['cieloResponse']);
        }
    }
}

/**
 * @param  int $invoiceId
 *
 * @return array
 */
function lkn_cielo_qr_code_get_last_transac($invoiceId) {
    $invoiceTransactions = localAPI(
        'GetTransactions',
        ['invoiceid' => $invoiceId]
    )['transactions']['transaction'];

    return end($invoiceTransactions);
}

/**
 * @param array $invoices
 *
 * @return array
 */
function lkn_cielo_qr_code_filter_for_qr_code($invoices) {
    $gatewayName = lkn_cielo_qr_code_gateway_name();


    if(empty($invoices)) {
        return [];
    }

    return array_filter(
        $invoices,
        function ($invoice) use ($gatewayName) {
            return $invoice['paymentmethod'] === $gatewayName;
        }
    );
}

//  Link: https://developers.whmcs.com/hooks-reference/cron/
add_hook('AfterCronJob', 1, function ($vars) {
    try {
        $invoices = localAPI('GetInvoices', [
            'status' => 'Unpaid',
            'orderby' => 'id',
            'order' => 'desc',
            'limitnum' => '25',
        ])['invoices']['invoice'];

        $invoicesForQrCode = lkn_cielo_qr_code_filter_for_qr_code($invoices);

        array_walk($invoicesForQrCode, function ($invoice) {
            $invoiceId = $invoice['id'];
            $invoiceLastTransac = lkn_cielo_qr_code_get_last_transac($invoiceId);

            lkn_cielo_qr_code_check_payment_by_transac($invoiceLastTransac);
        });
    } catch (Exception $e) {
        lkn_cielo_qr_code_log_transac('Erro ao rodar AfterCronJob', $e->getMessage());
    }
});

add_hook('DailyCronJob', 1, function ($vars) {
    try {
        $invoicesPerPage = 25;

        $getInvoices = function ($invoiceQueryOffset = 0) use ($invoicesPerPage) {
            return localAPI('GetInvoices', [
                'status' => 'Unpaid',
                'orderby' => 'id',
                'order' => 'desc',
                'limitstart' => $invoiceQueryOffset,
                'limitnum' => $invoicesPerPage,
            ]);
        };

        $invoiceQueryOffset = 0;

        $localApiGetInvoices = $getInvoices();

        $totalPages = intval(ceil($localApiGetInvoices['totalresults'] / $invoicesPerPage));
        $currentPage = 1;

        $checkQrCodePayment = function ($invoicesForQrCode) {
            if ($invoicesForQrCode === []) {
                return;
            }

            array_walk($invoicesForQrCode, function ($invoice) {
                $invoiceId = $invoice['id'];
                $invoiceLastTransac = lkn_cielo_qr_code_get_last_transac($invoiceId);

                lkn_cielo_qr_code_check_payment_by_transac($invoiceLastTransac);
            });
        };

        while ($currentPage <= $totalPages) {
            $invoices = $localApiGetInvoices['invoices']['invoice'];
            // $invoices = $localApiGetInvoices;

            $invoicesForQrCode = lkn_cielo_qr_code_filter_for_qr_code($invoices);
            $checkQrCodePayment($invoicesForQrCode);

            // Executes new localAPI for more invoices.
            $currentPage++;

            $invoiceQueryOffset = ($currentPage * $invoicesPerPage) - $invoicesPerPage;
            $localApiGetInvoices = $getInvoices($invoiceQueryOffset);
        }
    } catch (Exception $e) {
        lkn_cielo_qr_code_log_transac('Erro ao rodar DailyCronJob', $e->getMessage());
    }
});
