<?php
namespace src\DomainModel\Rules;

use src\DomainModel\BaseObject;

/**
 * Правило проверяющее не вносилось ли ранее данное наименование.
 * 
 * @author Смирнов С.Л.
 * @version 1.0.0.1
 * @copyright none
 */
class CheckAlreadyExistsNameRule extends RuleBase
{
    private $arrObj;  //@var array уже внесенныe объекты данного типа
    private $holder;  //@var object BaseObject, содержащий наименование
    
    public function __construct( array $arrObj, BaseObject $holder)
    {
        $this->arrObj = $arrObj;
        $this->holder = $holder;
        $this->brokenRuleMessage = '';
    }
    
    /**
    * Метод проверяет не существует ли уже объект с таким же наименованием.
    * 
    * @return bool true - в случае если данное наменование еще не было сохранено, иначе false
    */
    public  function isValid()
    {
        $name = $this->holder->getName();
        $this->arrObj;
        $arrObjFound = array_filter($this->arrObj, 
                               function($item) use($name){ return strcmp (mb_strtoupper($item->getName()), mb_strtoupper($name)) == 0;});
        $result = (count($arrObjFound) == 0);
        if ( ! $result ){
            $foundObj = array_pop($arrObjFound);
            if ($foundObj->getId() > 0 && $foundObj->getId() == $this->holder->getId()){
                $result =  true;
            } else {
                $this->brokenRuleMessage = '"' . $name . '" - уже существует в БД.';
                $result =  false;
            }
        }
        return  $result;     
    }
}
