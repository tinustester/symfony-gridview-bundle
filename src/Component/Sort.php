<?php

namespace Tinustester\Bundle\GridviewBundle\Component;

use Exception;
use Tinustester\Bundle\GridviewBundle\Gridview;
use Tinustester\Bundle\GridviewBundle\Helper\Html;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Router;
use Symfony\Component\HttpFoundation\RequestStack;
use Tinustester\Bundle\GridviewBundle\Helper\TextFormat;
use Tinustester\Bundle\GridviewBundle\Exception\SortException;

class Sort
{
    /** @var string  */
    public const ASC = 'asc';

    /** @var string  */
    public const DESC = 'desc';

    /**
     * @var bool Whether the sorting will be applied for multiple attributes.
     */
    protected bool $enableMultiSort = false;

    /**
     * @var array Attributes that will be sorted.
     * Example:
     * [
     *     'status',
     *     'name' => [
     *          Sort:ASC => ['first_name' => Sort:ASC, 'last_name' => Sort:ASC],
     *          Sort:DESC => ['first_name' => Sort:DESC, 'last_name' => Sort:DESC],
     *         'default' => Sort:DESC,
     *         'label' => 'Name',
     *     ],
     * ]
     *
     * If only attribute name was specified then default format will be used:
     * 'status' => [
     *     'asc' => ['status' => Sort:ASC],
     *     'desc' => ['status' => Sort:DESC],
     *     'default' => Sort:ASC,
     *     'label' => 'Status',
     * ]
     *
     * All properties are optional. If 'asc' or 'desc' key was not defined
     * then default values will be used.
     */
    protected array $attributes = [];

    /**
     * @var array|null Sort type of each attribute.
     */
    protected ?array $attributeOrders;

    /**
     * @var string The name of the parameter that will contain the sorting data
     * in the query string.
     */
    protected string $sortParam = 'sort';

    /**
     * @var array Default sort params. This params will be used if sort params
     * was not specified in request.
     * Example:
     * [
     *     'status' => Sort:DESC
     * ]
     */
    protected array $defaultOrder = [];

    /**
     * @var string Symbol that uses to separate sort attributes in query string.
     */
    protected string $separator = ',';

    /**
     * @var Request
     */
    protected Request $request;

    /**
     * @var Router
     */
    protected Router $router;

    /**
     * @var Html
     */
    protected Html $html;

    /**
     * Sort constructor.
     *
     * @param RequestStack $requestStack
     * @param Router $router
     */
    public function __construct(RequestStack $requestStack, Router $router)
    {
        $this->request = $requestStack->getCurrentRequest();
        $this->router = $router;
    }

    /**
     * Set attributes sort order.
     *
     * @param array $sortAttributes
     *
     * @return $this
     */
    public function setAttributes(array $sortAttributes): static
    {
        $this->attributes = $sortAttributes;
        $this->prepareSortAttributes();

        return $this;
    }

    /**
     * Prepare specified attributes sort data. If only attribute name was
     * specified then default sort params will be applied. If sort data
     * was specified it will be merged with default data in case if some of
     * required keys (asc, desc) was not specified.
     *
     * @return array
     */
    protected function prepareSortAttributes(): array
    {
        $preparedAttributes = [];

        foreach ($this->attributes as $name => $sortData) {

            $attributeName = is_array($sortData) ? $name : $sortData;

            $defaultAttributes = [
                self::ASC => [$attributeName => self::ASC],
                self::DESC => [$attributeName => self::DESC],
            ];

            if (!is_array($sortData)) {

                $preparedAttributes[$attributeName] = $defaultAttributes;
            } else {
                $preparedAttributes[$attributeName] = array_merge(
                    $defaultAttributes,
                    $sortData
                );
            }
        }

        $this->attributes = $preparedAttributes;

        return $this->attributes;
    }

    /**
     * Get list sort types of all attributes.
     *
     * @return array
     */
    public function fetchOrders(): array
    {
        $attributeOrders = $this->fetchAttributesOrder();

        $orders = [];

        foreach ($attributeOrders as $attribute => $sortType) {

            $attributeSortData = $this->attributes[$attribute];
            $relatedAttributes = $attributeSortData[$sortType];

            if (is_array($relatedAttributes)) {

                foreach ($relatedAttributes as $name => $sortDirection) {
                    $orders[$name] = $sortDirection;
                }

                continue;
            }

            $orders[] = $relatedAttributes;
        }

        return $orders;
    }

    /**
     * Get list sort types of attributes that specified in query string.
     *
     * @return array
     */
    public function fetchAttributesOrder(): array
    {
        $sortQueryParams = $this->parseSortQueryParams();

        foreach ($sortQueryParams as $attribute) {

            $sortType = self::ASC;

            if (!strncmp($attribute, '-', 1)) {
                $sortType = self::DESC;
                $attribute = substr($attribute, 1);
            }

            if (!isset($this->attributes[$attribute])) {
                continue;
            }

            $this->attributeOrders[$attribute] = $sortType;

            if (!$this->enableMultiSort) {
                return $this->attributeOrders;
            }
        }

        if (empty($this->attributeOrders) && is_array($this->defaultOrder)) {
            $this->attributeOrders = $this->defaultOrder;
        }

        return $this->attributeOrders;
    }

    /**
     * Get sort params from request query sting and convert it to array.
     * Example: string "&sort=name,-age" => array ['name', '-age']
     *
     * @return array
     */
    protected function parseSortQueryParams(): array
    {
        $queryParameters = $this->request->query->all();

        if (!isset($queryParameters[$this->sortParam])) {
            return [];
        }

        return explode($this->separator, $queryParameters[$this->sortParam]);
    }

    /**
     * Set attribute orders.
     *
     * @param array $attributeOrders
     *
     * @return Sort
     */
    public function setAttributeOrders(array $attributeOrders): static
    {
        $this->attributeOrders = [];

        foreach ($attributeOrders as $attribute => $order) {

            if (!isset($this->attributes[$attribute])) {
                continue;
            }

            $this->attributeOrders[$attribute] = $order;

            if (!$this->enableMultiSort) {
                break;
            }
        }

        return $this;
    }

    /**
     * Get sort data of certain attribute.
     *
     * @param string $attribute
     *
     * @return string|null
     */
    public function getAttributeOrder($attribute): ?string
    {
        if (!is_string($attribute)) {
            return null;
        }

        $attributesOrder = $this->fetchAttributesOrder();

        if (isset($attributesOrder[$attribute])) {
            return $attributesOrder[$attribute];
        }

        return null;
    }

    /**
     * Create a link for specified attribute with sort type in query params. If
     * label was not defined attribute name will be used.
     *
     * @param string $attribute
     * @param Gridview $gridview
     * @param array $options
     *
     * @return string
     * @throws Exception
     */
    public function createLink(string $attribute, Gridview $gridview, array $options = []): string
    {
        $sortType = $this->getAttributeOrder($attribute);

        if ($sortType) {
            $options['class'] = implode(" ", [$options['class'] ?? null, $sortType]);
            if (isset($options['class'])) {
                $options['class'] .= ' '.$sortType;
            } else {
                $options['class'] = $sortType;
            }
        }

        $options['data-sort'] = $this->createSortParam($attribute);

        if (isset($options['label'])) {
            $label = $options['label'];
            unset($options['label']);
        } else {
            $label = $this->attributes[$attribute]['label'] ?? TextFormat::camelCaseToWord($attribute);
        }

        return '<a '.$this->html->prepareTagAttributes($options) . ' href="'.$this->createUrl($attribute, $gridview).'">'.$label.'</a>';
    }

    /**
     * Creates url with sort params in query.
     *
     * @param string $attribute
     * @param Gridview $gridview
     * @param bool $absolute
     *
     * @return string
     * @throws Exception
     */
    public function createUrl(string $attribute, Gridview $gridview, bool $absolute = true): string
    {
        $parameters = $this->request->query->all();

        // Reset paging param
        $pageParamName = $gridview->getDataSource()?->getPagination()?->getPageParamName();
        if($pageParamName && isset($parameters[$pageParamName])){
            unset($parameters[$pageParamName]);
        }

        $parameters[$this->sortParam] = $this->createSortParam($attribute);

        return $this->router->generate(
            $this->request->get('_route'),
            $parameters,
            $absolute ? Router::ABSOLUTE_URL : Router::ABSOLUTE_PATH
        );
    }

    /**
     * @param string $attribute
     *
     * @return array
     * @throws SortException
     */
    protected function prepareQuerySortParams(string $attribute): array
    {
        if (!isset($this->attributes[$attribute])) {
            throw new SortException("Unknown sort attribute name: ".$attribute);
        }

        $sortData = $this->attributes[$attribute];
        $sortOrder = $this->fetchAttributesOrder();

        if (isset($sortOrder[$attribute])) {
            $sortType = $sortOrder[$attribute] === self::DESC ? self::ASC : self::DESC;
            unset($sortOrder[$attribute]);
        } else {
            $sortType = $sortData['default'] ?? self::ASC;
        }

        return $this->enableMultiSort ? array_merge([$attribute => $sortType], $sortOrder) : [$attribute => $sortType];
    }

    /**
     * Creates sorting query param from current attributes sort data.
     *
     * @param string $attribute
     *
     * @return string
     * @throws Exception
     */
    public function createSortParam(string $attribute): string
    {
        $sortOrder = $this->prepareQuerySortParams($attribute);
        $sortList = [];

        foreach ($sortOrder as $attribute => $sortType) {
            $sortList[] = $sortType === self::DESC ? '-'.$attribute : $attribute;
        }

        return implode($this->separator, $sortList);
    }

    /**
     * Check if attribute exists.
     *
     * @param string|null $attribute
     *
     * @return bool
     */
    public function hasAttribute(?string $attribute): bool
    {
        return isset($this->attributes[$attribute]);
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @param boolean $enableMultiSort
     *
     * @return $this
     */
    public function setEnableMultiSort(bool $enableMultiSort): static
    {
        $this->enableMultiSort = $enableMultiSort;
        return $this;
    }

    /**
     * @param string $sortParam
     *
     * @return $this
     * @throws SortException
     */
    public function setSortParam(string $sortParam): static
    {
        $this->sortParam = $sortParam;
        return $this;
    }

    /**
     * @param string $separator
     *
     * @return $this
     */
    public function setSeparator(string $separator): static
    {
        $this->separator = $separator;
        return $this;
    }

    /**
     * @param array $defaultOrder
     *
     * @return $this
     */
    public function setDefaultOrder(array $defaultOrder): static
    {
        $this->defaultOrder = $defaultOrder;
        return $this;
    }

    /**
     * @param Html $html
     *
     * @return $this
     */
    public function setHtml(Html $html): static
    {
        $this->html = $html;
        return $this;
    }
}