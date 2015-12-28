<?php
namespace src\DomainModel;

use src\DomainModel\NullObjects\NullStudent;
use src\DomainModel\Rules\IsNameValidRule;
use src\DomainModel\Rules\CheckAlreadyExistsNameRule;
use src\DomainModel\Rules\IsIdValidlRule;
use src\DomainModel\Rules\CorrectAgeRule;
use src\DomainModel\Rules\CorrectGroup;
use framework\exceptions as FrmworkExcep;

/**
* Обеспечивает работу со студентом.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class Student extends BaseObject
{
    private $surname = '';  //@var string фамилия 
    public function getSurname()     { return (string)$this->surname; }
    public function setSurname($val) { $this->surname = (string)$val; }
    
    private $firstname = ''; //@var string имя 
    public function getFirstname()     { return (string)$this->firstname; }
    public function setFirstname($val) { $this->firstname = (string)$val; }
    
   /**
    * Возвращает фамилию и имя.
    * 
    * @return string фамилия и имя
    */
    public function getName()     
    { 
        return $this->getSurname() . ' ' . $this->getFirstname(); 
    }
    public function setName($val) {  }
    
    const FEMALE = 0;
    const MALE = 1;
    private $sex = self::FEMALE; //@var int пол
    public function getSex()     { return ($this->sex == self::FEMALE) ? 'жен.' : 'муж.'; }
    public function setSex($val) { $this->sex = $val; }
    
    private $age = 0;   //@var int возраст
    public function getAge()     { return (int)$this->age; }
    public function setAge($val) { $this->age = (int)$val; }
    
    private $idGroup = 0;  //@var int идентификатор группы, в которой учится студент
    private $group = null; //@var object группa, в которой учится студент
    
   /**
    * Возвращаем группу, в которой учится студент.
    * 
    * @return object группa
    */
    public function getGroup()     
    { 
        return ($this->group) ? $this->group : Group::getSelfById($this->idGroup); 
    }
    public function setGroup($val) { $this->group = $val; }
    
    /**
    * Возвращаем факультет на котором учится студент.
    * 
    * @return object факультет
    */
    public function getFaculty() 
    { 
        return $this->getGroup()->getFaculty();
    }
    
  //  const KEY_CACH_STUDENT_ITEM = 'Student_Item_Id_';  // ключ для кэширования
    
    public function __construct( $surname = '', $firstname = '', $sex = self::FEMALE, $age = 0, $idGroup = 0, $id = -1) 
    {
        parent::__construct(null, $id);
        $this->surname   = $surname;
        $this->firstname = $firstname;
        $this->sex       = $sex;
        $this->age       = $age;
        $this->idGroup   = $idGroup;
    }
      
    /**
    * Конвертируем из stdClass и возвращаем студента или NullStudent.
    * 
    * @param object stdClass 
    * @return object студент
    */
    protected static function doGetSelfById(\stdClass $obj)
    {
        if ($obj->isNull){
            return new NullStudent();
         }
        try {
            $result = new Student( $obj->surname, $obj->firstname, $obj->sex, $obj->age, $obj->id_group, $obj->id);
        } catch (Exception $ex){
            throw new FrmworkExcep\CreateObjectException ('Невозможно отобразить данные.' . $ex->getMessage() . '.', Student::class, __METHOD__);
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
        return 'student';
    }
      
   /**
    * Выполняем проверку правил и в случае успешной проверки
    * сохраняем студента в БД.
    * 
    * @return bool true-сохранение в БД прошло успешно, false - не сохранились.
    */
    public function save()
    {
        $result = false;
        if($this->getCheckRules() == self::ON_RULES){
            $minLengthName = 3;   //@var int min длина имени и фамилии
            $maxLengthName = 128; //@var int max длина имени и фамилии 
            // подключаем правила для проверки:
            $this->persistenceRules = array(new IsIdValidlRule( $this ),
                                            new IsNameValidRule($this->getName(), $minLengthName, $maxLengthName, IsNameValidRule::NAME_PERSON),
                                            new CorrectAgeRule( $this->age ), 
                                            new CorrectGroup( $this ),
                                            new CheckAlreadyExistsNameRule( self::getAllItems()->getArrayCopy(), $this ));
            // проверяем результат проверки:
            if (!$this->isValid()){
                return $result;
            }
        }
        //в parent::save() - вызывается или insert или update, в зависимости от значения $this->id:
        $result = parent::save();
    //    if($result){
    //        self::deleteFromCache(Group::KEY_CACH_GROUP_ITEM  . $this->idGroup);
    //        self::putCache(self::KEY_CACH_STUDENT_ITEM . $this->getId(),  $this);
    //    }
        return $result;
    }
    
   /**
    * Создаем нового студента в БД.
    * 
    * @return bool true-сохранение в БД прошло успешно, false - не сохранились.
    */
    protected function insert ()
    {
        try {
            self::getDbConn()->beginTransaction();
            $cmdInsert = self::getDbConn()->prepare('INSERT INTO ' . self::getNameTable() . ' (surname, firstname, sex, age, id_group) VALUES (:surname, :firstname, :sex, :age, :id_group)');
            $cmdInsert->execute(array('surname'   => $this->getSurname(), 
                                      'firstname' => $this->getFirstname(),
                                      'sex'       => $this->sex,
                                      'age'       => $this->getAge(),
                                      'id_group'  => $this->idGroup));
            $this->id = self::getDbConn()->lastInsertId(self::class);
            self::getDbConn()->commit();
        } catch (FrmworkExcep\FrameworkException $exFrm){
            self::getDbConn()->rollBack();
            $exFrm->redirectToExcepPage();
        } catch (\Exception $ex){
            self::getDbConn()->rollBack();
            throw new FrmworkExcep\DbException ('Невозможно сохранить студента "' . $this->getName() . '" в БД.' . $ex->getMessage() . '.', Student::class, __METHOD__);
        }
        return $cmdInsert->rowCount() > 0;
    }
    
    /**
    * Обновляем существующего студента в БД.
    * 
    * @return bool true-сохранение в БД прошло успешно, false - не сохранились.
    */
    protected function update ()
    {
        try {
            self::getDbConn()->beginTransaction();
            $cmdUpdate = self::getDbConn()->prepare('UPDATE ' . self::getNameTable() . ' SET surname = :surname, firstname = :firstname, sex = :sex, age = :age, id_group = :id_group WHERE id = :id');
            $cmdUpdate->execute(array('surname'   => $this->getSurname(),
                                      'firstname' => $this->getFirstname(),
                                      'sex'       => $this->sex,
                                      'age'       => $this->getAge(),
                                      'id_group'  => $this->idGroup,
                                      'id'        => $this->getId()));
            self::getDbConn()->commit();
        } catch (FrmworkExcep\FrameworkException $exFrm){
            self::getDbConn()->rollBack();
            $exFrm->redirectToExcepPage();
        } catch (\Exception $ex){
            self::getDbConn()->rollBack();
            throw new FrmworkExcep\DbException ('Невозможно сохранить студента "' . $this->getName() . '" в БД.' . $ex->getMessage() . '.', Student::class, __METHOD__);
        }    
        return $cmdUpdate->rowCount() > 0;
    }
    
    /**
    * Удаляем студента из БД.
    * 
    * @return bool true-удаление из БД прошло успешно, иначе - false.
    */
    public function delete()
    {
        $result = parent::delete();
    //    self::deleteFromCache(Group::KEY_CACH_GROUP_ITEM  . $this->idGroup);
    //    self::deleteFromCache(self::KEY_CACH_STUDENT_ITEM  . $this->getId());
        return $result;
    }
    
    //protected static function doGetKeyForCacheAllItems() { return null; }
    
    //protected static function doGetKeyForCacheItem()     { return self::KEY_CACH_STUDENT_ITEM; }
        
    public function __toString( )  { return $this->getName(); }
}
