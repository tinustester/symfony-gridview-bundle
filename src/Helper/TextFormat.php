<?php

namespace Tinustester\Bundle\GridviewBundle\Helper;

class TextFormat
{
    /**
     * @param string $value
     *
     * @return string
     */
    public static function camelCaseToWord(string $value): string
    {
        return ucfirst(
            trim(
                str_replace(
                    ['_', '-', '.'],
                    ' ',
                    preg_replace('/(?<![A-Z])[A-Z]/', ' \0', $value)
                )
            )
        );
    }
}