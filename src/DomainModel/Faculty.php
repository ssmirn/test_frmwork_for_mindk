<?php
namespace src\DomainModel;

use src\DomainModel\NullObjects\NullGroup;
use src\DomainModel\NullObjects\NullFaculty;
use src\DomainModel\Group;
use src\DomainModel\Rules\IsNameValidRule;
use src\DomainModel\Rules\CheckAlreadyExistsNameRule;
use src\DomainModel\Rules\IsExistsIdRule;
use src\DomainModel\Rules\IsIdValidlRule;
use src\DomainModel\Rules\HasFacultyGroupsRule;
use framework\exceptions as FrmworkExcep;

/**
* Обеспечивает работу с факультетом.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class Faculty extends BaseObject
{
    private $arrGroups; //@var array  группы данного факультета
    
    //const KEY_CACH_FACULTY_ALL    = 'Faculty_All';     // ключи для кэширования
    //const KEY_CACH_FACULTY_ITEM   = 'Faculty_Item_Id_';
    
    public function __construct($name = '', $id = -1) 
    {
        parent::__construct($name, $id);
    }
    
   /**
    * Возвращает группы данного факультета или array(new NullGroup()) - 
    * если факультет не содержит групп.
    * 
    * @return array  группы данного факультета.
    */
    public function getGroups()
    {
        if (!$this->arrGroups){
            try {
                $cmdSelect = self::getDbConn()->prepare('SELECT * FROM group_acad WHERE id_faculty = :id_faculty');
                $cmdSelect->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, Group::class);
                $cmdSelect->execute(array('id_faculty'=> $this->getId()));
                $resArr = $cmdSelect->fetchAll();
             } catch (Exception $ex){
                 throw new FrmworkExcep\DbException ('Невозможно отобразить данные.' . $ex->getMessage() . '.', Faculty::class, __METHOD__);
            }
            if ($resArr){
                $this->arrGroups = new \ArrayObject($resArr);
            } else {
                $this->arrGroups = new \ArrayObject(array(new NullGroup()));
            } //if ($resArr){
        } 
        return $this->arrGroups;
    }
     
    /**
    * Сортируем факультеты по наименованию.
    * 
    * @param array факультеты.
    */
    protected static function doSortArrObj(\ArrayObject $arrObj) 
    {
        $arrObj->uasort( create_function('$ctL,$ctR','return strcmp(mb_strtoupper($ctL->getName()), mb_strtoupper($ctR->getName()));'));
    }
    
   /**
    * Возвращаем array(new NullFaculty()), используется если факультетов в БД нет.
    * 
    * @return array 
    */
    protected static function doGetNullArr()
    {
        return new \ArrayObject(array(new NullFaculty()));
    }
    
   /**
    * Конвертируем из stdClass и возвращаем факультет или NullFaculty.
    * 
    * @param object stdClass 
    * @return object факультет
    */
    protected static function doGetSelfById(\stdClass $obj)
    {
        if ($obj->isNull){
            return new NullFaculty();
        }
        try {
            $result = new Faculty($obj->name, $obj->id);
        } catch (Exception $ex){
            throw new FrmworkExcep\CreateObjectException ('Невозможно отобразить данные.' . $ex->getMessage() . '.', Faculty::class, __METHOD__);
        }
        return $result;
    }
    
   /**
    * Возвращаем наименование соотв. таблицы в БД.
    * 
    * @return string наименование соотв. таблицы в БД.
    */
    protected static function getNameTable() 
    { 
        return 'faculty'; 
    }
     
    /**
    * Выполняем проверку правил и в случае успешной проверки
    * сохраняем факультет в БД.
    * 
    * @return bool true-сохранение в БД прошло успешно, false - не сохранились.
    */
    public function save()
    {
        $result = false;
        if($this->getCheckRules() == self::ON_RULES){
            $minLengthName = 2;  //@var int min длина наименования факультета
            $maxLengthName = 52; //@var int max длина наименования факультета
            // подключаем правила для проверки:
            $this->persistenceRules = array(new IsIdValidlRule($this),
                                            new IsNameValidRule($this->getName(), $minLengthName, $maxLengthName, IsNameValidRule::NAME_DEPARTMENT),
                                            new CheckAlreadyExistsNameRule(self::getAllItems()->getArrayCopy(), $this));
            // проверяем результат проверки:
            if (!$this->isValid()){
                return $result;
            }
        }
        //в parent::save() - вызывается или insert или update, в зависимости от значения $this->id:
        $result = parent::save();
        /*if ($result){
            self::deleteFromCache(self::KEY_CACH_FACULTY_ALL);
            self::putCache(self::KEY_CACH_FACULTY_ITEM . $this->getId(),  $this);
        }*/
        return $result;
    }
        
   /**
    * Создаем новую запись в БД.
    * 
    * @return bool true-сохранение в БД прошло успешно, false - не сохранились.
    */
    protected function insert ()
    {
        try {
            self::getDbConn()->beginTransaction();
            $cmdInsert = self::getDbConn()->prepare('INSERT INTO ' . self::getNameTable() . ' (name) VALUES (:name)');
            $cmdInsert->execute(array('name' => $this->getName()));
            $this->id = self::getDbConn()->lastInsertId(self::class);
            self::getDbConn()->commit();
        } catch (FrmworkExcep\FrameworkException $exFrm){
            self::getDbConn()->rollBack();
            $exFrm->redirectToExcepPage();
        } catch (\Exception $ex){
            self::getDbConn()->rollBack();
            throw new FrmworkExcep\DbException ('Невозможно сохранить факультет "' . $this->getName() . '" в БД.' . $ex->getMessage() . '.', Faculty::class, __METHOD__);
        }
        return $cmdInsert->rowCount() > 0;
    }
    
   /**
    * Обновляем существующую запись в БД.
    * 
    * @return bool true-сохранение в БД прошло успешно, false - не сохранились.
    */
    protected function update ()
    {
        try {
            self::getDbConn()->beginTransaction();
            $cmdUpdate = self::getDbConn()->prepare('UPDATE ' . self::getNameTable() . ' SET name = :name WHERE id = :id');
            $cmdUpdate->execute(array('name' => $this->getName(), 'id' => $this->getId()));
            self::getDbConn()->commit();
        } catch (FrmworkExcep\FrameworkException $exFrm){
            self::getDbConn()->rollBack();
            $exFrm->redirectToExcepPage();
        } catch (\Exception $ex){
            self::getDbConn()->rollBack();
            throw new FrmworkExcep\DbException ('Невозможно сохранить факультет "' . $this->getName() . '" в БД.' . $ex->getMessage() . '.', Faculty::class, __METHOD__);
        }    
        return $cmdUpdate->rowCount() > 0;
    }
    
   /**
    * Удаляем факультет из БД.
    * 
    * @return bool true-удаление из БД прошло успешно, иначе - false.
    */
    public function delete()
    {
        if($this->getCheckRules() == self::ON_RULES){
             // подключаем правила для проверки:
            $this->persistenceRules = array(new IsExistsIdRule(self::getAllItems()->getArrayCopy(), $this),
                                            new HasFacultyGroupsRule($this));
            // проверяем результат проверки:
            if (!$this->isValid()){
                return false;
            }
        }
        //в родителе-BaseObject непосредственное удаление из БД:
        $result = parent::delete();
      //  self::deleteFromCache(self::KEY_CACH_FACULTY_ALL);
      //  self::deleteFromCache(self::KEY_CACH_FACULTY_ITEM . $this->getId());
        return $result;
    }
    
   // protected static function doGetKeyForCacheAllItems() { return self::KEY_CACH_FACULTY_ALL; }
    
   // protected static function doGetKeyForCacheItem()     { return self::KEY_CACH_FACULTY_ITEM; }
    
    public function __toString( )  { return $this->getName(); }
}
