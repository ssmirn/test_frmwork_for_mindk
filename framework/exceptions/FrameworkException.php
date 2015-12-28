<?php

namespace framework\exceptions;

use framework\engine\Response;
use framework\engine\registry\SessionRegistry;

/**
* Родительский класс для собственных классов исключений.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class FrameworkException extends \Exception 
{
    private $nameClass;  //@var string $nameClass имя класса, в котором было сгенерировано исключение
    private $nameMethod; //@var string $nameMethod имя метода, в котором было сгенерировано исключение
      
    public function __construct( $msg = '', $code = 0,  $nameClass = '', $nameMethod = '') 
    {
        $this->nameClass  = $nameClass;
        $this->nameMethod = $nameMethod;
        if ( is_int($lineNum) && $lineNum > 0 ){
            $this->line = $lineNum; 
        }
        parent::__construct( $msg, $code );
    }
    
   /**
    * Возвращает массив с описанием возможных путей решения.
    * Каждый класс-потомок перегружает этот метод и возвращает
    * свой набор описаний.
    * 
    * @return array  описания действий пользователя. 
    */    
    protected function suggestedSolutions ()
    {
        return array('Обратитесь к разработчику.');
    }

   /**
    * Возвращает описание исключения и порядок дальнейших действий.
    *
    * @return string  сообщение для клиента. 
    */    
    private function getErrMessage()
    {
        $result = '<h3>Test framework.  ' . $this->getOnlyNameClass(get_class($this)) . '. </h3><hr><br>' . 
                   $this->getMessage() . '<br>Исключение в классе "<i>' . $this->nameClass . '</i>"  в методе  "<i>' . 
                   $this->getOnlyNameMethod($this->nameMethod) . '</i>", строкa "<i>' . $this->getLine() . '</i>".';
        $result .= '<ul>';
        foreach ($this->suggestedSolutions() as $eachSln) {
            $result .= '<li>' . $eachSln;
        }
        $result .= '</ul>';
        return $result;
    }
    
    /**
    * Возвращает только имя метода.
    *
    * @param string $nameMethod строка с пространством имен, именем класса, именем метода.
    * @return string  имя метода. 
    */    
    private function getOnlyNameMethod( $nameMethod )
    {
        $result = '';
        $indexPos = strpos($nameMethod, '::');
        if ($indexPos !== false){
            $indexPos += 2;
            $result = substr($nameMethod, $indexPos, strlen($nameMethod) - $indexPos);
        } else {
            $result = $nameMethod;
        }
        return $result;
    }
    
   /**
    * Возвращает только имя класса.
    *
    * @param string $nameClass строка с пространством имен и именем класса.
    * @return string  имя класса. 
    */    
    private function getOnlyNameClass( $nameClass )
    {
        $result = '';
        $indexPos = strrpos($nameClass, '\\');
        if ($indexPos !== false){
            $result = substr($nameClass, ++$indexPos, strlen($nameClass) - $indexPos);
        } else {
            $result = $nameClass;
        }
        return $result;
    }
    
   /**
    * Редирект на страницу с описанием исключения.
    */    
    public final function redirectToExcepPage ()
    {
        SessionRegistry::getInstance()->putCache('msgExcep', $this->getErrMessage());
        $response = new Response();
        $response->redirectToExcepPage();
    }
    
}
