<?php

namespace Tinustester\Bundle\GridviewBundle\Column;

use DateInterval;
use DateTime;
use Exception;

/**
 * ColumnFormat used by [[Column]] class to prepare cell data before render.
 * This class format data with twig native functions.
 *
 * It contains five available formats:
 *  html - used by default. Escapes data for the "html" context;
 *  raw  - data will be rendered without any escaping;
 *  twig - escapes twig syntax to render it as plain text;
 *  date - prepare data to datetime representation with specified format.
 */
class ColumnFormat
{
    /** @var string  */
    public const TEXT_FORMAT = 'html';

    /** @var string  */
    public const RAW_FORMAT = 'raw';

    /** @var string  */
    public const TWIG_FORMAT = 'twig';

    /** @var string  */
    public const DATE_FORMAT = 'date';

    /**
     * @var string Default date format. Used in case if [[data]] is instance of
     * [[\DateTime]] or [[\DateInterval]] and date format was not specified.
     */
    private string $defaultDateFormat = 'Y-m-d H:i:s';

    /**
     * Escape data with certain escape format.
     *
     * @param string|object $data
     * @param string|array $format
     *
     * @return string
     */
    public function format($data, $format): string
    {
        if (
            is_object($data)
            && !($data instanceof DateTime)
            && !($data instanceof DateInterval)
        ) {
            throw new \InvalidArgumentException(
                'Only to '. DateTime::class.' and '. DateInterval::class
                .' instances grid column format can be applied. '
                .gettype($data).' given.'
            );
        }

        if (!is_string($format) && !is_array($format)) {
            throw new \InvalidArgumentException(
                'Invalid column data format type. String or array expected. '
                .gettype($format).' given.'
            );
        }

        $format = $this->normalizeCurrentFormat($data, $format);

        $formatMethodName = $this->getFormatMethodName($format);

        if (is_array($format)) {
            return call_user_func_array(
                [$this, $formatMethodName],
                [$data, current($format)]
            );
        }

        return call_user_func([$this, $formatMethodName], $data);
    }

    /**
     * Define current format depends on data type.
     *
     * @param mixed $data
     * @param string|array $format
     *
     * @return string|array
     */
    protected function normalizeCurrentFormat($data, $format): array|string
    {
        if (($data instanceof DateTime) || ($data instanceof DateInterval)) {
            if (!is_array($format) || key($format) !== self::DATE_FORMAT) {
                return [self::DATE_FORMAT => $this->defaultDateFormat];
            }
        }

        return $format;
    }

    /**
     * Fetch certain format method name depends on specified format.
     *
     * @param string|array $format
     *
     * @return string
     * @throws \RuntimeException
     */
    protected function getFormatMethodName($format): string
    {
        $formatName = is_array($format) ? key($format) : $format;

        $formatMethodName = $formatName.'Format';

        if (!method_exists($this, $formatMethodName)) {
            throw new \RuntimeException('Unknown column format: '.$formatName);
        }

        return $formatMethodName;
    }

    /**
     * Convert data to specified date format. $dateTime parameter can contain
     * timestamp or instance of \DateInterval or \DateTime classes.
     *
     * @param string|int|DateInterval|DateTime $dateTime
     * @param array|string $format
     *
     * @return string
     * @throws Exception
     */
    protected function dateFormat($dateTime, array|string $format): string
    {
        if (is_string($dateTime) || is_numeric($dateTime)) {
            return "{{ '".$dateTime."'|date('".$format."') }}";
        }

        if (!($dateTime instanceof DateInterval) && !($dateTime instanceof DateTime)) {
            throw new Exception(
                'Invalid date instance. Expected '. DateTime::class
                .' or '. DateInterval::class.' instances.'
            );
        }

        return $dateTime->format($format);
    }

    /**
     * Escapes a string for the HTML context.
     *
     * @param string $data
     *
     * @return string
     */
    protected function htmlFormat(string $data): string
    {
        return "{{ '".addslashes($data)."'|escape('html') }}";
    }

    /**
     * Returns data without any escape.
     *
     * @param string $data
     *
     * @return string
     */
    protected function rawFormat($data): string
    {
        return $data;
    }

    /**
     * Escape twig string before render.
     *
     * @param string $data
     *
     * @return string
     */
    protected function twigFormat(string $data): string
    {
        return "{% verbatim %}".$data."{% endverbatim %}";
    }
}