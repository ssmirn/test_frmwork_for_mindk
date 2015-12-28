<?php
namespace src\DomainModel\Rules;

/**
 * Абстракция правила бизнес-логики. Все классы реализующие конкретные правила проверки наследуют данный класс.
 * 
 * @author Смирнов С.Л.
 * @version 1.0.0.1
 * @copyright none
 */
abstract class RuleBase
{
    protected $brokenRuleMessage;       //@var string сообщение о нарушенном правиле
    private static $brokenTotalMessage; //@var string суммарное сообщение о ВСЕХ нарушенных правилах
    
    abstract public function isValid();
    
   /**
    * Метод вызывает проверку для каждого экземпляра правила ($eachRulesToCheck->isValid())
    * и в случае если правило нарушено копирует его в массив $brokenRules.
    * А $brokenRules - ссылочный аргумент, который и вернет клиентскому коду все
    * нарушенные им правила.
    * 
    * @param  array $rulesToCheck массив с правилами.
    * @param  array $brokenRules  массив, в который копируются экземпляры нарушенных правил (isValid() == false) из $rulesToCheck.
    */
    public static function collectBrokenRules(array $rulesToCheck, array &$brokenRules)
    {
        foreach ($rulesToCheck as $eachRulesToCheck) {
            if (!$eachRulesToCheck->isValid()){
                $brokenRules[] = $eachRulesToCheck;
                self::$brokenTotalMessage .= $eachRulesToCheck->brokenRuleMessage . ' ' . PHP_EOL;
            }
        }
    }
    
   /**
    * Возвращает суммарное сообщение о нарушенных правилах
    */
    public static function getTotalMessage()
    {
       return self::$brokenTotalMessage;
    }
    
}
