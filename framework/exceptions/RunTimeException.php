<?php

namespace framework\exceptions;

/**
* Класс исключения для обработки ошибки времени выполнения.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class RunTimeException extends FrameworkException 
{
    private $toDo;
    
    public function __construct( $msg,  $nameClass, $nameMethod, $toDo  ) 
    {
        $this->toDo = $toDo;
        parent::__construct( $msg, 65006, $nameClass, $nameMethod );
    }
    
    protected function suggestedSolutions ()
    {
        return array ('Runtime error.', $this->toDo);
    }
}

