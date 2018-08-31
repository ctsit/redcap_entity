<?php

namespace REDCapEntity;

use Exception;

class EntityQuery {
    protected $entityType;
    protected $entityFactory;
    protected $conditions = [];
    protected $orderBy = 'id';
    protected $desc = true;
    protected $limit = 0;
    protected $offset = 0;
    protected $countQuery = 0;

    function __construct($entity_factory, $entity_type) {
        if (!$entity_factory->entityTypeExists($entity_type)) {
            throw new Exception('Invalid entity type.');
        }

        $this->entityType = $entity_type;
        $this->entityFactory = $entity_factory;
    }

    function condition($property, $value, $op = '=') {
        $cond = '`' . db_escape($property) .  '` ';

        if (is_array($value)) {
            $cond .= 'IN ("' . implode('", "', array_map('db_escape', $value)) . '")';
        }
        elseif ($value === null) {
            $cond = 'IS NULL';
        }
        else {
            $op = strtoupper($op);
            if (!in_array($op, ['=', '>', '<', '<=', '>=', '<>', '!=', 'LIKE'])) {
                $op = '=';
            }

            if (is_bool($value)) {
                $value = $value ? '"1"' : '"0"';
            }
            else {
                $value = '"' . db_escape($value) . '"';
            }

            $cond .= $op . ' ' . $value;
        }

        $this->conditions[$property] = $cond;
        return $this;
    }

    function orderBy($property, $desc = false) {
        $this->orderBy = $property;
        $this->desc = !empty($desc);
        return $this;
    }

    function limit($limit, $offset = 0) {
        if (intval($limit) != $limit || $limit < 0 || intval($offset) != $offset || $offset < 0) {
            return;
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
        $select = $this->countQuery ? 'COUNT(id) count' : 'id';

        $sql = 'SELECT ' . $select . ' FROM `redcap_entity_' . $entity_type . '`';
        if (!empty($this->conditions)) {
            $sql .= ' WHERE ' . implode($glue, $this->conditions);
        }

        if (!$this->countQuery) {
            $sql .= ' ORDER BY `' . db_escape($this->orderBy) . '`';
            if ($this->desc) {
                $sql .= ' DESC';
            }
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

        if ($this->countQuery) {
            $count = db_fetch_assoc($q);
            return $count['count'];
        }

        $ids = [];
        while ($result = db_fetch_assoc($q)) {
            $ids[$result['id']] = $result['id'];
        }

        if ($load_objects) {
            return $this->entityFactory->loadInstances($entity_type, $ids);
        }

        return $ids;
    }
}
