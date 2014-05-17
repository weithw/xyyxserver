<?php

namespace dao;

use ZPHP\Core\Config as ZConfig,
    ZPHP\Db\Pdo as ZPdo;
use Aura\Sql_Query\QueryFactory;
require_once(dirname(dirname(__DIR__)).'/lib/Aura.Sql/autoload.php');
class Group
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

    public function fetchOne($groupname)    
    {
        if (empty($this->query_factory))
            $this->connectDb();
        $select = $this->query_factory->newSelect();                  //Aura
        $select->cols(['groupname', 'builderID'])
               ->from('grp')
               ->where('groupname='.'"'.$groupname.'"')
               ->limit(1);
            
        $sth = $this->pdo->prepare($select->__toString());
        $sth->execute($select->getBindValues());
        $sth->setFetchMode(\PDO::FETCH_ASSOC);
        $result = $sth->fetch();

        return $result;
    }

    public function add($attr)      
    {
        if (empty($this->query_factory))
            $this->connectDb();
        $insert = $this->query_factory->newInsert();                       //Aura
        $insert->into('grp')
               ->cols([
                    'ID'=>0,
                    'groupname'=>$attr["groupname"],
                    'builderID'=>$attr["builderID"],
                ]);        
        $sth = $this->pdo->prepare($insert->__toString());
        $sth->execute($insert->getBindValues());
        $name = $insert->getLastInsertIdName('id');
        $id = $this->pdo->lastInsertId($name);
        return $id;
    }

    public function del($attr)
    {
        if (empty($this->query_factory))
            $this->connectDb();
        if ($this->fetchOne($attr["groupname"])["builderID"] != $attr["builderID"]) {
            return false;
        }
        $delete = $this->query_factory->newDelete();                       //Aura
        $delete->from('grp')
               ->where('groupname = :groupname')
               ->where('builderID = :builderID') 
               ->bindValues([                  // bind these values to the query
                    'groupname'=>$attr["groupname"],
                    'builderID'=>$attr["builderID"],
                ]);  
        $sth = $this->pdo->prepare($delete->__toString());
        $result = $sth->execute($delete->getBindValues());
        return $result;
    }
}
