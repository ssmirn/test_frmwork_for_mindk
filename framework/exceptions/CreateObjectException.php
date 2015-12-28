<?php

namespace framework\exceptions;

/**
* Класс исключения для обработки ошибок при создании объекта.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class CreateObjectException extends FrameworkException 
{
    public function __construct(  $msg,  $nameClass, $nameMethod  ) 
    {
        parent::__construct( $msg, 65004, $nameClass, $nameMethod );
    }
    
    protected function suggestedSolutions ()
    {
        return array ('Ошибка при создании объекта.',
                      'Проверьте конструктор класса.', 
                      'Если ошибка возникает при создании экземпляра контролера, обратите внимание на нижеследующие пункты.', 
                      'Имя класса контролера должно совпадать со значением атрибута "name" в соотв. теге "controller" в файле "config.xml".',
                      'Класс контролера должен быть реализован в одноименном *.php-файле.', 
                      'Класс контролера должен находиться в пространстве имен "framework\controllers".',
                      'Класс контролера должен наследоваться от ControllerBase.');
    }
 
}
