<?php

namespace WHMCS\Module\Gateway\lkncielo3ds\Helpers;

abstract class Formatter
{
    /**
     * Runs strip_tags recursively over an array.
     *
     * @since 1.1.0
     * @see https://stackoverflow.com/a/40081879/16530764
     *
     * @param array $array
     *
     * @return array
     */
    final public static function stripTagsArray(array $array): array
    {
        $data = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::stripTagsArray($value);
            } else {
                $data[$key] = trim(strip_tags($value));
            }
        }

        return $data;
    }

    /**
     * @since 1.1.0
     *
     * @param string $value
     *
     * @return string
     */
    final public static function removeNonNumber(string $value): string
    {
        return preg_replace('/[^0-9]/', '', $value);
    }

    /**
     * @since 1.1.0
     *
     * @param string $name
     *
     * @return string
     */
    final public static function normalizePersonName(string $name): string
    {
        $normalizedName = preg_replace_callback(
            '/\b(\w)(\w*)\b/',
            function ($matches) {
                return ucfirst(strtolower($matches[1])) . strtolower($matches[2]);
            },
            $name
        );

        return $normalizedName;
    }
}
