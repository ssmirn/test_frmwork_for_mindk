<?php

namespace src\DomainModel\Rules;

/**
 * Интерфейс для классов предметной области, которые должны
 * реализовать функционал для прохождения проверки правил.
 * 
 * @author Смирнов С.Л.
 * @version 1.0.0.1
 * @copyright none
 */
interface IRule
{
    public function brokenRules();
    public function isValid();
    public function getBrokenRulesTotalMessage();
}
