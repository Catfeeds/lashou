<?php
define('DATABASE_NAME', 'lashou_test');
define('DATABASE_USER', 'root');
define('DATABASE_PASS', '');
define('DATABASE_HOST', 'localhost:3307');
require_once './pdo/class.DBPDO.php';

$pdo = new DBPDO;


//$pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);

try {
    //开启事务


    
    //提交
    $pdo->commit();
} catch(Exception $e) {
    //抓住try里面出现的错误，并且处理
    //echo $e->getMessage(); //获取异常信息
    
    //回滚
    $pdo->rollBack();
}