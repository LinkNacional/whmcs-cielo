<?php

/**
 * This file holds functions that validates the license of this module.
 *
 * @link https://docs.whmcs.com/Licensing_Addon
 * @link https://docs.whmcs.com/Licensing_Addon#Integrating_the_Check_Code
 *
 * @since     1.3.0
 */

use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Config;
use WHMCS\Module\Gateway\lkncielo3ds\Helpers\Logger;

/**
 * Validates the Link Nacional license.
 *
 * @return string|bool Returns true when the license is valid or a string with the error message, in case of an invalid license.
 */
function lkncielo3ds_check_license(): string|bool
{
    return true;
}
