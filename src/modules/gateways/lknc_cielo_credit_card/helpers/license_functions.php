<?php

/**
 * @link      https://github.com/LinkNacional/whmcs-cielo
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @since     2.0.0
 */

define('ROOTDIR', dirname(dirname(dirname(dirname(__FILE__)))));
require_once ROOTDIR . '/modules/gateways/lknc_cielo_credit_card/helpers/gateway_functions.php';

/**
 * Validates the Link Nacional license.
 *
 * @return string|bool returns true if is valid and a HTML string containing the error message.
 */
function lknc_check_license()
{
    return true;
}
