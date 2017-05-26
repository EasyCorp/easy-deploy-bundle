<?php

/*
 * This file is part of the EasyDeploy project.
 *
 * (c) Javier Eguiluz <javier.eguiluz@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace EasyCorp\Bundle\EasyDeployBundle\Helper;

/**
 * This helper class encapsulates common string operations not available in a
 * user-friendly way in PHP and other operations specific to the application.
 */
class Str
{
    public static function startsWith(string $haystack, string $needle): bool
    {
        return '' !== $needle && 0 === mb_strpos($haystack, $needle);
    }

    public static function endsWith(string $haystack, string $needle): bool
    {
        return $needle === mb_substr($haystack, -mb_strlen($needle));
    }

    public static function contains(string $haystack, string $needle): bool
    {
        return '' !== $needle && false !== mb_strpos($haystack, $needle);
    }

    public static function lineSeparator(string $char = '-'): string
    {
        return str_repeat($char, 80);
    }

    public static function prefix($text, string $prefix): string
    {
        $text = is_array($text) ? $text : explode(PHP_EOL, $text);

        return implode(PHP_EOL, array_map(function ($line) use ($prefix) {
            return $prefix.$line;
        }, $text));
    }

    public static function stringify($value): string
    {
        if (is_resource($value)) {
            return 'PHP Resource';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        if (is_object($value) && !method_exists($value, '__toString')) {
            return json_encode($value, JSON_UNESCAPED_SLASHES);
        }

        return (string) $value;
    }

    public static function formatAsTable(array $data, bool $sortKeys = true): string
    {
        if ($sortKeys) {
            ksort($data);
        }

        $arrayAsString = '';
        $longestArrayKeyLength = max(array_map('strlen', array_keys($data)));
        foreach ($data as $key => $value) {
            $arrayAsString .= sprintf("%s%s : %s\n", $key, str_repeat(' ', $longestArrayKeyLength - mb_strlen($key)), self::stringify($value));
        }

        return rtrim($arrayAsString);
    }
}
