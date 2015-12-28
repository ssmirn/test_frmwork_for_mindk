<?php
namespace src\DomainModel\NullObjects;

use src\DomainModel\Group;

/**
* Реализация решения Null Object для класса Group.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class NullGroup extends Group
{
    public function getName()     { return  'Нет групп.'; }
    public function setName($val) {  }
    
    public function getFaculty() { return parent::getFaculty(); }
    public function setFaculty($val) {  }
    
    public function getStudents() { return parent::getStudents(); }
    
    public function __construct()  {  }
    
    protected static function getNameTable() { return ''; }
    
    protected static function doGetSelfById(\stdClass $obj) { return new $this; }
    
    protected static function doSortArrObj(\ArrayObject $arrObj) { }
    
    public function save() { }
        
    protected function insert () { }
    
    protected function update () { }
    
    public function delete() { }
    
    protected static function doGetKeyForCacheAllItems() { return ''; }
    
    protected static function doGetKeyForCacheItem()     { return ''; }
      
    public function __toString( )  { return $this->getName();}
}
