<?php

namespace dao;

use ZPHP\Core\Config as ZConfig,
ZPHP\Db\Pdo as ZPdo;
use Aura\Sql_Query\QueryFactory;
require_once(dirname(dirname(__DIR__)).'/lib/Aura.Sql/autoload.php');
class User
{
    private $query_factory = null;
    private $pdo = null;
    public function connectDb()                                           //use Aura to connect database
    {
        if (empty($this->query_factory)) {
            $config = ZConfig::get('pdo');
            $this->pdo = new \PDO($config['dsn'], $config['user'], $config['pass']);
            $this->query_factory = new QueryFactory('mysql');
        }
    }

    public function fetchOne(array $items=[])    
    {
        if (empty($this->query_factory))
            $this->connectDb();
        if (1 == count($items)) {             

            $select = $this->query_factory->newSelect();                  //Aura
            foreach ($items as $key => $value) {
                $condition = $key . '="'.$value .'"';
            }
            $select->cols(['username', 'phone', 'weixin', 'intro','ID', 'sex'])              
            ->from('user')
            ->where($condition)
            ->limit(1);
            $sth = $this->pdo->prepare($select->__toString());
            $sth->execute($select->getBindValues());
            $sth->setFetchMode(\PDO::FETCH_ASSOC);
            $result = $sth->fetch();
        } else if (2 == count($items)) {
            $select = $this->query_factory->newSelect();                  //Aura
            $condition = array();
            foreach ($items as $key => $value) {
                $condition[] = $key . '="'.$value .'"';
            }
            $select->cols(['username', 'weixin', 'phone', 'intro', 'sex'])
            ->from('user')
            ->where($condition[0])
            ->where($condition[1]);
            $sth = $this->pdo->prepare($select->__toString());
            $sth->execute($select->getBindValues());
            $sth->setFetchMode(\PDO::FETCH_ASSOC);
            $result = $sth->fetch();
        }

        return $result;
    }

    public function add($attr)      
    {
        if (empty($this->query_factory))
            $this->connectDb();
        $insert = $this->query_factory->newInsert();                       //Aura
        $attr['ID'] = 0;
        $insert->into('user')
        ->cols($attr);        
        $sth = $this->pdo->prepare($insert->__toString());
        $sth->execute($insert->getBindValues());
        $name = $insert->getLastInsertIdName('ID');
        $id = $this->pdo->lastInsertId($name);
        return $id;
    }

    public function update($oldvalue, $attr)
    {
        if (empty($this->query_factory))
            $this->connectDb();
        $update = $this->query_factory->newUpdate();
        $update->table('user')                  
        ->cols($attr)
        ->where("{$oldvalue[0]} = '{$oldvalue[1]}'");

        $sth = $this->pdo->prepare($update->__toString());
        $result = $sth->execute($update->getBindValues());
        return $result;
    }
}
