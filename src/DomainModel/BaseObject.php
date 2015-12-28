<?php
namespace src\DomainModel;

use src\DbConn\DbPdo;
use framework\engine\utils\BaseService;
use src\DomainModel\Rules\IRule;
use src\DomainModel\Rules\RuleBase;
use framework\engine\registry\SessionRegistry;
use framework\exceptions as FrmworkExcep;

/**
* Родительский класс для Faculty, Group, Student. 
* Включает в себя общие для всех потомков данные, с помощью SQL-запросов работает с базой данных. 
* Также BaseObject реализует интерфейс IRule, тем самым обеспечивая возможность проверки правил бизнес-логики для всех своих потомков.
* Другими словами BaseObject взаимодействует с базой данных и содержит логику домена. Такое поведение подходит
* под определение решения Active Record. Но, строго говоря, структура данных класса, реализующего Active Record, 
* должна в точности соответствовать записи в соответствующей таблице базы данных, т.е. каждое поле объекта должно
* соответствовать одному столбцу таблицы или представлению базы данных. Поэтому при строгом подходе к реализации
* Active Record следовало бы создать лишь три класса - Faculty, Group и Student, поля каждого из которых полностью соответствовали бы
* полям заданной таблицы БД, связать их SQL-запросами и "наполнить" классы логикой. Логика домена в нашем случае - это правила, 
* накладываемые на данные и их структуру, например, не стоит разрешать удалять группу, если в ней существуют студенты, не следует  
* разрешать удалять факультет, если в нем существуют группы... Есть правила, очень похожие для всех трех классов
* (Faculty, Group и Student): например, хорошо было бы, чтобы пользователь не внес несколько одинаковых названий факультетов
* или групп.
*  Разумеется мы создадим классы реализующие правила проверки (вместе с их родителем - RuleBase). 
* А в Faculty, Group и Student будем их вызывать, подставляя разные параметры, затем обработаем результаты проверки. 
* Кроме этого во всех трех таблицах существуют еще и одинаковые поля, например id и name, 
* некоторые запросы у всех трех классов будут напоминать друг друга: 'SELECT * FROM NAME_TABLE WHERE id = :id' или 
* 'DELETE FROM NAME_TABLE WHERE id = :id'. И вероятней всего, что на этой почве мы получим дублирование кода в Faculty, Group и Student...
*  Чтобы избежать дублирования можно(нужно) общее поведение вынести в общего родителя - BaseObject. Но ценой одного из принципов Active Record...
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
abstract class BaseObject implements IRule
{
    private static $DbConn; //@var DbPdo 
        
    const OFF_RULES = 0;
    const ON_RULES  = 1;
    protected $codeModeRules; //@var int если 0  то правила откл., 1 - правила вкл. (по умолчанию вкл. в конструкторе)
    protected function getCheckRules() { return $this->codeModeRules; }
    public    function OnCheckRules()  { $this->codeModeRules = self::ON_RULES; }  // вкл. правила
    public    function OffCheckRules() { $this->codeModeRules = self::OFF_RULES; } // откл. правила
    
    protected $persistenceRules   = array();//@var array RuleBase массив подключаемых правил проверки бизнес-логики
    private   $checkedRuleMessage = '';     //@var string сообщение с результатом проверки всех правил бизнес-логики
    
    const NEW_RECORD = -1;
    protected $id = self::NEW_RECORD; //@var int идентификатор записи
    public    final function getId()     { return (int)$this->id; }
    protected final function setId($val) { $this->id = (BaseService::isId($val)) ? (int)$val : -1; }
    
    protected $name = ''; //@var string имя факультета, группы, студента
    public function getName()     { return (string)$this->name; }
    public function setName($val) { $this->name = (string)$val; }
    
    public function __construct($name = '', $id = -1) 
    {
        $this->setName($name);
        $this->setId($id);
        $this->OnCheckRules();
        self::$DbConn = self::getDbConn();
    }
    
   /**
    * Возвращаем соединение с БД. 
    */
    protected final static function getDbConn()
    {
        return (self::$DbConn) ? self::$DbConn : DbPdo::conn();
    }
    
   /**
    * Возвращаем отсортированный массив объектов, в зависимости от значения static::getNameTable().
    * Реализует шаблон Template method.
    * Потомоки переопределяют getNameTable(), подставляя свое наименование таблицы БД.
    * Также каждый потомок перегружает static::doSortArrObj($arrObj), задавая свое условие сортировки.
    * В случае отсутствия записей возвращаются соответствующие Null Object, которые будут заданы
    * в каждом классе-потомке путем переопределения static::doSortArrObj($arrObj).
    * 
    * @return array
    */
    public final static function getAllItems ()
    {
       /* if ( static::doGetKeyForCacheAllItems() ){
            $cacheAllItems = self::getCache( static::doGetKeyForCacheAllItems() );
        }
        if ( $cacheAllItems ){
            return $cacheAllItems;
        }*/
        try {
            $cmdSelect = self::getDbConn()->prepare("SELECT * FROM " . static::getNameTable());
            $cmdSelect->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, static::class);
            $cmdSelect->execute();
            $resArr = $cmdSelect->fetchAll();
        } catch (FrmworkExcep\FrameworkException $exFrm){
            $exFrm->redirectToExcepPage();
        } catch (\Exception $ex){
            throw new FrmworkExcep\DbException ('Невозможно отобразить данные.' . $ex->getMessage() . '.', BaseObject::class, __METHOD__);
        }
        if ($resArr){
                $arrObj = new \ArrayObject($resArr);
                static::doSortArrObj($arrObj);
                //if ( static::doGetKeyForCacheAllItems() ){
                //    self::putCache(static::doGetKeyForCacheAllItems(),  $arrObj);
                //}
                return $arrObj;
        }
        return static::doGetNullArr();
    }
    
   /**
    * Потомки задают свой порядок сортировки.
    */
     protected static function doSortArrObj(\ArrayObject $arrObj){}
    
    /**
    * Потомки вернут массив соотв. Null Object.
    */
    protected static function doGetNullArr(){}
     
    /**
    * Возвращаем соотв. объект по заданному $id.
    * Каждый потомок переопределяет getNameTable(), подставляя свое наименование таблицы БД.
    * Также каждый потомок переопределяет static::doGetSelfById($resObj), который
    * конвертирует $resObj (результат выполнения SQL-запроса) в соотвующий объект.
    * 
    * @param int $id
    * @return object
    */
    public final static function getSelfById ($id)
    {
       /*if ( static::doGetKeyForCacheItem() ){
            $cacheItem = self::getCache(static::doGetKeyForCacheItem() . $id);
        }
        if ( $cacheItem ){
            return $cacheItem;
        }*/
        try {
            $cmdSelect = self::getDbConn()->prepare('SELECT * FROM ' . static::getNameTable() . ' WHERE id = :id');
            $cmdSelect->execute(array('id'=> $id));
            $resObj = $cmdSelect->fetch(\PDO::FETCH_OBJ);
        } catch (FrmworkExcep\FrameworkException $exFrm){
            $exFrm->redirectToExcepPage();
        } catch (\Exception $ex){
            throw new FrmworkExcep\DbException ('Невозможно отобразить данные.' . $ex->getMessage() . '.', BaseObject::class, __METHOD__);
        }
        $stdNull = new \stdClass();
        $stdNull->isNull = true;
        $resObj = (is_bool($resObj) && $resObj === false) ? $stdNull : $resObj;
        $result = static::doGetSelfById($resObj);
        //if ( static::doGetKeyForCacheItem() ){
        //    self::putCache(static::doGetKeyForCacheItem() . $id,  $result);
        //}
        return $result;
    }
    
   /**
    * Потомки вернут cвой объект.
    */
    abstract protected static function doGetSelfById(\stdClass $obj);
    
   /**
    * Потомки вернут название cвоей таблицы из БД.
    */
    abstract protected static function getNameTable();
     
    /**
    * Сохраняем запись в БД. В зависимости от значения
    * self::NEW_RECORD вызываем insert или update.
    * Потомки также переопределяют данный метод, где
    * в начале подключат правила, а затем проверят их,
    * вызвав isValid(). А потом вызовут и parent::save().
    * 
    * @param int $result  результат insert или update.
    */
    public function save()
    {
        if ($this->id == self::NEW_RECORD) {
            $result = $this->insert();
        } else {
            $result = $this->update();
        }
        return $result;
    }
    
    /**
    * Потомки переопределят для вставки своего экземпляра в БД.
    */
    abstract protected function insert();
    
    /**
    * Потомки переопределят для обновления своего экземпляра в БД.
    */
    abstract protected function update();
    
   /**
    * Удаляем соотв. объект по заданному $this->getId().
    * Каждый потомок переопределяет getNameTable(), подставляя свое наименование таблицы БД.
    * 
    * @return int  результат выполнения запроса.
    */
    public function delete()
    {
        try {
            self::getDbConn()->beginTransaction();
            $cmdDelete = self::getDbConn()->prepare('DELETE FROM ' . static::getNameTable() . ' WHERE id = :id');
            $cmdDelete->execute(array('id'=> $this->getId()));
            self::getDbConn()->commit();
        } catch (FrmworkExcep\FrameworkException $exFrm){
            self::getDbConn()->rollBack();
            $exFrm->redirectToExcepPage();
        } catch (\Exception $ex){
            self::getDbConn()->rollBack();
            throw new FrmworkExcep\DbException ('Невозможно удалить данные.' . $ex->getMessage() . '.', BaseObject::class, __METHOD__);
        }
        return $cmdDelete->rowCount() > 0;
    }
   
   /**
    * Вызываем проверку правил.
    * Каждый потомок у себя заполнит $this->persistenceRules нужными
    * ему правилами. RuleBase::collectBrokenRules($this->persistenceRules, $brokenRules) -
    * проверит заданные правила и в $brokenRules вернет нарушенные правила, а
    * в RuleBase::getTotalMessage() - описание нарушенных правил.
    * Так как метод универсален и перегружать его в потомках не стоит,
    * пусть будет final, равно как и isValid() и getBrokenRulesTotalMessage().
    * 
    * @return array  массив нарушеных правил.
    */
    public final function brokenRules()
    {
        $brokenRules = array();
        if ($this->getCheckRules() == self::OFF_RULES){
            return $brokenRules;
        }
        RuleBase::collectBrokenRules($this->persistenceRules, $brokenRules);
        $this->checkedRuleMessage = RuleBase::getTotalMessage();
        return $brokenRules;
    }
    
   /**
    * Иницируем проверку правил.
    * Если $this->brokenRules() вернет массив с нарушенным(и)
    * правилом(и) то и isValid() вернет false, а если все
    * проверки прошли успешно, то $this->brokenRules() - пуст
    * и isValid() возвращает true.
    * 
    * @return bool true - проверка правил прошла успешно, иначе - false.
    */
    public final function isValid()
    {
        return count($this->brokenRules()) == 0;
    }
    
    /**
    * Возвращает описание нарушенных правил.
    * 
    * @return string описание нарушенных правил.
    */
    public final function getBrokenRulesTotalMessage()
    {
        return $this->checkedRuleMessage;
    }
    
   /**
    * Для сохранения данных в SessionRegistry.
    * 
    * @param string $key  ключ.
    * @param mixed $value значение предназначенное для кэширования.
    */
    public final static function putCache ($key, $value)
    {
        SessionRegistry::getInstance()->putCache($key, $value);
    }
    
   /**
    * Для извлечения необходимых данных из SessionRegistry.
    * 
    * @param string $key  ключ извлекаемых данных
    * @return mixed 
    */
    public final static function getCache ($key)
    {
        return SessionRegistry::getInstance()->getCache($key);
    }
    
   /**
    * Для удаления данных из SessionRegistry.
    * 
    * @param string $key  ключ удаляемых данных
    */
    public final static function deleteFromCache ($key)
    {
        SessionRegistry::getInstance()->deleteByKey($key);
    }
    
    //protected static function doGetKeyForCacheAllItems() {}
    
    //protected static function doGetKeyForCacheItem() {}
    

}