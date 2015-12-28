<?php
namespace framework\engine;

use framework\engine\registry\FrameworkRegistry;
use framework\engine\routing\RouteMapLoader;
use framework\engine\Request;
use framework\exceptions\FrameworkException;

/**
* FrontController организует и распределяет выполнение запросов.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class FrontController {
    
    private function __construct() {}

   /**
    * Сохраняем таблицу маршрутизации в реестре FrameworkRegistry (при первом обращении к RouteMapLoader).
    * Затем вызывается соответствующее текущим параметрам Request действие контролера.
    */
    public static function main() 
    {
        try {
            // считываем таблицу маршрутизации (при первом обращении)
            // и сохраняем ее в FrameworkRegistry:
            RouteMapLoader::getInstance()->initialize(); 
            // создаем в соотвествии с заданным маршрутом(Request) экземпляр контролера, 
            // затем посредством метода run вызывается соотвующее действие:
            while( $controller = FrameworkRegistry::getRouting()->getController( Request::getInstance() ) ) {
                $controller->run();
            }
        } catch (FrameworkException $frmExcep) {
            $frmExcep->redirectToExcepPage();
        } 
    }
}


