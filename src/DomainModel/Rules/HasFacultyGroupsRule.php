<?php
namespace src\DomainModel\Rules;

use src\DomainModel\Faculty;
use src\DomainModel\NullObjects\NullGroup;

/**
 * Правило проверяющее наличие групп у данного факультета.
 * 
 * @author Смирнов С.Л.
 * @version 1.0.0.1
 * @copyright none
 */
class HasFacultyGroupsRule extends RuleBase
{
    private $faculty;    //@var object Faculty 
    
    public function __construct( Faculty $faculty)
    {
        $this->faculty = $faculty;
        $this->brokenRuleMessage = '';
    }
    
   /**
    * Метод проверяет есть ли у факультета группы
    * 
    * @return bool возвращает true если групп нет, иначе false.
    */
    public  function isValid()
    {
        $result = false;
        $arrRetVal = $this->faculty->getGroups();
        
        if (!$arrRetVal || !$arrRetVal[0]){
            $result = true;
        }
        if ($arrRetVal[0] instanceof NullGroup){
            $result = true;
        }
        if (!$result){
            $this->brokenRuleMessage = 'В факультете "' . $this->faculty->getName() . '" есть группы (' . count($arrRetVal) . '). Факультет можно будет удалить после удаления из него всех групп.';
        }
        return $result;
    }
     
    
}

