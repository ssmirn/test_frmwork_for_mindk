<?php
namespace src\DomainModel\NullObjects;

use src\DomainModel\Student;

/**
* Реализация решения Null Object для класса Student.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class NullStudent extends Student
{
    public function getSurname()     { return 'Нет студентов.'; }
    public function setSurname($val) {  }
    
    public function getFirstname()     { return ''; }
    public function setFirstname($val) {  }
    
    public function getSex()     { return 0; }
    public function setSex($val) {  }
    
    public function getAge()     { return 0; }
    public function setAge($val) { }
    
    private $idGroup = 0;
    private $group = null;
    public function getGroup()   { return parent::getGroup(); }
    public function setGroup($val) { }
    
    public function getFaculty() { return parent::getFaculty(); }
    
    public function __construct( ) { }
       
    protected static function getNameTable() { return ''; }
    
    protected static function doGetSelfById(\stdClass $obj) { return new $this; }
    
    protected static function doSortArrObj(\ArrayObject $arrObj) { }
    
    public function save() {}
    
    protected function insert () {}
    
    protected function update () { }
    
    public function delete() { }
    
    protected static function doGetKeyForCacheAllItems() { return ''; }
    
    protected static function doGetKeyForCacheItem()     { return ''; }
        
    public function __toString( ) { return $this->getSurname(); }
}
