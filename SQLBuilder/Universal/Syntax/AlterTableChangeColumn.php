<?php
namespace SQLBuilder\Universal\Syntax;
use SQLBuilder\ToSqlInterface;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\MySQLDriver;
use SQLBuilder\Driver\PgSQLDriver;
use SQLBuilder\ArgumentArray;
use SQLBuilder\Universal\Traits\KeyTrait;
use SQLBuilder\Universal\Syntax\Column;
use SQLBuilder\Exception\UnsupportedDriverException;

class AlterTableChangeColumn implements ToSqlInterface
{
    protected $fromColumn;

    protected $toColumn;

    protected $after;

    protected $first;

    public function __construct($fromColumn, Column $toColumn) {
        $this->fromColumn = $fromColumn;
        $this->toColumn = $toColumn;
    }

    public function after($column) {
        if ($column instanceof Column) {
            $this->after = $column->getName();
        } else {
            $this->after = $column;
        }
        return $this;
    }

    public function first()
    {
        $this->first = true;
        return $this;
    }

    public function toSql(BaseDriver $driver, ArgumentArray $args) 
    {
        $sql = 'CHANGE COLUMN ';
        if (is_string($this->fromColumn)) {
            $sql .= $driver->quoteIdentifier($this->fromColumn);
        } elseif ($this->fromColumn instanceof Column) {
            $sql .= $driver->quoteIdentifier($this->fromColumn->getName());
        }

        // the 'toColumn' must be a type of Column, we need at least column type to rename.
        $sql .= ' ' . $driver->quoteIdentifier($this->toColumn->getName()) . ' ' . $this->toColumn->buildDefinitionSqlForModify($driver, $args);

        if ($driver instanceof MySQLDriver) {
            if ($this->after) {
                $sql .= ' AFTER ' . $driver->quoteIdentifier($this->after);
            } else if ($this->first) {
                $sql .= ' FIRST';
            }
        }
        return $sql;
    }
}
