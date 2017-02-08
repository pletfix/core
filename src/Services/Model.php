<?php

namespace Core\Services;

use Core\Services\Contracts\Model as ModelContract;

/**
 * Object-Relational Mapping - Active Record Object for CRUD (create, read, update, delete) operations
 */
class Model implements ModelContract
{
    #
    # Relationships
    #

    /**
     * @param string $table
     * @param string $foreignKey
     * @return $this
     */
    function has(/** @noinspection PhpUnusedParameterInspection */ $table, $foreignKey = null)
    {
//        $foreignKey = $foreignKey ?: $this->table.$this->Base->fkEnding;
//        $this->tableClause .= " LEFT JOIN `$table` ON `$this->table`.`id` = `$table`.`$foreignKey`";
        return $this;
    }
    /**
     * @param string $table
     * @param string $foreignKey
     * @return $this
     */
    function belongsTo(/** @noinspection PhpUnusedParameterInspection */ $table, $foreignKey = null)
    {
//        $foreignKey = $foreignKey ?: $table.$this->Base->fkEnding;
//        $this->tableClause .= " LEFT JOIN `$table` ON `$this->table`.`$foreignKey` = `$table`.`id`";
        return $this;
    }
    /**
     * @param string $table
     * @return $this
     */
    function hasAndBelongsTo(/** @noinspection PhpUnusedParameterInspection */ $table)
    {
//        $tables = array($this->table, $table);
//        sort($tables);
//        $joinTable = join('_', $tables);
//        $aKey = $this->table.$this->Base->fkEnding;
//        $bKey = $table.$this->Base->fkEnding;
//        $this->tableClause .= "
//            LEFT JOIN `$joinTable` ON `$this->table`.`id` = `$joinTable`.`$aKey`
//            LEFT JOIN `$table` ON `$table`.`id` = `$joinTable`.`$bKey`";
        return $this;
    }


    function test()
    {
//        $db = database();
//        $articles = static::hydrate($db->query('seelct * from articles where id=?', [323]));
//
//        $article = $articles->first();
//        $article->name = 'fsdf';
//        $article->save();
//
//        $article->update(['name' => 'fdsaf']);
//        /** @noinspection PhpUndefinedFieldInspection */
//        $db->update('articles', ['name' => 'fdsaf'], 'id=?', [$articles->id]);
    }

    /**
     * Create a collection of models from plain arrays.
     *
     * @param array $items
     * @return Collection|static[]
     */
    public static function hydrate(array $items)
    {
//        $items = array_map(function ($item) {
//            return new static($item);
//        }, $items);
//
        return new Collection($items);
    }
}