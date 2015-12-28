<?php
namespace framework\engine\registry;

use framework\engine\routing\Routing;
use framework\engine\routing\RouteMapManager;
use framework\engine\Request;
use framework\controllers\ControllerBase;
use framework\engine\utils\BaseService;
use framework\exceptions as FrmworkExcep;

/**
* Реестр для хранения cлужебных данных фреймворка, в т.ч. таблицы маршрутизации.
* Используется сериализация (framework\app\data\temp\*.*)
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class FrameworkRegistry extends Registry 
{
    private static $instance;  //@var object FrameworkRegistry 
    private $routing = null;   //@var object Routing 
    private static $tmpDir;    //@var string путь к temp папке для хранения кешируемых данных  
    private $values = array(); //@var array массив для хранения данных 
    private $timeStampsLastEditValues = array();//@var array массив для хранения TimeStamp-ов моментов сохранения в $values
    private static $msgForInvalidArgExcp;       //@var string сообщение для исключений

    private function __construct() { }

   /**
    * Реализуем Singleton.
    */
    public static function getInstance() 
    {
        if (!isset(self::$instance) ) { 
            self::$instance = new self(); 
            self::$tmpDir  = '..' . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 'app' .
                             DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'temp' ;
            self::$msgForInvalidArgExcp  = 'Неверный аргумент метода.';
        }
        return self::$instance;
    }
    
    protected function get($key)       { return (isset($this->values[$key])) ? $this->values[$key] : null; }
    protected function set($key, $val) { $this->values[$key] = $val; }

    /**
    * Возвращаем значение из массива $values с возможностью сериализации.
    * Если время сохранения значения во временном файле больше TimeStamp-а сохранения в памяти,
    * (т.е. файл содержит более актуальные данные) то значение считывается из временного файла.
    *
    * @param string $key - ключ кешируемого значения
    * @return mixed значение из кеша 
    */
    private function getWithSerialize($key) 
    {
        $path = self::$tmpDir . DIRECTORY_SEPARATOR . $key;
        if ( file_exists($path) ) {
            clearstatcache();
            $timeStampLastEditFile=filemtime($path); // время последней модификации файла
            if ( !isset($this->timeStampsLastEditValues[$key] ) ) 
            { 
                $this->timeStampsLastEditValues[$key]=0; 
            }
            if ( $timeStampLastEditFile > $this->timeStampsLastEditValues[$key] ) {
                $data = file_get_contents( $path );
                $this->timeStampsLastEditValues[$key]=$timeStampLastEditFile;
                $this->values[$key] = \unserialize($data);
                return $this->values[$key];
            }
        }
        return $this->get($key);
    }

   /**
    * Кеширование во временный файл.
    * @param string $key - ключ кешируемого значения, он же имя временного файла
    * @param mixed $val  - сериализируемое значение 
    */
    private  function setWithSerialize($key, $val) 
    {
        $this->set($key, $val);
        $pathTmpFile = self::$tmpDir . DIRECTORY_SEPARATOR . $key;
        file_put_contents( $pathTmpFile, serialize( $val ) );
        $this->timeStampsLastEditValues[$key]=time();
    }
    
    public static function getHost()            { return self::getInstance()->get('host'); }
    public static function setHost($host)       
    { 
        if ( BaseService::isNotString($host) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            FrameworkRegistry::class, __METHOD__, '$host', 'string');
        }
        self::getInstance()->set('host', $host); 
    }
    
    public static function getBaseUrl()         { return self::getInstance()->get('baseUrl'); }
    public static function setBaseUrl($baseUrl) 
    { 
        if ( BaseService::isNotString($baseUrl) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            FrameworkRegistry::class, __METHOD__, '$baseUrl', 'string');
        }
        self::getInstance()->set('baseUrl', $baseUrl); 
    }

    public static function getConnStringDB()          { return self::getInstance()->get('connDB'); }
    public static function setConnStringDB($conn)
    { 
        if ( BaseService::isNotString($conn) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            FrameworkRegistry::class, __METHOD__, '$conn', 'string');
        }
        self::getInstance()->set('connDB', $conn); 
    }
    
    public static function getStartRoute()            { return self::getInstance()->get('startRoute'); }
    public static function setStartRoute($startRoute)       
    { 
        if ( ! BaseService::isParamCfg($startRoute, 2, 48) ){
            throw new FrmworkExcep\InvalidArgumentException('"' . $startRoute . '" - недопустимое имя роутера.',
                                                RouteMapManager::class, __METHOD__, '$startRoute', 'string');
        }
        self::getInstance()->set('startRoute', $startRoute); 
    }
    
    public static function getExcepPage()            { return self::getInstance()->get('excepPage'); }
    public static function setExcepPage($excepPage)       
    { 
        if ( BaseService::isNotString($excepPage) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            FrameworkRegistry::class, __METHOD__, '$excepPage', 'string');
        }
        self::getInstance()->set('excepPage', $excepPage); 
    }
    
    public static function getRequest()                     { return self::getInstance()->get('request'); }
    public static function setRequest( Request $request )   
    { 
        if ( ! $request ) {
            throw new FrmworkExcep\NullReferenceException ('Попытка передачи null.', FrameworkRegistry::class, __METHOD__, '$request');
        }
        self::getInstance()->set('request', $request ); 
    }
    
    private static function getRouteMap()                      { return self::getInstance()->getWithSerialize( 'rmap' ); }
    public  static function setRouteMap( RouteMapManager $map) { self::getInstance()->setWithSerialize( 'rmap', $map ); }
    
    public static function getLastExecutedController()  { return self::getInstance()->get('lastController'); }
    /**
    * Сохраняем последний выполненный контролер. 
    * @param object $controller - экземпляр контролера
    */
    public static function setExecutedController( ControllerBase $controller ) 
    { 
        if ( ! $controller ) {
            throw new FrmworkExcep\NullReferenceException ('Попытка передачи null.', FrameworkRegistry::class, __METHOD__, '$controller');
        }
        self::getInstance()->set('lastController', $controller ); 
    }

    public static function getRouting() 
    {
        $tmpReg = self::getInstance();
        if ( ! isset( $tmpReg->routing ) ) {
            $tmpReg->routing = new Routing( self::getRouteMap() );
        }
        return $tmpReg->routing;
    }
    public static function setRouting( Routing $routing ) { self::getInstance()->routing = $routing; }
        
}