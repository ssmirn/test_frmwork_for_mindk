<?php

namespace framework\engine;

use framework\engine\registry\FrameworkRegistry;
use framework\exceptions as FrmworkExcep;
use framework\engine\utils\BaseService;


/**
* Обеспечивает работу с запросом.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class Request 
{
    private static $instance;       //@var Request 
    private $properties = array();  //@var array  для хранения служебных данных  
    private $parameters = array();  //@var array  для хранения параметров запроса
    private $isProgrammRedirectRoute = false; //@var boolean флаг для режима программного редиректа
    private $isLoadingDefPage = false;        //@var boolean флаг для определения загрузки default-страницы  
    private static $msgForInvalidArgExcp; //@var string сообщение для исключений
        
    private function __construct() { }
    
   /**
    * Реализуем Singleton.
    */
    public static function getInstance() 
    {
        if ( ! isset(self::$instance) ) { 
            self::$instance = new self(); 
            self::$instance->filterReq();
            if ( ! self::$msgForInvalidArgExcp ) {
                self::$msgForInvalidArgExcp  = 'Неверный аргумент метода.';
            }
            FrameworkRegistry::setRequest( self::$instance );
        }
        return self::$instance;
    }   
  
   /**
    * Разбираем запрос. Выделяем и сохраняем маршрут и параметры. 
    */
    private function filterReq() 
    {
        $url_parts = \parse_url($_SERVER['REQUEST_URI']);
        if (!$url_parts) { return; }
        $routeName = trim(\str_replace(FrameworkRegistry::getBaseUrl(), "", $url_parts['path']), '/');
        $this->isLoadingDefPage = (strcasecmp(FrameworkRegistry::getBaseUrl(), $url_parts['path']) == 0);
        if( strlen($routeName) == 0 ) { return; }
        $this->offProgramRedirectRoute();
        foreach( $_REQUEST as $nameArg => $valArg ) {
           $this->addParameter( $nameArg, $valArg );
        } 
        $this->setRouteName($routeName);
        return;
    }
    
   /**
    * Сохраняем имя маршрута. 
    * @param string $routeName - имя маршрута
    * @param mixed $val  - сериализируемое значение 
    */
    public function setRouteName ($routeName) 
    { 
        if ( BaseService::isNotString( $routeName )  ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp, Request::class, __METHOD__, '$routeName', 'string');
        }
        $this->addProperty('route', $routeName); 
        FrameworkRegistry::setRequest( self::$instance );
    }
    
    public function getRouteName ()  
    { 
        return (isset( $this->properties['route'] )) ? $this->properties['route'] : null;  
    }
        
    public function addProperty($key, $val)   
    { 
        if ( ! $key ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Request::class, __METHOD__, '$key', 'mixed');
        }
        $this->properties[$key] = $val; 
    }
    public function getProperty($key)  
    { 
        if ( ! $key ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Request::class, __METHOD__, '$key', 'mixed');
        }
        return (isset( $this->properties[$key] )) ? $this->properties[$key] : null; 
    }
    
    /**
    * Сохраняем параметр запроса. 
    * Для защиты при сохранении значения параметра используется htmlspecialchars.
    * @param string $key - имя параметра
    * @param string $val - значение параметра
    */
    public function addParameter ($key, $val) 
    {
        if ( ! $key ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Request::class, __METHOD__, '$key', 'string');
        }
        $this->parameters[$key] = htmlspecialchars($val); 
    }
    public function getParameters()            { return $this->parameters; }
    public function removeAllParameters()      { return $this->parameters = array(); }
    
    public function isLoadingDefaultPage( )    { return $this->isLoadingDefPage; }
    
    public function onProgramRedirectRoute ( ) { $this->isProgrammRedirectRoute = true; }
    public function offProgramRedirectRoute( ) { $this->isProgrammRedirectRoute = false; }
    public function isProgramRedirectRoute ( ) { return $this->isProgrammRedirectRoute; }
    
}

