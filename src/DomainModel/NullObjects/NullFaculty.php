<?php
namespace src\DomainModel\NullObjects;

use src\DomainModel\Faculty; 

/**
* Реализация решения Null Object для класса Faculty.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class NullFaculty extends Faculty
{
    public function getName()     { return  'Нет данных.'; }
    public function setName($val) {  }
  
    public function getGroups() { return parent::getGroups(); }
    
    public function __construct()  { }
    
    protected static function getNameTable()  { return ''; }
    
    protected static function doSortArrObj(\ArrayObject $arrObj) {}
    
    protected static function doGetSelfById(\stdClass $obj) { return new $this; }
        
    public function save() { }
        
    protected function insert () { }
    
    protected function update () { }
    
    public function delete() { }
    
    protected static function doGetKeyForCacheAllItems() { return ''; }
    
    protected static function doGetKeyForCacheItem()     { return ''; }
    
    public function __toString( ) 
    {
        return $this->getName();
    }
}
