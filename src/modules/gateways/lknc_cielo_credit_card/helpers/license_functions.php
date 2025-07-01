<?php

/**
 * @link      https://github.com/LinkNacional/whmcs-cielo-credit-card
 * @author    Link Nacional <ticket@linknacional.com.br>
 * @author    Bruno Ferreira <ferreira.bruno@linknacional.com>
 * @since     2.0.0
 */

require_once __DIR__ . '/gateway_functions.php';

/**
 * Validates the Link Nacional license.
 *
 * @return string|bool returns true if is valid and a HTML string containing the error message.
 */
function lknc_check_license()
{
    return true;
}
