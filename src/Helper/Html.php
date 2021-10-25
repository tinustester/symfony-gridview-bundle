<?php

namespace Tinustester\Bundle\GridviewBundle\Helper;

use Tinustester\Bundle\GridviewBundle\Exception\HtmlException;

class Html
{
    /** @var string  */
    public const CLASS_ATTR = 'class';

    /** @var string  */
    public const STYLE_ATTR = 'style';

    /** @var string  */
    public const DATA_ATTR = 'data';

    /**
     * @var array List of [data] type attributes
     */
    public array $dataAttributes = ['data', 'data-ng', 'ng'];

    /**
     * Converts list of tag attributes from array to encoded string
     * representation.
     *
     * @param array $attributes
     *
     * @return string
     */
    public function prepareTagAttributes(array $attributes): string
    {
        $preparedHtml = '';

        foreach ($attributes as $attributeName => $attributeData) {
            $preparedHtml .= $this->prepareTagAttribute(
                $attributeName,
                $attributeData
            );
        }

        return trim($preparedHtml);
    }

    /**
     * Prepare certain type of attributes.
     *
     * @param string $attributeName
     * @param mixed $attributeData
     *
     * @return mixed|string
     */
    protected function prepareTagAttribute(string $attributeName, $attributeData)
    {
        if (is_bool($attributeData)) {
            return $attributeName . ' ';
        }

        $preparedAttribute = '';

        if (is_array($attributeData)) {
            $attributeType = $this->guessAttributeType($attributeName);

            $prepareMethod = 'prepare' . ucfirst($attributeType) . 'Attribute';

            if (!method_exists($this, $prepareMethod)) {
                $preparedAttribute .= " $attributeName='" . $this->jsonEncode($attributeData) . "' ";

                return $preparedAttribute;
            }

            return call_user_func_array(
                [$this, $prepareMethod], [$attributeName, $attributeData]
            );
        }

        $preparedAttribute .= " $attributeName=" . json_encode($attributeData);

        return $preparedAttribute;
    }

    /**
     * Get attribute type.
     *
     * @param string $attributeName
     *
     * @return bool|string
     */
    protected function guessAttributeType(string $attributeName): bool|string
    {
        if (in_array($attributeName, $this->dataAttributes)) {
            return self::DATA_ATTR;
        }

        if (in_array($attributeName, [self::CLASS_ATTR, self::STYLE_ATTR])) {
            return $attributeName;
        }

        return false;
    }

    /**
     * Creates string representation of class attribute from array.
     *
     * @param string $attributeName
     * @param array $attributeData
     *
     * @return string
     * @throws HtmlException
     */
    protected function prepareClassAttribute(string $attributeName, array $attributeData): string
    {
        return $attributeName . "=" . $this->jsonEncode(implode(' ', $attributeData));
    }

    /**
     * Creates string representation of data attribute from array.
     *
     * @param string $attributeName
     * @param array $attributeData
     *
     * @return string
     * @throws HtmlException
     */
    protected function prepareDataAttribute(string $attributeName, array $attributeData): string
    {
        $preparedAttribute = '';

        foreach ($attributeData as $dataName => $dataValue) {
            if (!is_string($dataName) && !is_numeric($dataName)) {
                throw new HtmlException(
                    'Unexpected type of the data attribute name. String or '
                    . 'numeric expected. ' . gettype($dataName) . ' given.'
                );
            }

            $preparedAttribute .= " " . $attributeName . "-" . $dataName . "=";
            $preparedAttribute .= is_array($dataValue) ? json_encode($dataValue) : $this->jsonEncode($dataValue);
        }

        return $preparedAttribute;
    }

    /**
     * Creates string representation of style attribute from array.
     *
     * @param string $attributeName
     * @param array $attributeData
     *
     * @return string
     * @throws HtmlException
     */
    protected function prepareStyleAttribute(string $attributeName, array $attributeData): string
    {
        $preparedAttribute = " " . $attributeName . "=";
        $styles = [];

        foreach ($attributeData as $styleName => $styleValue) {
            if (!is_string($styleName) || !is_string($styleValue)) {
                throw new HtmlException(
                    'The expected type of the style name and style value is a '
                    . 'string. ' . gettype($attributeName) . ' given.'
                );
            }

            $styles[] = $styleName . ":" . $styleValue;
        }

        $preparedAttribute .= $this->jsonEncode(implode('; ', $styles));

        return $preparedAttribute;
    }

    /**
     * Encodes data for using as html attribute value.
     *
     * @param mixed $data
     *
     * @return string
     */
    public function jsonEncode($data): string
    {
        return json_encode(
            $data,
            JSON_UNESCAPED_UNICODE
            | JSON_HEX_QUOT
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
        );
    }
}