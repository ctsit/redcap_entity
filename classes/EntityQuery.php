<?php

namespace REDCapEntity;

use Exception;

class EntityQuery {
    protected $entityType;
    protected $entityFactory;
    protected $joins = [];
    protected $fields = ['e.id'];
    protected $orderBy = [];
    protected $limit = 0;
    protected $offset = 0;
    protected $countQuery = 0;
    protected $rawResults;

    function __construct($entity_factory, $entity_type) {
        if (!$entity_factory->getEntityTypeInfo($entity_type)) {
            throw new Exception('Invalid entity type.');
        }

        $this->entityType = $entity_type;
        $this->entityFactory = $entity_factory;
    }

    function addField($expr, $alias) {
        $this->_checkAlias($alias);
        $this->fields[] = $expr . ' ' . $alias;
        return $this;
    }

    function condition($expr, $value, $op = '=') {
        $cond = $expr . ' ';

        if (is_array($value)) {
            $cond .= 'IN ("' . implode('", "', array_map('db_escape', $value)) . '")';
        }
        elseif ($value === null) {
            $cond .= 'IS NULL';
        }
        else {
            if (is_bool($value)) {
                $value = $value ? '"1"' : '"0"';
            }
            else {
                $value = '"' . db_escape($value) . '"';
            }

            if (!in_array(strtoupper($op), ['=', '>', '<', '<=', '>=', '<>', '!=', 'LIKE'])) {
                throw new Exception('Invalid operator.');
            }

            $cond .= $op . ' ' . $value;
        }

        $this->conditions[] = $cond;
        return $this;
    }

    function join($table, $alias, $expr, $type = 'INNER') {
        if (!in_array(strtoupper($type), ['INNER', 'LEFT', 'RIGHT', 'OUTER FULL'])) {
            throw new Exception('Invalid join type.');
        }

        $this->_checkAlias($alias);
        $this->_checkAlias($table, 'Invalid table.');

        $this->joins[] = $type . ' JOIN ' . $table . ' ' . $alias . ' ON ' . $expr;
        return $this;
    }

    function orderBy($expr, $desc = false) {
        $this->orderBy[] = $expr . (empty($desc) ? '' : ' DESC');
        return $this;
    }

    function limit($limit, $offset = 0) {
        if (intval($limit) != $limit || $limit < 0) {
            throw new Exception('Invalid limit.');
        }

        if (intval($offset) != $offset || $offset < 0) {
            throw new Exception('Invalid offset.');
        }

        $this->limit = $limit;
        $this->offset = $offset;
        return $this;
    }

    function countQuery() {
        $this->countQuery = true;
        return $this;
    }

    function execute($load_objects = true, $require_all_conds = true) {
        $glue = $require_all_conds ? ' AND ' : ' OR ';
        $entity_type = db_escape($this->entityType);

        $select =  $this->countQuery ? 'COUNT(e.id) __count' : implode(', ', $this->fields);
        $sql = 'SELECT ' . $select . ' FROM redcap_entity_' . $entity_type . ' e ' . implode(' ', $this->joins);

        if (!empty($this->conditions)) {
            $sql .= ' WHERE ' . implode($glue, $this->conditions);
        }

        if (!$this->countQuery && !empty($this->orderBy)) {
            $sql .= ' ORDER BY ' . implode(', ', $this->orderBy);
        }

        if ($this->limit) {
            $sql .= ' LIMIT ' . $this->limit;

            if ($this->offset) {
                $sql .= ' OFFSET ' . $this->offset;
            }
        }

        if (!$q = db_query($sql)) {
            return false;
        }

        $this->rawResults = [];

        if ($this->countQuery) {
            $count = db_fetch_assoc($q);
            return $count['__count'];
        }

        $ids = [];
        while ($result = db_fetch_assoc($q)) {
            $this->rawResults[$result['id']] = $result;
            $ids[$result['id']] = $result['id'];
        }

        if ($load_objects) {
            return $this->entityFactory->loadInstances($entity_type, $ids);
        }

        return $ids;
    }

    function getRawResults() {
        return $this->rawResults;
    }

    protected function _checkAlias($alias, $msg = 'Invalid alias.') {
        if (preg_match('/[^a-z0-9_]+/', $alias)) {
            throw new Exception($msg);
        }
    }
}
