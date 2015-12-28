<?php
namespace framework\engine\utils;

/**
 * Служебный класс, определяющий ряд универсальных методов, 
 * используемых остальными классами.
 * 
 * @author Смирнов С.Л.
 * @version 1.0.0.1
 * @copyright none
 */
 final class BaseService
{
   /**
    * Метод проверяет начинается ли данная строка ($source) с заданных символов ($needle).
    * 
    * @param string $source проверяемая строка.
    * @param string $needle искомая строка.
    * @return bool true - в случае если строка $source начинается со строки $needle, в противном случае false.
    */
    static public function strStartsWith($source, $needle)
    {
        if (self::isNotString($source) && self::isNotString($needle)){
            return false;
        }
        return substr_compare($source, $needle, 0, strlen($needle)) === 0;
    }
    
    /**
    * Метод проверяет заканчивается ли данная строка ($source) заданными символами ($needle).
    * 
    * @param string $source проверяемая строка.
    * @param string $needle искомая строка.
    * @return bool true - в случае если строка $source заканчивается строкой $needle, в противном случае false.
    */
    static public function strEndsWith($source, $needle)
    {
        if (self::isNotString($source) && self::isNotString($needle)){
            return false;
        }
        $length = strlen($needle);
        return (substr($source, -$length) === $needle);
    }
    
    static public function getIp()
    {
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : "null";
    }
    
   /**
    * Метод проверяет является ли передаваемый параметр целым положительным числом.
    * 
    * @param mixed $id проверяемое значение.
    * @return bool true - в случае если $id целое и >= -1, в противном случае - false .
    */
    static public function isId ($id)
    {
        if(is_string($id) && preg_match('/^[0-9]+$/', $id)) {
            $id = (int)$id;
        }
        return (is_int($id) && $id >= -1);
    }
    
    
    /**
    * Метод проверяет не является ли передаваемый параметр НЕ строкой
    * или пустой строкой.
    * 
    * @param string $str проверяемая строка.
    * @return bool true - в случае если параметр НЕ строка или пустая строка, в противном случае false.
    */
    static public function isNotString ($str)
    {
        return (!is_string($str) || strlen(trim($str)) == 0);
    }
    
    /**
    * Метод проверяет является ли передаваемый параметр строкой
    * и эта строка не пуста.
    * 
    * @param string $str проверяемая строка.
    * @return bool true - в случае если параметр  строка и строка не пуста, в противном случае false.
    */
    static public function isFullStr ($str)
    {
        return (is_string($str) && strlen(trim($str)) > 0);
    }
    
    /**
    * Метод проверяет может ли передаваемый параметр 
    * считаться фамилией, именем человека, пройдя 
    * проверку с регулярным выражением.
    * 
    * @param string $str проверяемая строка.
    * @param int $minLength min длина строки.
    * @param int $maxLength mах длина строки.
    * @return bool true - в случае если проверка успешно прошла.
    */
    static public function isPersonName ($str, $minLength = 3, $maxLength = 128)
    {
        return preg_match("/^[-'\. A-Za-zА-Яа-я\s]{{$minLength},{$maxLength}}+$/u", $str);
 
    }
    
   /**
    * Метод проверяет может ли передаваемый параметр 
    * считаться наименованием какого-либо подразделения.
    * 
    * @param string $str проверяемая строка.
    * @param int $minLength min длина строки.
    * @param int $maxLength mах длина строки.
    * @return bool true - в случае если проверка успешно прошла.
    */
    static public function isDepartmentName ($str, $minLength = 2, $maxLength = 128)
    {
        return preg_match("/^[-'\._ 0-9A-Za-zА-Яа-я\s]{{$minLength},{$maxLength}}+$/u", $str);
 
    }
    
   /**
    * Метод проверяет может ли передаваемый параметр 
    * считаться корректным значением из config-файла.
    * 
    * @param string $str проверяемая строка.
    * @param int $minLength min длина строки.
    * @param int $maxLength mах длина строки.
    * @return bool true - в случае если проверка успешно прошла.
    */
    static public function isParamCfg ($str, $minLength = 2, $maxLength = 48)
    {
        return preg_match("/^[-\._0-9A-Za-z]{{$minLength},{$maxLength}}+$/u", $str);
 
    }
    
    static public function getCurrentUserName()
    {
        return "Current user"; 
    }
    
}
