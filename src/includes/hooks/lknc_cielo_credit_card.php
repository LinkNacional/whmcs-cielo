<?php

add_hook('ClientAreaPageCreditCardCheckout', 1, function ($vars) {
    define('ROOTDIR', dirname(dirname(dirname(__FILE__))));
    $templatesPath = ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/inc/templates';
    $invoiceBalance = $vars['invoice']['model']['balance'];
    $gatewayVariables = getGatewayVariables('lknc_cielo_credit_card');

    $minimumInstallmentAmount = $gatewayVariables['minimumInstallmentAmount'];
    $minimumInstallmentAmount = preg_filter('/[^,.0-9 ]/', '', $minimumInstallmentAmount);
    $minimumInstallmentAmount = str_replace(',', '.', $minimumInstallmentAmount);
    $minimumInstallmentAmount = floatval($minimumInstallmentAmount);

    $smarty = new Smarty();
    $smarty->setTemplateDir($templatesPath);
    $smarty->assign('minimumInstallmentAmount', $minimumInstallmentAmount);
    $smarty->assign('invoiceBalance', $invoiceBalance);
    $installmentField = $smarty->fetch('invoice_payment_fields.tpl');

    $fields = ['lknc_invoice_installment' => $installmentField];

    return $fields;
});
