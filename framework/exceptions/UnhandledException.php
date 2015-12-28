<?php

namespace framework\exceptions;

/**
* Класс исключения для неизвестной ошибки.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class UnhandledException extends FrameworkException 
{
    public function __construct( $msg, $nameClass, $nameMethod ) 
    {
        if (is_string($msg) && strlen(trim($msg)) == 0){
            $msg = 'Непредвиденная ошибка';
        }
        parent::__construct( $msg, 65030, $nameClass, $nameMethod );
    }
    
    protected function suggestedSolutions ()
    {
        return array ('Error:   ' . $this->getTraceAsString() );
    }
}
