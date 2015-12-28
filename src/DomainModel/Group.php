<?php
namespace src\DomainModel;

use src\DomainModel\NullObjects\NullStudent;
use src\DomainModel\NullObjects\NullGroup;
use src\DomainModel\Student;
use src\DomainModel\Rules\IsNameValidRule;
use src\DomainModel\Rules\CheckAlreadyExistsNameRule;
use src\DomainModel\Rules\IsIdValidlRule;
use src\DomainModel\Rules\IsExistsIdRule;
use src\DomainModel\Rules\HasGroupStudentsRule;
use framework\exceptions as FrmworkExcep;

/**
* Обеспечивает работу с группой.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class Group extends BaseObject
{
    private $idFaculty = 0;     //@var int идентификатор факультета группы
    private $faculty = null;    //@var object факультет группы
    public function getFaculty()             { return ($this->faculty) ? $this->faculty : Faculty::getSelfById($this->idFaculty); }
    public function setFaculty(Faculty $val) { return $this->faculty = $val; }
    
    private $countStudents = 0; //@var int кол-во студентов в группе
    private $arrStudents;       //@var array студенты обучающиеся в данной группе
    
  //  const KEY_CACH_GROUP_ALL      = 'Group_All';      // ключи для кэширования
  //  const KEY_CACH_GROUP_ITEM     = 'Group_Item_Id_';
    
    public function __construct($name = '', $idFaculty = 0, $id = -1) 
    {
        parent::__construct($name, $id);
        $this->idFaculty = $idFaculty;
        $this->countStudents = 0;
    }
    
   /**
    * Возвращает студентов данной группы или array(new NullStudent()) - 
    * если группа не содержит студентов.
    * 
    * @return array  студенты данной группы.
    */
    public function getStudents()
    {
        if (!$this->arrStudents){
            try {
                $cmdSelect = self::getDbConn()->prepare('SELECT * FROM student WHERE id_group = :id_group ORDER BY surname');
                $cmdSelect->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, Student::class);
                $cmdSelect->execute(array('id_group'=> $this->getId()));
                $resArr = $cmdSelect->fetchAll();
                $this->countStudents = count($resArr);
            } catch (Exception $ex){
                throw new FrmworkExcep\DbException ('Невозможно отобразить данные.' . $ex->getMessage() . '.', Group::class, __METHOD__);
            }
            if ($resArr){
                $this->arrStudents = new \ArrayObject($resArr);
            } else {
                $this->arrStudents = new \ArrayObject(array(new NullStudent()));
            } // if ($resArr){
        } // if (!$this->arrStudents){
        return $this->arrStudents;
    }
    
    /**
    * Возвращает кол-во студентов данной группы.
    * 
    * @return int кол-во студентов данной группы.
    */
    public function getCountStudents()
    {
          if (!$this->arrStudents){
              $this->getStudents();
          }
          return $this->countStudents;
    }
    
    /**
    * Сортируем студентов по имени.
    * 
    * @param array студенты.
    */
    protected static function doSortArrObj(\ArrayObject $arrObj) 
    {
        $arrObj->uasort( create_function('$ctL,$ctR','return strcmp(mb_strtoupper($ctL->getName()), mb_strtoupper($ctR->getName()));'));
    }
    
   /**
    * Возвращаем array(new NullGroup()), пригодится если групп нет в факультете.
    * 
    * @return array 
    */
    protected static function doGetNullArr()
    {
        return new \ArrayObject(array(new NullGroup()));
    }
    
   /**
    * Конвертируем из stdClass и возвращаем группу или NullGroup.
    * 
    * @param object stdClass 
    * @return object группа
    */
    protected static function doGetSelfById(\stdClass $obj)
    {
        if ($obj->isNull){
            return new NullGroup();
        }
        try {
            $result = new Group($obj->name, $obj->id_faculty, $obj->id);
        } catch (Exception $ex){
            throw new FrmworkExcep\CreateObjectException ('Невозможно отобразить данные.' . $ex->getMessage() . '.', Group::class, __METHOD__);
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
        return 'group_acad';
    }
        
   /**
    * Выполняем проверку правил и в случае успешной проверки
    * сохраняем группу в БД.
    * 
    * @return bool true-сохранение в БД прошло успешно, false - не сохранились.
    */
    public function save()
    {
        $result = false;
        if($this->getCheckRules() == self::ON_RULES){
            $minLengthName = 2;  //@var int min длина наименования группы
            $maxLengthName = 12; //@var int max длина наименования группы
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
            self::deleteFromCache(self::KEY_CACH_GROUP_ALL);
            self::deleteFromCache(Faculty::KEY_CACH_FACULTY_ITEM  . $this->idFaculty);
            self::deleteFromCache(Faculty::KEY_CACH_FACULTY_ALL);
            self::putCache(self::KEY_CACH_GROUP_ITEM . $this->getId(),  $this);
        }*/
        return $result;
    }
      
   /**
    * Создаем новую группу в БД.
    * 
    * @return bool true-сохранение в БД прошло успешно, false - не сохранились.
    */
    protected function insert ()
    {
        try {
            self::getDbConn()->beginTransaction();
            $cmdInsert = self::getDbConn()->prepare('INSERT INTO ' . self::getNameTable() . ' (name, id_faculty) VALUES (:name, :id_faculty)');
            $cmdInsert->execute(array('name'       => $this->getName(),
                                      'id_faculty' => $this->idFaculty));
            $this->id = self::getDbConn()->lastInsertId(self::class);
            self::getDbConn()->commit();
        } catch (FrmworkExcep\FrameworkException $exFrm){
            self::getDbConn()->rollBack();
            $exFrm->redirectToExcepPage();
        } catch (\Exception $ex){
            self::getDbConn()->rollBack();
            throw new FrmworkExcep\DbException ('Невозможно сохранить группу "' . $this->getName() . '" в БД.' . $ex->getMessage() . '.', Group::class, __METHOD__);
        }
        return $cmdInsert->rowCount() > 0;
    }
    
   /**
    * Обновляем существующую группу в БД.
    * 
    * @return bool true-сохранение в БД прошло успешно, false - не сохранились.
    */
    protected function update ()
    {
        try {
            self::getDbConn()->beginTransaction();
            $cmdUpdate = self::getDbConn()->prepare('UPDATE ' . self::getNameTable() . ' SET name = :name, id_faculty = :id_faculty WHERE id = :id');
            $cmdUpdate->execute(array('name'       => $this->getName(),
                                      'id_faculty' => $this->idFaculty,
                                      'id'         => $this->getId()));
            self::getDbConn()->commit();
        } catch (FrmworkExcep\FrameworkException $exFrm){
            self::getDbConn()->rollBack();
            $exFrm->redirectToExcepPage();
        } catch (\Exception $ex){
            self::getDbConn()->rollBack();
            throw new FrmworkExcep\DbException ('Невозможно сохранить группу "' . $this->getName() . '" в БД.' . $ex->getMessage() . '.', Group::class, __METHOD__);
        }    
         return $cmdUpdate->rowCount() > 0;
    }
    
    /**
    * Удаляем группу из БД.
    * 
    * @return bool true-удаление из БД прошло успешно, иначе - false.
    */
    public function delete()
    {
        if($this->getCheckRules() == self::ON_RULES){
             // подключаем правила для проверки:
            $this->persistenceRules = array(new IsExistsIdRule(self::getAllItems()->getArrayCopy(), $this),
                                            new HasGroupStudentsRule($this));
            // проверяем результат проверки:
            if (!$this->isValid()){
                return false;
            }
        }
        $result = parent::delete();
    /*  self::deleteFromCache(self::KEY_CACH_GROUP_ALL);
        self::deleteFromCache(Faculty::KEY_CACH_FACULTY_ITEM  . $this->idFaculty);
        self::deleteFromCache(Faculty::KEY_CACH_FACULTY_ALL);
        self::deleteFromCache(self::KEY_CACH_GROUP_ITEM . $this->getId());
     */
        return $result;
    }
    
    //protected static function doGetKeyForCacheAllItems() { return self::KEY_CACH_GROUP_ALL; }
    
    //protected static function doGetKeyForCacheItem()     { return self::KEY_CACH_GROUP_ITEM; }
      
    public function __toString( ) { return $this->name; }
    
}
