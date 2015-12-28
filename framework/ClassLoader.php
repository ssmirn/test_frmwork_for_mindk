<?php

namespace framework;

/**
* Реализует автоматическую загрузку классов.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class ClassLoader
{
    private static $onAutoLoad     = false;
    private static $arrLoadedFiles = array();
    
    private function __construct() {}
        
    private static function register($className)
    {
        if ( !is_string($className) || strlen(trim($className)) == 0 ) { return false; }
        if ( class_exists( $className )){ return false; }
        $nameFile = '..'. DIRECTORY_SEPARATOR . str_replace('\\', DIRECTORY_SEPARATOR, $className). '.php';
        if ( ! file_exists($nameFile) ){
            return false;
        }
        if ( ! in_array($nameFile, self::$arrLoadedFiles )) {
            self::$arrLoadedFiles[] = $nameFile;
            require ($nameFile);
            return true;
        }
        return false;
    }
    
    public static function onAutoLoad()
    {
        if ( !self::$onAutoLoad ){
            spl_autoload_register(array(self::class, 'register'));
            self::$onAutoLoad = true;
        }
    }
    
    public static function offAutoLoad()
    {
        self::$onAutoLoad = false;
        self::$arrLoadedFiles = array();
        spl_autoload_unregister(array(self::class, 'register'));
    }
 
}
