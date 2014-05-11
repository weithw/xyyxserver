<?php
namespace service;


class Base {

    protected $dao;

    public function fetchById($id)
    {
        return $this->dao->fetchById($id);
    }

    public function fetchOne($items)
    {
        return $this->dao->fetchOne($items);
    }

    public function fetchWhere($where)
    {
        return $this->dao->fetchWhere($where);
    }

    public function update($oldvalue,$attr)
    {
        return $this->dao->update($oldvalue,$attr);
    }

    public function add($attr)
    {
        return $this->dao->add($attr);
    }

    public function del($attr)
    {
        return $this->dao->del($attr);
    }

    public function fetchEntity($where = '1', $params = null, $fields = '*', $orderBy = null)
    {
        return $this->dao->fetchEntity($where, $params, $fields, $orderBy);
    }

    public function fetchCount($where = 1)
    {
        return $this->dao->fetchCount($where);
    }

    public function remove($id)
    {
        return $this->dao->remove($id);
    }

} 