<?php
namespace src\DomainModel\Rules;

use framework\engine\utils\BaseService;

/**
 * Правило проверяющее допустимость наименования.
 * 
 * @author Смирнов С.Л.
 * @version 1.0.0.1
 * @copyright none
 */
class IsNameValidRule extends RuleBase
{
    const NAME_PERSON     = 0;
    const NAME_DEPARTMENT = 1;
    private $codeName     = self::NAME_PERSON;
    
    private $name;          //@var string проверяемое имя.
    private $minLengthName; //@var int min длина имени.
    private $maxLengthName; //@var int max длина имени.
    
    public function __construct($name, $minLength = 2, $maxLength = 128, $codeName = self::NAME_PERSON)
    {
        $this->name          = $name;
        $this->minLengthName = $minLength;
        $this->maxLengthName = $maxLength;
        $this->codeName      = $codeName;
        $this->brokenRuleMessage = '';
    }
    
   /**
    * Метод проверяет правильность имени.
    * 
    * @return bool возвращает true, если имя успешно прошло проверку,  иначе false.
    */
    public  function isValid()
    {
        if ($this->codeName == self::NAME_PERSON){
            $result = BaseService::isPersonName ( $this->name,  $this->minLengthName, $this->maxLengthName);
        } elseif ($this->codeName == self::NAME_DEPARTMENT) {
            $result = BaseService::isDepartmentName( $this->name,  $this->minLengthName, $this->maxLengthName );
        }
        if (!$result){
            $this->brokenRuleMessage ='"' . $this->name . '" - недопустимое имя.';
        }
        return $result;
    }
}

