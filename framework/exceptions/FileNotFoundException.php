<?php
namespace framework\exceptions;

/**
* Класс исключения для обработки ошибки недоступности файла.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class FileNotFoundException extends FrameworkException 
{
    
    public function __construct( $msg,  $nameClass, $nameMethod ) {
        parent::__construct( $msg, 65004, $nameClass, $nameMethod );
    }
    
    protected function suggestedSolutions ()
    {
        return array ('Файл не найден.', 
                      'Проверьте правильность пути и наименования файла.', 
                      'Проверьте соотв. настройки в config.xml.');
    }
 
 }