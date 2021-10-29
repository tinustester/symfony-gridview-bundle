<?php

namespace Tinustester\Bundle\GridviewBundle\DataProvider;

use Tinustester\Bundle\GridviewBundle\Component\Sort;
use Tinustester\Bundle\GridviewBundle\Component\Pagination;
use Tinustester\Bundle\GridviewBundle\Exception\DataProviderException;

abstract class BaseDataProvider
{
    /**
     * @var string Full class name of target entity.
     */
    protected string $entityName;

    /**
     * @var mixed
     */
    protected \Doctrine\ORM\QueryBuilder $dataProvider;

    /**
     * @var Pagination
     */
    protected Pagination $pagination;

    /**
     * @var Sort
     */
    protected Sort $sort;

    /**
     * @param Pagination $pagination
     *
     * @return $this
     */
    public function setPagination(Pagination $pagination): static
    {
        $this->pagination = $pagination;
        return $this;
    }

    /**
     * @param Sort $sort
     *
     * @return BaseDataProvider
     */
    public function setSort(Sort $sort): static
    {
        $this->sort = $sort;
        return $this;
    }

    /**
     * @return Pagination
     */
    public function getPagination(): Pagination
    {
        return $this->pagination;
    }

    /**
     * @return Sort
     */
    public function getSort(): Sort
    {
        return $this->sort;
    }

    /**
     * Get total count of entities.
     *
     * @return int
     */
    public function getTotalCount(): int
    {
        return count($this->dataProvider);
    }

    /**
     * @param string $entityName
     *
     * @return $this
     * @throws DataProviderException
     */
    public function setEntityName(string $entityName): static
    {
        $this->entityName = $entityName;
        return $this;
    }

    /**
     * Get short class name.
     *
     * @return bool|string
     */
    public function getEntityShortName(): bool|string
    {
        if (!$this->entityName || !is_string($this->entityName)) {
            return false;
        }

        return substr(
            $this->entityName,
            strrpos($this->entityName, '\\') + 1
        );
    }

    /**
     * Returns list of entity attributes.
     *
     * @return mixed
     */
    abstract public function fetchEntityFields();

    /**
     * Returns prepared set of entities.
     *
     * @return mixed
     */
    abstract public function fetchEntities();
}