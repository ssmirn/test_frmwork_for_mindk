<?php
namespace src\DomainModel\Rules;

use framework\engine\utils\BaseService;

/**
 * Правило проверяющее корректность возраста студента.
 * 
 * @author Смирнов С.Л.
 * @version 1.0.0.1
 * @copyright none
 */
class CorrectAgeRule extends RuleBase
{
    private $age;  //@var int возраст 
    
    public function __construct( $age )
    {
        $this->age = $age;
        $this->brokenRuleMessage = '';
    }
    
  /**
    * Метод проверяет допустимость применения значения $age в качестве возраста.
    * 
    * @return bool  true - возраст верен, иначе false.
    */
    public  function isValid()
    {
        $result = (BaseService::isId($this->age) && (16 <= $this->age && $this->age <= 50));
        if (!$result){
            $this->brokenRuleMessage = '"' . $this->age . '" - недопустимый возраст.';
        }                       
        return  $result;     
    }
}
