<?php
namespace src\DomainModel\Rules;

use src\DomainModel\BaseObject;
use framework\engine\utils\BaseService;

/**
 * Правило проверяющее является ли экземпляр ненулевым.
 * Т.е. у него id > 0 и имя - непустая строка.
 * 
 * @author Смирнов С.Л.
 * @version 1.0.0.1
 * @copyright none
 */
class IsIdValidlRule extends RuleBase
{
    private $holder;    //@var BaseObject объект.
    
    public function __construct(BaseObject $holder)
    {
        $this->holder = $holder;
        $this->brokenRuleMessage = '';
    }
    
   /**
    * Метод проверяет является ли экземпляр BaseObject нулевым.
    * 
    * @return bool возвращает true, если у BaseObject - целочисленный id (или == -1) и не нулевое наименование,  иначе false.
    */
    public  function isValid()
    {
        $result = BaseService::isId($this->holder->getId()) && 
                  BaseService::isFullStr($this->holder->getName());
        if (!$result){
            $this->brokenRuleMessage ='"' . $this->holder->getId() . '" - null - объект.';
        }
        return $result;
    }
}
