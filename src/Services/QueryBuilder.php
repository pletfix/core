<?php

namespace Core\Services;

use Core\Services\Contracts\QueryBuilder as QueryBuilderContract;
use Aura\SqlQuery\QueryFactory;

/**
 * Adapter for the Aura's QueryFactory
 *
 * @see https://github.com/auraphp/Aura.SqlQuery Aura.SqlQuery
 */
class QueryBuilder implements QueryBuilderContract
{
    /**
     * Instances of Aura's QueryFactory.
     *
     * @var QueryFactory
     */
    private $auraFactory;

    /**
     * Create a new QueryBuilder instance.
     *
     * @param QueryFactory $auraFactory
     */
    public function __construct($auraFactory)
    {
        $this->auraFactory = $auraFactory;
    }

    /**
     * @inheritdoc
     */
    public function select()
    {
        return $this->auraFactory->newSelect();
    }

    /**
     * @inheritdoc
     */
    public function insert()
    {
        return $this->auraFactory->newInsert();
    }

    /**
     * @inheritdoc
     */
    public function update()
    {
        return $this->auraFactory->newUpdate();
    }

    /**
     * @inheritdoc
     */
    public function delete()
    {
        return $this->auraFactory->newDelete();
    }

}