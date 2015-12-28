<?php

namespace framework\engine\routing;

/**
* Интерфейс для работы с таблицей маршрутизации.
* Реализуется  классами Routing и RouteMapManager.
* Использование таблицы маршрутизации всегда тесно связано с функционалом класса Routing.
* Введен для того, чтобы клиентские классы обращались к таблице маршрутизации
* через Routing (Routing агрегирует экземпляр RouteMapManager, являясь для него Proxy). 
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
interface IRouteMapManager
{
    public function addControllerNameToRoute( $route, $controller );
    public function getControllerName( $route );
    
    public function addActionNameToRoute( $route, $action );
    public function getActionName( $route );
    
    public function addParamToAction( $route, $param, $limitationRule, $defaultvalue = '' );
    public function getParameters($route);
   
    public function addViewToRoute( $route = 'default', $status = 0, $view = '' );
    public function getView( $route, $status );

    public function addRedirectToRoute( $route, $status=0, $newRoute='' );
    public function getRedirect( $route, $status );
}
