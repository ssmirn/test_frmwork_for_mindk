<?php
namespace src\DbConn;

use framework\engine\registry\FrameworkRegistry; 
use framework\exceptions as FrmworkExcep;

/**
* Соединение с БД.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class DbPdo {
    
    private function __construct() {}
    
    private static $conn;
    
   /**
    * Реализуем Singleton.
    */
    public static function conn()
    {
        if (!self::$conn) {
            $dsn = FrameworkRegistry::getConnStringDB();
            if(!file_exists($dsn)){
                throw new FrmworkExcep\FileNotFoundException ('Ошибка подключения к БД "' . $dsn .  '".', DbPdo::class, __METHOD__);
            }
            self::$conn = new \PDO('sqlite:' . $dsn);
            self::$conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        }
        return self::$conn;
    }
}

