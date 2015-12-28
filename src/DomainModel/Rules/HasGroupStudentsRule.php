<?php
namespace src\DomainModel\Rules;

use src\DomainModel\Group;
use src\DomainModel\NullObjects\NullStudent;

/**
 * Правило проверяющее наличие студентов у данной группы.
 * 
 * @author Смирнов С.Л.
 * @version 1.0.0.1
 * @copyright none
 */
class HasGroupStudentsRule extends RuleBase
{
    private $group;    //@var object Group 
    
    public function __construct( Group $group)
    {
        $this->group = $group;
        $this->brokenRuleMessage = '';
    }
    
   /**
    * Метод проверяет наличие студентов у данной группы.
    * 
    * @return bool возвращает true, если cтудентов в группе нет,  иначе false.
    */
    public  function isValid()
    {
        $result = false;
        $arrRetVal = $this->group->getStudents();
        if (!$arrRetVal || !$arrRetVal[0]){
            $result = true;
        }
        if ($arrRetVal[0] instanceof NullStudent){
            $result = true;
        }
        if (!$result){
            $this->brokenRuleMessage = 'В группе ' . $this->group->getName() . ' - ' . count($arrRetVal) . ' студ. Удалить группу можно, когда в ней не будет студентов.';
        }
        return $result;
    }
     
    
}
