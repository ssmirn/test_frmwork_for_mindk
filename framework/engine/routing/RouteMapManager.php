<?php

namespace framework\engine\routing;
use framework\exceptions\InvalidArgumentException;
use framework\engine\utils\BaseService;

/**
* Обеспечивает работу с таблицей маршрутизации.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class RouteMapManager implements IRouteMapManager
{
    private $controllerMap = array(); //@var string[] массив с наименованиями контролеров
    private $actionMap     = array(); //@var string[] массив с наименованиями действий
    private $paramMap      = array(); //@var string[] массив с аргументами действий, ограничениями
                                      // на значения аргументов и значениями по умолчанию  
    private $viewMap       = array(); //@var string[] массив с наименованиями представлений
    private $redirectMap   = array(); //@var string[] массив с наименованиями маршрутов для редиректа
    private static $msgForInvalidArgExcp; //@var string сообщение для исключений
     
    public function __construct( ) 
    {
        if ( ! self::$msgForInvalidArgExcp ) {
            self::$msgForInvalidArgExcp  = 'Неверный аргумент метода.';
        }
    }
    
   /**
    * Метод сохраняет наименование контролера в элементе массива controllerMap, 
    * ключом  для которого выступает имя соотв. маршрута
    * 
    * @param string $route      имя маршрута
    * @param string $controller имя контролера
    */
    public function addControllerNameToRoute( $route, $controller ) 
    { 
        if ( ! BaseService::isParamCfg($route, 2, 48) ){
            throw new InvalidArgumentException('"' . $route . '" - недопустимое имя роутера.',
                                                RouteMapManager::class, __METHOD__, '$route', 'string');
        }
        if ( ! BaseService::isParamCfg($controller, 2, 48) ){
            throw new InvalidArgumentException('"' . $controller . '" - недопустимое имя контролера.',
                                                RouteMapManager::class, __METHOD__, '$controller', 'string');
        }
        $this->controllerMap[$route] = $controller; 
    }
    /**
    * Получаем имя контролера для данного маршрута
    * 
    * @param string $route  имя маршрута
    * @return string|null   
    */
    public function getControllerName( $route )
    { 
        if ( BaseService::isNotString($route) ) {
            throw new InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                RouteMapManager::class, __METHOD__, '$route', 'string');
        }
        return (isset( $this->controllerMap[$route] )) ? $this->controllerMap[$route] : null; 
    }
    
    /**
    * Метод сохраняет наименование действия в элементе массива actionMap, 
    * ключом  для которого выступает имя соотв. маршрута
    * 
    * @param string $route  имя маршрута
    * @param string $action имя действия
    */
    public function addActionNameToRoute( $route, $action ) 
    { 
        if ( ! BaseService::isParamCfg($route, 2, 48) ){
            throw new InvalidArgumentException('"' . $route . '" - недопустимое имя роутера.',
                                                RouteMapManager::class, __METHOD__, '$route', 'string');
        }
        if ( ! BaseService::isParamCfg($action, 2, 48) ){
            throw new InvalidArgumentException('"' . $action . '" - недопустимое имя действия.',
                                                RouteMapManager::class, __METHOD__, '$action', 'string');
        }
        $this->actionMap[$route] = $action;
    }
    
    /**
    * Получаем имя действия для данного маршрута
    * 
    * @param string $route  имя маршрута
    * @return string|null   
    */
    public function getActionName( $route )
    { 
        if ( BaseService::isNotString($route) ) {
            throw new InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                RouteMapManager::class, __METHOD__, '$route', 'string');
        }
        return (isset( $this->actionMap[$route] )) ? $this->actionMap[$route] : null; 
    }
    
   /**
    * Метод сохраняет имя аргумента, ограничение на значение аргумента (регулярное выражение),
    * значение по умолчанию аргумента     в элементе массива paramMap, 
    * ключом  для элемента выступает имя соотв. маршрута
    * 
    * @param string $route  имя маршрута
    * @param string $param  имя аргумента
    * @param string $limitationRule  ограничение (регулярное выражение) для значения аргумента
    * @param string $defaultvalue  значение по умолчанию для  аргумента
    */
    public function addParamToAction( $route, $param, $limitationRule = '', $defaultvalue = '' ) 
    { 
        if ( ! BaseService::isParamCfg($route, 2, 48) ){
            throw new InvalidArgumentException('"' . $route . '" - недопустимое имя роутера.',
                                                RouteMapManager::class, __METHOD__, '$route', 'string');
        }
        if ( ! BaseService::isParamCfg($param, 2, 48) ){
            throw new InvalidArgumentException('"' . $param . '" - недопустимое имя параметра.',
                                                RouteMapManager::class, __METHOD__, '$param', 'string');
        }
        $this->paramMap[$route][$param]['limitrule']   = $limitationRule; 
        $this->paramMap[$route][$param]['defaultvalue']= $defaultvalue; 
    }
    
   /**
    * Получаем параметры c ограничениями и значениями по умолчанию для данного маршрута
    * 
    * @param string $route  имя маршрута
    * @return array   
    */
    public function getParameters($route)      
    { 
        if ( BaseService::isNotString($route) ) {
            throw new InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                RouteMapManager::class, __METHOD__, '$route', 'string');
        }
        return $this->paramMap [$route]; 
    }
   
    /**
    * Метод сохраняет имя представления, соответствующий статус результата
    * выполнения действия  в элементе массива viewMap, с ключом - имя маршрута.
    * 
    * @param string $route  имя маршрута
    * @param int $status  результат выполнения действия контролера
    * @param string $view  имя представления
    */
    public function addViewToRoute( $route = 'default', $status = 0, $view = '' )  
    { 
        if ( ! BaseService::isParamCfg($route, 2, 48) ){
            throw new InvalidArgumentException('"' . $route . '" - недопустимое имя роутера.',
                                                RouteMapManager::class, __METHOD__, '$route', 'string');
        }
        $this->viewMap[$route][$status] = $view; 
    }
    
    /**
    * Получаем имя представления для данного маршрута и
    * статуса выполнения действия контролера
    * 
    * @param string $route  имя маршрута
    * @param int $status  результат выполнения действия контролера
    * @return string|null  
    */
    public function getView( $route, $status ) 
    { 
        if ( BaseService::isNotString($route) ) {
            throw new InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                RouteMapManager::class, __METHOD__, '$route', 'string');
        }
        if ( ! BaseService::isId( $status ) ) {
            throw new InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                RouteMapManager::class, __METHOD__, '$status', 'int');
        }
        return (isset( $this->viewMap[$route][$status] )) ? $this->viewMap[$route][$status] : null; 
    }

    /**
    * Метод сохраняет имя маршрута для редиректа, соответствующий статус результата
    * выполнения действия  в элементе массива redirectMap, с ключом - именем маршрута.
    * 
    * @param string $route  имя маршрута
    * @param int $status  результат выполнения действия контролера
    * @param string $newRoute  имя маршрута для редиректа
    */
    public function addRedirectToRoute( $route, $status = 0, $newRoute = '' )  
    { 
        if ( ! BaseService::isParamCfg($route, 2, 48) ){
            throw new InvalidArgumentException('"' . $route . '" - недопустимое имя роутера.',
                                                RouteMapManager::class, __METHOD__, '$route', 'string');
        }
        if ( ! BaseService::isId( $status ) ) {
            throw new InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                RouteMapManager::class, __METHOD__, '$status', 'int');
        }
        $this->redirectMap[$route][$status]=$newRoute;
    }
    /**
    * Получаем имя маршрута для редиректа для данного маршрута и
    * статуса результата выполнения действия.
    * 
    * @param string $route  имя маршрута
    * @param int $status  результат выполнения действия контролера
    * @return string|null  
    */
    public function getRedirect( $route, $status ) 
    { 
        if ( BaseService::isNotString($route) ) {
            throw new InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                RouteMapManager::class, __METHOD__, '$route', 'string');
        }
        if ( ! BaseService::isId( $status ) ) {
            throw new InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                RouteMapManager::class, __METHOD__, '$status', 'int');
        }
        return (isset( $this->redirectMap[$route][$status] )) ? $this->redirectMap[$route][$status] : null; 
    }
}

