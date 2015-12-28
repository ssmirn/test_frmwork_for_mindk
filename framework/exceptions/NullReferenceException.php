<?php
namespace framework\exceptions;

/**
* Класс исключения для обработки попытoк обращения к null.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class NullReferenceException extends FrameworkException 
{
    private $nameArg;
    
    public function __construct( $msg,  $nameClass, $nameMethod,  $nameArg ) 
    {
        $this->nameArg    = $nameArg;
        parent::__construct( $msg, 65001, $nameClass, $nameMethod );
    }
    
    protected function suggestedSolutions ()
    {
        return array ( $this->nameArg . ' == null.' );
    }
}
