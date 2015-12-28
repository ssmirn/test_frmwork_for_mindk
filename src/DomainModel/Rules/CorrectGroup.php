<?php
namespace src\DomainModel\Rules;

use src\DomainModel\Student;
use src\DomainModel\NullObjects\NullGroup;

/**
 * Правило проверяющее наличие группы у студента.
 * 
 * @author Смирнов С.Л.
 * @version 1.0.0.1
 * @copyright none
 */
class CorrectGroup extends RuleBase
{
    private $student;  //@var object Student
    
    public function __construct( Student $student )
    {
        $this->student = $student;
        $this->brokenRuleMessage = '';
    }
    
  /**
    * Метод проверяет есть ли у студента группа.
    * 
    * @return bool  true - в случае если группа есть, иначе false
    */
    public  function isValid()
    {
        $result = !($this->student->getGroup() instanceof NullGroup);
        if ( ! $result  ) {
            $this->brokenRuleMessage = 'Выберите для студента группу.';
        } 
        return  $result;     
    }
}
