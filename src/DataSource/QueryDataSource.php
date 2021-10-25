<?php

namespace Tinustester\Bundle\GridviewBundle\DataSource;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Tinustester\Bundle\GridviewBundle\Component\Sort;
use Tinustester\Bundle\GridviewBundle\Exception\DataSourceException;
use Tinustester\Bundle\GridviewBundle\Exception\PaginationException;

class QueryDataSource extends BaseDataSource
{
    /**
     * @var string
     */
    private string $rootAlias;

    /**
     * @var QueryBuilder
     */
    protected QueryBuilder $dataSource;

    /**
     * @var ServiceEntityRepository
     */
    protected ServiceEntityRepository $entityRepository;

    /**
     * @inheritdoc
     * @throws PaginationException
     */
    public function fetchEntities()
    {
        $this->pagination->setTotalCount($this->getTotalCount());
        $this->dataSource
            ->setMaxResults($this->pagination->getPageSize())
            ->setFirstResult($this->pagination->getOffset());

        $sortParams = $this->getSort()->fetchOrders();

        foreach ($sortParams as $fieldName => $sortType) {
            $this->dataSource->addOrderBy($fieldName, $sortType);
        }

        return $this->dataSource->getQuery()->getResult();
    }

    /**
     * @param QueryBuilder $queryBuilder
     *
     * @return $this
     */
    public function setDataSource(QueryBuilder $queryBuilder): static
    {
        $this->dataSource = $queryBuilder;
        return $this;
    }

    /**
     * @return QueryBuilder
     */
    public function getDataSource(): QueryBuilder
    {
        return $this->dataSource;
    }

    /**
     * @param ServiceEntityRepository $entityRepository
     *
     * @return $this
     */
    public function setEntityRepository(ServiceEntityRepository $entityRepository): static
    {
        $this->entityRepository = $entityRepository;
        return $this;
    }

    /**
     * @return ServiceEntityRepository
     */
    public function getEntityRepository(): ServiceEntityRepository
    {
        return $this->entityRepository;
    }

    /**
     * Get current instance of Sort class. If sort options was not specified
     * then default sorting params will be applied for each entity attribute.
     *
     * @return Sort
     */
    public function getSort(): Sort
    {
        $sortAttributes = $this->sort->getAttributes();

        if (!$sortAttributes) {
            $entityFields = $this->fetchEntityFields();

            $sortAttributes = [];

            foreach ($entityFields as &$fieldName) {

                $attributeName = $this->getRootAlias().'.'.$fieldName;

                $sortAttributes[$fieldName] = [
                    Sort::ASC => [$attributeName => Sort::ASC],
                    Sort::DESC => [$attributeName => Sort::DESC],
                ];
            }

            $this->sort->setAttributes($sortAttributes);
        }

        return $this->sort;
    }

    /**
     * @inheritdoc
     */
    public function fetchEntityFields()
    {
        if (!$this->entityName) {
            return [];
        }

        return $entityFields = $this->dataSource->getEntityManager()
            ->getClassMetadata($this->entityName)->getFieldNames();
    }

    /**
     * @inheritdoc
     */
    public function getTotalCount($criteria = []): int
    {
        return $this->entityRepository->count([]);
    }

    /**
     * @return string
     */
    public function getRootAlias(): string
    {
        if (!$this->rootAlias) {
            $this->rootAlias = $this->getEntityShortName();
        }

        return $this->rootAlias;
    }

    /**
     * Set alias of the main entity of QueryBuilder. If alias was not specified
     * then entity name will be used by default.
     *
     * @param string $rootAlias
     *
     * @return $this
     * @throws DataSourceException
     */
    public function setRootAlias(string $rootAlias): static
    {
        $this->rootAlias = $rootAlias;
        return $this;
    }
}