<?php

namespace Tinustester\Bundle\GridviewBundle\DataProvider;

use Tinustester\Bundle\GridviewBundle\Component\Sort;
use Tinustester\Bundle\GridviewBundle\Component\Pagination;

/**
 * DataProviderInterface is the interface that must be implemented by data provider classes.
 *
 * Data providers are components that sort and paginate data, and provide them to widgets.
 *
 * @author Lucas Weijers <l.weijers@nobears.nl>
 */
interface DataProviderInterface
{
    /**
     * Prepares the data models and keys.
     *
     * This method will prepare the data models and keys that can be retrieved via
     * [[getModels()]] and [[getKeys()]].
     *
     * This method will be implicitly called by [[getModels()]] and [[getKeys()]] if it has not been called before.
     *
     * @param bool $forcePrepare whether to force data preparation even if it has been done before.
     */
    public function prepare(bool $forcePrepare = false);

    /**
     * Returns the number of data models in the current page.
     * This is equivalent to `count($provider->getModels())`.
     * When [[getPagination|pagination]] is false, this is the same as [[getTotalCount|totalCount]].
     * @return int the number of data models in the current page.
     */
    public function getCount();

    /**
     * Returns the total number of data models.
     * When [[getPagination|pagination]] is false, this is the same as [[getCount|count]].
     * @return int total number of possible data models.
     */
    public function getTotalCount();

    /**
     * Returns the data models in the current page.
     * @return array the list of data models in the current page.
     */
    public function getModels();

    /**
     * Returns the key values associated with the data models.
     * @return array the list of key values corresponding to [[getModels|models]]. Each data model in [[getModels|models]]
     * is uniquely identified by the corresponding key value in this array.
     */
    public function getKeys();

    /**
     * @return Sort|false the sorting object. If this is false, it means the sorting is disabled.
     */
    public function getSort();

    /**
     * @return Pagination|false the pagination object. If this is false, it means the pagination is disabled.
     */
    public function getPagination();
}
