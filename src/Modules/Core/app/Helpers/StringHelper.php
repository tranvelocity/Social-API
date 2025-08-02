<?php

declare(strict_types=1);

namespace Modules\Core\app\Helpers;

class StringHelper
{
    /**
     * Converts escaped characters to their corresponding Unicode characters.
     *
     * This method takes a string containing escaped Unicode characters in the form \uXXXX
     * and converts each escaped character to its corresponding Unicode character.
     *
     * @param string $string The input string containing escaped Unicode characters.
     * @return string The string with escaped Unicode characters replaced by their corresponding Unicode characters.
     */
    public static function convertToUnicodeCharacters(string $string): string
    {
        // Replace the escaped characters with their corresponding Unicode characters if exists
        $string = preg_replace_callback('/\\\\u([0-9a-fA-F]{4})/', function ($match) {
            return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UTF-16BE');
        }, $string);

        // Remove the unnecessary backslashes and quotes
        return str_replace(['\\"', '\\'], ['"', ''], $string);
    }
}
