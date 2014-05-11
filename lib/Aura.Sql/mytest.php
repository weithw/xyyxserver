<?php
use Aura\Sql_Query\QueryFactory;
require_once('autoload.php');
$pdo = new PDO("mysql:host=127.0.0.1;dbname=zchat","root","89657415");
$query_factory = new QueryFactory('mysql');
            $select = $query_factory->newSelect();
            $select->cols(['*'])
                   ->from('user')
                   ->where('username='.'"www"')
                   ->where('password="www"');
                   var_dump($select->__toString());
            $sth = $pdo->prepare($select->__toString());
            $sth->execute($select->getBindValues());
            $result = $sth->fetch();
            var_dump($result);