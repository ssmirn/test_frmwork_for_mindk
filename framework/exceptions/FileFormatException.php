<?php

namespace framework\exceptions;

/**
* Класс исключения для обработки ошибок  формата файла.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class FileFormatException extends FrameworkException {
    
    private $toDo;
    
    public function __construct( $msg,  $nameClass, $nameMethod,  $toDo ) 
    {
        $this->toDo = $toDo;
        parent::__construct( $msg, 65003, $nameClass, $nameMethod );
    }
    
    protected function suggestedSolutions ()
    {
        return array ( $this->toDo );
    }
   
 }
