<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Helpers;

/**
 * Provides helper methods to access the modules settings.
 *
 * @since 1.0.0
 */
final class Config
{
    /**
     * Returns a constant defined in configs.php.
     *
     * @since 1.0.0
     *
     * @param string $constant
     *
     * @return mixed
     */
    final public static function constant(string $constant): mixed
    {
        $constants = require __DIR__ . '/../configs.php';

        return self::getArrayKeyValue($constants, $constant);
    }

    /**
     * Returns a module's constants according to the current env, defined in config.php.
     *
     * @since 1.0.0
     *
     * @param string $name
     *
     * @return mixed
     */
    final public static function env(string $varName): mixed
    {
        $constants = require __DIR__ . '/../configs.php';

        $env = self::setting('env');

        return self::getArrayKeyValue($constants, "$env.$varName");
    }

    /**
     * Returns a module's setting, defined in _config().
     *
     * @since 1.0.0
     *
     * @param string $name
     *
     * @return mixed
     */
    final public static function setting(string $name): mixed
    {
        try {
            $settings = getGatewayVariables(self::constant('name'));

            return self::parseConfig($name, $settings[$name]);
        } catch (\Throwable $th) {
            return null;
        }
    }

    /**
     * @since 1.0.0
     *
     * @param string $name
     * @param mixed  $value
     *
     * @return mixed
     */
    private static function parseConfig(string $name, mixed $value): mixed
    {
        return match ($name) {
            'api_soft_descriptor' => substr(preg_replace("/[^a-zA-Z0-9\s]/", '', $value), 0, 13),
            'min_installment_value' => self::toFloatFromBrazillianDecimal($value),
            'debit_discount' => self::toPercentageFromBrazillianDecimal($value),
            'credit_discount' => self::toPercentageFromBrazillianDecimal($value),
            'partial_payment_min_amount' => self::toFloatFromBrazillianDecimal($value),
            'calculate_brand_taxes' => (bool) $value,
            'enable_partial_payment' => (bool) $value,
            'enable_credit_card_installments' => (bool) $value,
            default => trim($value)
        };
    }

    private static function toFloatFromBrazillianDecimal(?string $value): float
    {
        if (is_null($value) || (float) $value === 0.0) {
            return 0.0;
        }

        return round((float) (str_replace(',', '.', preg_replace('/[^0-9,.]/', '', $value))), 2);
    }

    private static function toPercentageFromBrazillianDecimal(?string $value): float
    {
        if (is_null($value) || (float) $value === 0.0) {
            return 0.0;
        }

        return round(self::toFloatFromBrazillianDecimal($value) / 100, 2);
    }

    /**
     * @since 1.0.0
     *
     * @param array  $array
     * @param string $keys  can be a key1.subkey1.subkey2.
     *
     * @return mixed
     */
    private static function getArrayKeyValue(array $array, string $keys): mixed
    {
        $keys = explode('.', $keys);

        foreach ($keys as $key) {
            if (is_array($array) && array_key_exists($key, $array)) {
                $array = $array[$key];
            }
        }

        return $array;
    }
}
