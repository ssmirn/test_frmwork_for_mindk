<?php

namespace framework\engine\routing;

use framework\engine\Request;
use framework\controllers as NamespaceController;
use framework\engine\registry\FrameworkRegistry;
use framework\engine\Response;
use framework\engine\utils\BaseService;
use framework\exceptions as FrmworkExcep;


/**
* Обеспечивает создание экземпляра необходимого контролера
* и выполнение соответствующего действия.
* Реализует шаблон проектирования Proxy, перехватывая все
* обращения к RouteMapManager (таблице маршрутизации). 
* Поэтому использует интерфейс IRouteMapManager (IRouteMapManager
* также используется и RouteMapManager).
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class Routing implements IRouteMapManager
{
    private static $baseController;        //@var object ControllerBase
    private static $defaultController;     //@var object DefaultController
    private $executingController = null;   //@var object cозданный экземпляр контролера 
    private static $msgForInvalidArgExcp;  //@var string сообщение для исключения InvalidArgumentException
    private static $msgForCreateObjExcp;   //@var string сообщение для исключения CreateObjectException
    private $routeMapManager    = null;    //@var object RouteMapManager  
    private $request            = null;    //@var object Request 
    private $alreadyCalledRoute = array(); //@var array массив с именем предыдущего вызванного контролера
    private $paramsAction       = null;    //@var array массив с аргументами для действия контролера

    public function __construct( RouteMapManager $map ) 
    {
        $this->routeMapManager = $map;
        if ( ! self::$msgForInvalidArgExcp ) {
            self::$msgForInvalidArgExcp  = 'Неверный аргумент метода.';
        }
        if ( ! self::$msgForCreateObjExcp ) {
            self::$msgForCreateObjExcp  = 'Невозможно создать экземпляр контролера "';
        }
    }
    
   /**
    * Возвращает имя выполняемого маршрута
    * 
    * @return string   
    */
    public function getRouteName( ) 
    {
        if ( ! $this->request ) { $this->request = Request::getInstance(); }
        return $this->request->getRouteName();
    }

   /**
    * Возвращает имя представления
    * 
    * @return string   
    */
    public function getViewName( )  
    { 
        return $this->getSettings('View'); 
    }

   /**
    * Возвращает имя нового маршрута для редиректа. В случае
    * если выполняется редирект маршрута, то для экземпляра 
    * $this->request устанавливается значение нового маршрута,
    * как значение актуального, при этом массив аргументов
    * для текущего действия - обнуляется ($this->removeAllParametersForAction())
    * 
    * @return string   
    */
    public function getRedirectRouteName( ) 
    {
        $redirectRouteName = $this->getSettings('Redirect');
        if ($redirectRouteName) {
            $this->request->setRouteName($redirectRouteName);
            $this->removeAllParametersForAction();
        }
        return $redirectRouteName;
    }

    /**
    * Возвращает имя представления или нового маршрута для редиректа.
    * Вызывает метод getView или getRedirect.
    * 
    * @param string $suffixNameMethod суффикс для вызываемого метода - View или Redirect.
    * @return string   
    */
    private function getSettings( $suffixNameMethod ) 
    {
        if ( BaseService::isNotString($suffixNameMethod) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$suffixNameMethod', 'string');
        }
        $nameMethod = 'get' . $suffixNameMethod; // получаем имя вызываемого метода
        if ( ! method_exists( Routing::class, $nameMethod ) ){
            throw new FrmworkExcep\RunTimeException('В классе Routing не существует метода ' . $nameMethod ,
                                                     Routing::class, __METHOD__, 'В случае необходимости создайте метод ' . $nameMethod );
        }
        $nameRoute      = $this->getRouteName();
        $prevController = FrameworkRegistry::getLastExecutedController(); // получаем экземпляр последнего сохраненного выполненого контролера
        if ( $prevController ){
            $status = $prevController->getStatus();//результат выполнения действия последнего контролера
        }
        if ( ! $status ) { $status = 0; }
        //вызываются методы getView или getRedirect для 
        //текущего маршрута и статуса до первого возвращенного значения:
        $result = $this->$nameMethod( $nameRoute, $status );
        if ( ! $result ) {
            $result = $this->$nameMethod( $nameRoute, 0 );
        }
        if ( ! $result ) {
            $result = $this->$nameMethod( 'default', $status );
        }
        if ( ! $result ) {
            $result = $this->$nameMethod( 'default', 0 );
        }
        return $result;
    }

   /**
    * Создает и возвращает экземпляр контролера для заданного маршрута.
    * Актуальный маршрут берем из Request.
    * 
    * @param Request $req.
    * @return object|null экземпляр контролера   
    */
    public function getController( Request $req ) 
    {
        $this->request = $req;
        $nameRedirectRoute = '';
        if ( ! FrameworkRegistry::getLastExecutedController() ) {
            if ( $this->request->isLoadingDefaultPage() ){
                // В случае если впервые загружается Default страница фраймворка,
                // переключаемся на стартовую страницу приложения:
                $response = new Response();
                $response->redirectToStartPage();
                $this->executingController = null;
                return null;
            }
            // получаем имя контролера для текущего маршрута:
            $controllerName = $this->getControllerName( $this->getRouteName() );
            if ( !$controllerName ) {
                // переключаемся на default страницу
                $this->executingController = $this->getDefaultController();
                return $this->executingController; 
            }
        } else {
            // получаем имя маршрута для редиректа:
            if ( $this->request->isProgramRedirectRoute() ){
                $nameRedirectRoute = $this->getRouteName(); // программный редирект
            } else {
                // редирект на основе спроектированной табл. маршрутизации (config.xml):
                $nameRedirectRoute = $this->getRedirectRouteName(); 
            }
            if ( !$nameRedirectRoute ) { 
                // редиректа для текущего маршрута не предусмотрено:
                $this->executingController = null;
                return null; 
            }
            $controllerName = $this->getControllerName( $nameRedirectRoute );
        }
        // получаем экземпляр контролера по его имени $controllerName:
        $controllerObj = $this->createController( $controllerName );
        
        //подстраховываемся от циклического вызова одного и 
        //того же маршрута:
        $uniqueNameRoute = $this->getUniqueNameRoute();
        if ( isset( $this->alreadyCalledRoute[$uniqueNameRoute] ) ) {
            $this->executingController = null;
            return null;
        }
        $this->alreadyCalledRoute[$uniqueNameRoute] = 1;
        
        if ( $this->request->isProgramRedirectRoute() ){
            // отключаем режим программного редиректа:
            $this->request->offProgramRedirectRoute();
        }
        //иницируем подготовку параметров для выполнения действия контролера:
        $this->getParametersForAction();
        return $controllerObj;
    }
    
   /**
    * Создание экземпляра контролера на основе имени контролера.
    *
    * @param string $nameController.
    * @return object контролер   
    */
    private function createController( $nameController ) 
    {
        $this->executingController = null;
        if ( BaseService::isNotString($nameController) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$nameController', 'string');
        }
        $className = NamespaceController::class . "\\$nameController"; 
        $filepath = '..'          . DIRECTORY_SEPARATOR . 'framework' . DIRECTORY_SEPARATOR . 
                    'controllers' . DIRECTORY_SEPARATOR . $nameController . '.php';
        if ( ! file_exists( $filepath ) ) {
            throw new FrmworkExcep\FileNotFoundException (self::$msgForCreateObjExcp  . $nameController . '" из файлa "' . $filepath . '.php".', 
                                                          Routing::class, __METHOD__);
        }
        if ( ! class_exists( $className) ) {
            throw new FrmworkExcep\RunTimeException(self::$msgForCreateObjExcp  . $nameController . '".',
                                                     Routing::class, __METHOD__, 'Проверьте '. $className);
        }
        $controllerClass = new \ReflectionClass($className);
        if ( ! self::$baseController ) {
            self::$baseController = new \ReflectionClass( NamespaceController::class . '\\ControllerBase' );
        }
        if ( ! $controllerClass->isSubClassOf( self::$baseController ) ) {
           throw new FrmworkExcep\RunTimeException(self::$msgForCreateObjExcp  . $nameController . '".',
                                                     Routing::class, __METHOD__, 'Класс контролера должен быть потомком ControllerBase.'); 
        }
        try {
            // создание экземпляра контролера по имени $nameController:
            $this->executingController = $controllerClass->newInstance($this);
            return $this->executingController;
        } catch (\Exception $ex) {
            throw new FrmworkExcep\CreateObjectException('Невозможно создать экземпляр класса "' . $className . '" ' . $ex->getMessage(),
                                                         Routing::class, __METHOD__); 
        }
    }
    
   /**
    * Обеспечивает выполнение действия заданного контролера
    */
    public function executeAction( ) 
    {
        if ( ! $this->executingController ) {
            throw new FrmworkExcep\NullReferenceException( '$this->executingController == null.',
                                                     Routing::class, __METHOD__, 'Проверте таблицу маршрутизации.');
        }
        $actionName = $this->getActionName( ); //получаем имя действия для выполнения
        if ( ! $actionName ){
            throw new FrmworkExcep\NullReferenceException( 'Наименование действия не может быть пустым',
                                                     Routing::class, __METHOD__, 'Проверте таблицу маршрутизации.');
        }
        $nameExecController   = get_class($this->executingController); // имя выполняемого контролера
        if ( ! method_exists( $nameExecController, $actionName ) ) {
            throw new FrmworkExcep\RunTimeException('Попытка вызова несуществующего метода "' . $actionName . '" в контролере "' . $nameExecController . '".',
                                                     Routing::class, __METHOD__, 'Проверте наличие в классе контролера данного метода.');
        }
        //получаем аргументы для вызываемого действия (метода):
        $arrParamsForAction = $this->getParametersForAction(); 
        //выполнение действия контролера:
        if ( is_array($arrParamsForAction) && sizeof($arrParamsForAction) >= 1 ){
            \call_user_func_array( array( $this->executingController, $actionName ), $arrParamsForAction );
        } else {
            \call_user_func( array( $this->executingController, $actionName ) );
        }
    }
    
   /**
    * Создание и возвращение экземпляра Default-контролера.
    *
    * @return object|null Default-контролер   
    */
    private function getDefaultController( ) 
    {
        $this->request->setRouteName('default');
        if ( isset( $this->alreadyCalledRoute['default'] ) ) {
            return null;
        }
        $this->alreadyCalledRoute['default'] = 1;
        if ( ! self::$defaultController ) {
            try {
                self::$defaultController  = new NamespaceController\DefaultController($this);
            } catch (\Exception $ex) {
                throw new FrmworkExcep\CreateObjectException('Невозможно создать экземпляр класса DefaultController. ' . $ex->getMessage(),
                                                     Routing::class, __METHOD__); 
            }
        }
        return  self::$defaultController;
    }
    
    private function getUniqueNameRoute( ) 
    {
        return $this->getRouteName() . implode('', $this->request->getParameters());
    }
    
   /**
    * Bозвращение параметров для действия контролера.
    *
    * @return array|null    
    */
    public function getParametersForAction( ) 
    {
        $result = null;
        if ( strcasecmp($this->getRouteName(), 'default') == 0 ){
            return $result;
        }
        if ( is_array($this->paramsAction) && sizeof($this->paramsAction) >= 1 ){
            return $this->paramsAction; 
        }
        if ( ! $this->request ) {
            $this->request = Request::getInstance();
        }
        // заполнение массива параметров $this->paramsAction на основе параметров из актуального Request
        // и параметров действия из соотв. маршрута в табл. маршрутизации:
        $parametersRoute = new ParametersRoute($this->request->getParameters(), $this->getParameters($this->getRouteName()));
        // возвращение статуса и массива параметров:
        list($statusCheckRulesForParams, $this->paramsAction) = $parametersRoute->getParamsForCallingAction();
        if ( $statusCheckRulesForParams == ParametersRoute::statuses('CHECK_ERR')) {
            
            throw new FrmworkExcep\RunTimeException('Ошибка в аргументах действия "' . $this->getActionName( ) . '" в контролере "' . $this->getControllerName( $this->getRouteName()). '".',
                                                     Routing::class, __METHOD__, 'Проверьте соотв. аргументов в Request и config.xml.'); 
        }
        if ( ($statusCheckRulesForParams == ParametersRoute::statuses('NO_PARAMETERS')) ||
             ($statusCheckRulesForParams == ParametersRoute::statuses('CHECK_OK'))        ) {
                $result = $this->paramsAction; 
        }
        return $result;
    }
    
   /**
    * Обнуляем массив параметров для действия контролера.
    */
    private function removeAllParametersForAction()  
    { 
        $this->paramsAction = array();
    }
    
   /**
    * Метод иницирует редирект на другой маршрут 
    * 
    * Note: Редирект на другой маршрут может быть выполнен двумя способами:
    *           - неявно через тег <redirect> в таблице маршрутизации, т.е. фраймворк
    *               самостоятельно отправит Вас на заданный маршрут, в зависимости от 
    *               установленного значения  $enumResultAction (или без него);
    *           - или через вызов данного метода из ControllerBase->programmRedirectToRoute().
    *
    * @param string $nameRedirectRoute - имя маршрута для форварда
    * @param array  $arrParams         - массив с параметрами для действия вызываемого маршрута
    */
    public function programmRedirectToRoute( $nameRedirectRoute, $arrParams )
    {
        if ( BaseService::isNotString ($nameRedirectRoute) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$nameRedirectRoute', 'string');
        }
        if ( ! $this->request ) { $this->request = Request::getInstance(); }
        $this->request->onProgramRedirectRoute();         // устанавливаем флаг программного редиректа
        $this->request->setRouteName($nameRedirectRoute); // устанавливаем для request имя нового (редиректного) маршрута
        $this->addParametersForRedirectRoute($arrParams); // вносим в request параметры для действия редиректного маршрута
    }
    
   /**
    * Вносим в request параметры для действия контролера маршрута, на который выполняется редирект.
    *
    * @param array $arrParams параметры из нового маршрута
    */
    public function addParametersForRedirectRoute( $arrParams )
    {
        if ( ! is_array($arrParams) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$arrParams', 'array');
        }
        if ( ! $this->request ) { $this->request = Request::getInstance(); }
        $this->request->removeAllParameters(); // удаляем из request текущие параметры
        $this->removeAllParametersForAction(); // обнуляем $this->paramsAction
        foreach ($arrParams as $key => $value) {
            $this->request->addParameter($key, $value); //вносим в request параметры
        }
        FrameworkRegistry::setRouting( $this );
    }
    
    /**
    * Bозвращение менеджера таблицы маршрутизации.
    *
    * @return object RouteMapManager    
    */
    public function getRouteMapManager( ) 
    {  
        return $this->routeMapManager; 
    } 
    
    /**
    * Метод сохраняет наименование контролера для соотв. маршрута
    * 
    * @param string $route      имя маршрута
    * @param string $controller имя контролера
    */
    public function addControllerNameToRoute( $route, $controller )
    { 
        if ( BaseService::isNotString ($route) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$route', 'string');
        }
        if ( BaseService::isNotString ($controller) ) {
             throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                             Routing::class, __METHOD__, '$controller', 'string');
        }
        $this->getRouteMapManager()->addControllerNameToRoute($route, $controller); 
    } 
    
   /**
    * Возращает имя контролера для данного маршрута
    * 
    * @param string $route  имя маршрута
    * @return string|null   
    */
    public function getControllerName( $route ) 
    { 
        if ( BaseService::isNotString ($route) ) {
             throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                             Routing::class, __METHOD__, '$route', 'string');
        }
        return $this->getRouteMapManager()->getControllerName($route); 
    }
    
   /**
    * Метод сохраняет наименование действия для соотв. маршрута
    * 
    * @param string $route  имя маршрута
    * @param string $action имя действия
    */
    public function addActionNameToRoute( $route, $action ) 
    { 
        if ( BaseService::isNotString ($route) ) {
         throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                          Routing::class, __METHOD__, '$route', 'string');
        }
        if ( BaseService::isNotString ($action) ) {
             throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$action', 'string');
        }
        $this->getRouteMapManager()->addActionNameToRoute( $route, $action ); 
    }
    
   /**
    * Возращает имя действия для данного маршрута
    * 
    * @param string $route  имя маршрута
    * @return string|null   
    */
    public function getActionName( $route = '' ) 
    {
        if ( ! $route ){
            $route = $this->getRouteName();
        }
        return $this->getRouteMapManager()->getActionName( $route ); 
    }
    
   /**
    * Метод сохраняет имя параметра, ограничение на значение параметра (регулярное выражение),
    * значение по умолчанию параметра  для  соотв. маршрута
    * 
    * @param string $route  имя маршрута
    * @param string $param  имя аргумента
    * @param string $limitationRule  ограничение (регулярное выражение) для значения аргумента
    * @param string $defaultvalue  значение по умолчанию для  аргумента
    */
    public function addParamToAction( $route, $param, $limitationRule = '', $defaultvalue = '' ) 
    { 
        if ( BaseService::isNotString ($route) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$route', 'string');
        }
        if ( BaseService::isNotString ($param) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$param', 'string');
        }
        $this->getRouteMapManager()->addParamToAction( $route, $param, $limitationRule,  $defaultvalue ); 
    }
    
   /**
    * Получаем параметры c ограничениями и значениями по умолчанию для данного маршрута
    * 
    * @param string $route  имя маршрута
    * @return array   
    */
    public function getParameters( $route ) 
    { 
        if ( BaseService::isNotString ($route) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$route', 'string');
        }
        return $this->getRouteMapManager()->getParameters($route); 
    }
   
   /**
    * Метод сохраняет имя представления, соотв. статус результата
    * выполнения действия контролера для заданного маршрута.
    * 
    * @param string $route  имя маршрута
    * @param int $status  результат выполнения действия контролера
    * @param string $view  имя представления
    */
    public function addViewToRoute( $route='default', $status = 0, $view = '' ) 
    { 
        if ( BaseService::isNotString ($route) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$route', 'string');
        }
        if ( ! BaseService::isId( $status ) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$status', 'int');
        }
        $this->getRouteMapManager()->addViewToRoute($route, $status, $view); 
 
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
        if ( BaseService::isNotString ($route) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$route', 'string');
        }
        if ( ! BaseService::isId( $status ) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$status', 'int');
        }
        return $this->getRouteMapManager()->getView( $route, $status ); 
    }

   /**
    * Метод сохраняет для данного маршрута - имя маршрута для редиректа и 
    * соответствующий статус результата выполнения действия контролера
    * 
    * @param string $route  имя маршрута
    * @param int $status  результат выполнения действия контролера
    * @param string $newRoute  имя маршрута для редиректа
    */
    public function addRedirectToRoute( $route, $status = 0, $newRoute = '') 
    { 
        if ( BaseService::isNotString ($route) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$route', 'string');
        }
        if ( ! BaseService::isId( $status ) ) {
           throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$status', 'int');
        }
        $this->getRouteMapManager()->addRedirectToRoute( $route, $status, $newRoute ); 
    }
    
    
   /**
    * Получаем имя маршрута для редиректа для заданного маршрута и
    * статуса выполнения действия 
    * 
    * @param string $route  имя маршрута
    * @param int $status  результат выполнения действия контролера
    * @return string|null  
    */
    public function getRedirect( $route, $status) 
    { 
        if ( BaseService::isNotString ($route) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$route', 'string');
        }
        if ( ! BaseService::isId( $status ) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$status', 'int');
        }
        return $this->getRouteMapManager()->getRedirect( $route, $status ); 
         
    }
     
}

