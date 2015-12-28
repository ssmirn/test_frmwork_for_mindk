<?php

namespace framework\exceptions;

/**
* Класс исключения для обработки ошибок при передаче параметров в методы.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class InvalidArgumentException extends FrameworkException 
{
    private $nameArg; //@var string $nameArg имя аргумента
    private $typeArg; //@var string $nameArg тип аргумента
    
    public function __construct( $msg,  $nameClass, $nameMethod,  $nameArg, $typeArg) 
    {
        $this->nameArg    = $nameArg;
        $this->typeArg    = $typeArg;
        parent::__construct( $msg, 65005, $nameClass, $nameMethod );
    }
    
    protected function suggestedSolutions ()
    {
        return array ('Ошибочный аргумент "<i>' . $this->nameArg . '</i>" (oжидаемый тип аргумента - ' . $this->typeArg . '). ',
                      'Error:   ' . $this->getTraceAsString());
    }
}
