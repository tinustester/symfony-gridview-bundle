<?php

namespace Tinustester\Bundle\GridviewBundle\Column;

use Tinustester\Bundle\GridviewBundle\Helper\TextFormat;
use Tinustester\Bundle\GridviewBundle\Exception\ColumnException;

class Column extends BaseColumn
{
    /**
     * @var string Entity field name. If content parameter is empty then content
     * will be taken from instance of entity by specific attribute name.
     */
    protected string $attributeName;

    /**
     * @var string
     */
    protected string $format = ColumnFormat::TEXT_FORMAT;

    /**
     * @var mixed Filter input field type. Can apply one of Symfony form input
     * types.
     * @see https://symfony.com/doc/current/forms.html#built-in-field-types
     */
    protected $filterType = null;

    /**
     * @var array List of options that will be applied to filter input field
     */
    protected array $filterFieldOptions = [];

    /**
     * Get column header cell content. If label value was specified it will be
     * used as column label. Otherwise entity attribute name will be converted
     * from camelCase to words.
     *
     * @return string
     * @throws ColumnException
     * @throws \Exception
     */
    public function getHeaderCellContent(): string
    {
        if (!$this->label && !$this->attributeName) {
            throw new ColumnException(
                'Grid column label or entity attribute name should be specified.'
            );
        }

        $label = $this->label ?: TextFormat::camelCaseToWord($this->attributeName);

        if ($this->sortable) {
            $sort = $this->gridView->getDataProvider()->getSort();

            $sortAttribute = $sort->hasAttribute($this->label) ? $label : ($sort->hasAttribute($this->attributeName)
                ? $this->attributeName : null);

            if ($sortAttribute) {
                return $sort->createLink($sortAttribute, $this->gridView, ['label' => $label]);
            }
        }

        return $label;
    }

    /**
     * Initialize column filter field. If filter entity and attribute name was
     * specified then new field will be added to filter form.
     *
     * @return bool
     */
    public function initColumnFilter(): bool
    {
        if (!$this->gridView->getFilterEntity() || !$this->attributeName) {
            return false;
        }

        $this->gridView->getFormBuilder()->add(
            $this->attributeName,
            $this->filterType,
            $this->filterFieldOptions
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function renderFilterCellContent(): string
    {
        if (!$this->gridView->getFilterEntity() || !$this->attributeName) {
            return parent::renderFilterCellContent();
        }

        return '<td ' . $this->html->prepareTagAttributes($this->filterOptions)
            . '>{{ form_widget(' . $this->gridView->getId() . '.' . $this->attributeName
            . ') }}</td>';
    }

    /**
     * Get cell content.
     *
     * @param object $entityInstance
     * @param int $index
     *
     * @return mixed|null
     * @throws ColumnException
     */
    public function getCellContent(object $entityInstance, int $index)
    {
        if (is_callable($this->content)) {
            return call_user_func_array($this->content, [$entityInstance, $index]);
        }

        $currentInstance = $entityInstance;

        if (strpos($this->attributeName, '.')) {
            $attributes = explode('.', $this->attributeName);

            foreach ($attributes as $attribute) {
                $attribute = trim($attribute);

                $currentInstance = $this->getFromArray(
                    $currentInstance,
                    $attribute
                );
            }
        } else {
            return $this->getFromArray(
                $currentInstance,
                $this->attributeName
            );
        }

        return $currentInstance;
    }

    /**
     * Get content from entity when [[attributeName]] specified as array key.
     * Example: ['user']
     *
     * @param mixed $entityInstance
     * @param string $attributeName
     *
     * @return mixed|null
     * @throws ColumnException
     */
    private function getFromArray($entityInstance, string $attributeName)
    {
        if (
            preg_match_all(
                '/(?<=[\]|\w+])\[(.*?)\]/',
                $attributeName,
                $keyMatches
            )
            && preg_match('/^(\w+)\[/', $attributeName, $attributeMatches)
        ) {
            $resultInstance = $this->getAttributeValue(
                $entityInstance,
                $attributeMatches[1]
            );

            foreach ($keyMatches[1] as $key) {
                $key = trim($key, "\",'");

                $resultInstance = $resultInstance[$key];
            }

            return $resultInstance;
        }

        return $this->getAttributeValue($entityInstance, $attributeName);
    }

    /**
     * @param object|array $entityInstance
     * @param string $attributeName
     *
     * @return mixed|null
     * @throws ColumnException
     */
    public function getAttributeValue($entityInstance, string $attributeName)
    {
        if (is_array($entityInstance)) {
            return array_key_exists($attributeName, $entityInstance) ? $entityInstance[$attributeName] : '';
        }

        if ($attributeName) {
            $entityValueGetterName = null;

            foreach (['get', 'is'] as $methodPrefix) {
                $methodName = $methodPrefix . ucfirst($attributeName);

                if (method_exists($entityInstance, $methodName)) {
                    $entityValueGetterName = $methodName;
                    break;
                }
            }

            if (!$entityValueGetterName) {
                throw new ColumnException(
                    get_class($entityInstance) . ' has no (getter) property ' . $attributeName
                );
            }

            return call_user_func([$entityInstance, $entityValueGetterName]);
        }

        return null;
    }

    /**
     * @param mixed $entityInstance
     * @param int $index
     * @param string|null $emptyCellContent
     *
     * @return string
     * @throws ColumnException
     */
    public function renderCellContent($entityInstance, int $index, $emptyCellContent = null): string
    {
        if (!is_object($entityInstance) && !is_array($entityInstance)) {
            throw new ColumnException(
                'Entity instance of grid column must be an be object or array. '
                . gettype($entityInstance) . ' given.'
            );
        }

        $cellContent = $this->getCellContent($entityInstance, $index);

        if (!is_null($cellContent)) {
            $cellContent = $this->columnFormat->format($cellContent, $this->format);
        } else {
            $cellContent = $emptyCellContent;
        }

        return '<td ' . $this->html->prepareTagAttributes($this->contentOptions) . '>' . $cellContent . '</td>';
    }

    /**
     * @param string $attributeName
     *
     * @return $this
     * @throws ColumnException
     */
    public function setAttributeName(string $attributeName): static
    {
        $this->attributeName = $attributeName;
        return $this;
    }

    /**
     * @param string $filterType
     *
     * @return $this
     */
    public function setFilterType(string $filterType): static
    {
        $this->filterType = $filterType;
        return $this;
    }

    /**
     * @param array $filterFieldOptions
     *
     * @return $this
     */
    public function setFilterFieldOptions(array $filterFieldOptions): static
    {
        $this->filterFieldOptions = array_merge($this->filterFieldOptions, $filterFieldOptions);
        return $this;
    }

    /**
     * @return string
     */
    public function getAttributeName(): string
    {
        return $this->attributeName;
    }
}