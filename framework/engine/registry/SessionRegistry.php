<?php
namespace framework\engine\registry;

use framework\engine\utils\BaseService;
use framework\exceptions as FrmworkExcep;

/**
* Реестр для хранения данных клиентского кода фраймворка.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class SessionRegistry extends Registry  
{
    private static $instance;               //@var SessionRegistry 
    private static $msgForInvalidArgExcp;   //@var string сообщение для исключений
    
    private function __construct() 
    {
        session_start();
    }

    static function getInstance() 
    {
        if ( ! isset(self::$instance) ) { 
            self::$instance = new self(); 
            self::$msgForInvalidArgExcp  = 'Неверный аргумент метода.';
        }
        return self::$instance;
    }
    
    protected function get($key)       
    { 
        if ( isset( $_SESSION[$key] ) ) {
            return $_SESSION[$key];
        }
        return null;
    }
    
    protected function set($key, $val) 
    { 
         $_SESSION[$key] = $val; 
    }

    public static function  getCache( $key ) 
    {
        if ( BaseService::isNotString($key) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            SessionRegistry::class, __METHOD__, '$key', 'string');
        }
        return self::getInstance()->get($key);
    }
    
    public static function putCache( $key, $val ) 
    {
        if ( BaseService::isNotString($key) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            SessionRegistry::class, __METHOD__, '$key', 'string');
        }
        if ( ! val ) {
            throw new FrmworkExcep\NullReferenceException ('Попытка передачи null в качестве аргумента метода.', SessionRegistry::class, __METHOD__, '$val');
        }
        self::getInstance()->set($key, $val);
    }
    
    public static function deleteByKey( $key ) 
    {
        if ( BaseService::isNotString($key) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            SessionRegistry::class, __METHOD__, '$key', 'string');
        }
        if ( isset( $_SESSION[$key] ) ) {
            unset($_SESSION[$key]);
        }
    }
    
   /**
    * Удаляем переменные сеанса, у которых имена начинаются c $prefix.
    * 
    * @param  prefix   наименование переменной.
    */
    public static function deleteByPrefix ($prefix)
    {
        $arrVarsToRemove = array (); //массив, в который копируются имена переменных сеанса, подлежащиx удалению
        foreach ( $_SESSION as $key => $val ) {
            if (BaseService::strStartsWith($key, $prefix)) {
                $arrVarsToRemove[] = $key;
            }
        }
        foreach( $arrVarsToRemove as $eachArrVarsToRemove ){
            self::deleteByKey($eachArrVarsToRemove);
        }
        return;
    }
}