<?php
namespace src\DomainModel\Rules;

use src\DomainModel\BaseObject;
use framework\engine\utils\BaseService;

/**
 * Правило проверяющее есть ли уже в массиве элемент с таким же id.
 * 
 * @author Смирнов С.Л.
 * @version 1.0.0.1
 * @copyright none
 */
class IsExistsIdRule extends RuleBase
{
    private $arrObj;  //@var array массив уже внесенных объектов данного типа
    private $holder;  //@var object BaseObject, содержащий id
    
    public function __construct( array $arrObj, BaseObject $holder)
    {
        $this->arrObj = $arrObj;
        $this->holder = $holder;
        $this->brokenRuleMessage = '';
    }
    
  /**
    * Метод проверяет не существует ли уже объект с таким же id.
    * 
    * @return bool true - в случае если объект с данным id уже существует,  иначе false.
    */
    public  function isValid()
    {
        $id = $this->holder->getId();
        $result = BaseService::isId($id);
        if (!$result){
            $this->brokenRuleMessage ='"' . $this->holder->getId() . '" - не является идентификатором.';
            return  false;  
        }
        $arrObjFound = array_filter($this->arrObj, 
                               function($item) use($id){ return $item->getId() == $id;});
        $result = (count($arrObjFound) > 0);
        if (!$result){
            $this->brokenRuleMessage = '"' . $id . '" - НЕ существует в БД.';
        }                       
        return  $result;     
    }
}