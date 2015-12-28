<?php
namespace framework\controllers;

/**
* Класс контролера по умолчанию.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class DefaultController extends ControllerBase 
{
   /**
    * Метод вызывается в соответствии со значением тега
    * <default><action> в табл.маршрутизации
    */
    public function index( )
    { 
        $this->getView( ); // вызов представления, заданного в теге <default> табл.маршрутизации
    }
}

