<?php
namespace framework\exceptions;

/**
* Класс исключения для обработки ошибок в БД.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class DbException extends FrameworkException 
{
    public function __construct( $msg,  $nameClass, $nameMethod ) 
    {
        parent::__construct( $msg, 65007, $nameClass, $nameMethod );
    }
    
    protected function suggestedSolutions ()
    {
        return array ('Ошибка в структуре БД.');
    }
}
