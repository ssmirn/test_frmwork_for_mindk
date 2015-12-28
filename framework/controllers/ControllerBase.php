<?php
namespace framework\controllers;

use framework\engine\registry\FrameworkRegistry;
use framework\engine\routing\Routing;
use framework\engine\Response;
use framework\exceptions as FrmworkExcep;
use framework\engine\utils\BaseService;

/**
* Родительский класс для контролеров.
* Из экземпляра Routing получает данные о контролере, действии (методе),
* аргументах действия, соотв. представлении, возможном маршруте для редиректа из 
* данного действия. 
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
abstract class ControllerBase 
{
    protected static $enumResultAction = array (
        'RES_ACT_DEF'     => 0,
        'RES_ACT_OK'      => 1,
        'RES_ACT_ERR'     => 2,
        'RES_ACT_NO_DATA' => 3
    ); //@var array перечисление с результатами выполнения действия, в зависимости от 
       // установленного значения будет вызвано соотв. представление или форвард на другой маршрут.
       // Установка значения $enumResultAction не обязательна, если в карте маршрутизации
       // отсутствует соотв. значение для вызываемого представления или редиректа на другой маршрут.
    protected $status  = 0;     //@var int выбранное значение из $enumResultAction
    protected $routing = null;  //@var Routing
    private   $isStoredController  = false; //@var bool флаг для сохран.экземпляра вызываемого контролера в Request-е
                                            //применяется в методе saveController (),
                                            //далее  используется при форварде на другой маршрут.
       
    public final function __construct(Routing $routing) 
    { 
        $this->routing = $routing;
        $this->status  = self::$enumResultAction['RES_ACT_DEF'];
    }
    
    /**
    *  Вызывает заданное действие (метод) контролера. 
    */
    public final function run( ) 
    {
        $this->isStoredController = ($this instanceof DefaultController);
        if ( ! $this->routing ) {
            throw new FrmworkExcep\NullReferenceException( '$this->routing == null.',
                                                           ControllerBase::class, __METHOD__, 'Проверте таблицу маршрутизации.');
        }
        // вызываем выполнение необходимого действия
        // текущего контролера:
        $this->routing->executeAction();
        $this->saveController();
        return;
   }
   
    /**
    * Метод вызывает редирект на другой маршрут через routing->programmRedirectToRoute 
    * 
    * Редирект на другой маршрут может быть выполнен двумя способами:
    *       - неявно через тег <redirect> в таблице маршрутизации, т.е. фраймворк
    *        самостоятельно отправит Вас на заданный маршрут, в зависимости от 
    *        установленного значения  $enumResultAction;
    *      - или через вызов данного метода в любом действии контролера.
    *
    * @param string $nameRoute     - имя маршрута для форварда
    * @param array  $arrParameters - массив с параметрами для действия вызываемого маршрута
    */
    public final function programmRedirectToRoute( $nameRoute, $arrParameters = array() ) 
    { 
        if ( BaseService::isNotString($nameRoute) ) {
            throw new FrmworkExcep\InvalidArgumentException('Неверный аргумент метода.',
                                                            ControllerBase::class, __METHOD__, '$nameRoute', 'string');
        }
        $this->routing->programmRedirectToRoute( $nameRoute, $arrParameters ); 
    }
    
    /**
    * Добавляем параметры действия для автоматически вызываемого маршрута
    * 
    * @param array  $arrParameters - массив с параметрами для действия вызываемого маршрута
    */
    public final function addParametersForAutoRedirectRoute( $arrParameters ) 
    { 
        if ( ! is_array($arrParameters) ) {
            throw new FrmworkExcep\InvalidArgumentException(self::$msgForInvalidArgExcp,
                                                            Routing::class, __METHOD__, '$arrParameters', 'array');
        }
        $this->routing->addParametersForRedirectRoute( $arrParameters ); 
    }
    
    /**
    * Возвращаем установленный результат выполнения действия контролера 
    * 
    * @return int 
    */
    public final function getStatus() 
    { 
        if ( ! $this->status ){ $this->status = self::$enumResultAction['RES_ACT_DEF']; }
        return (int)$this->status; 
    }

   /**
    * Возвращаем соотв. переданному параметру $strStatusArg
    * целочисленное значение из перечисления $enumResultAction
    *
    * @param string $strStatusArg - наименование соотв. одному из ключей $enumResultAction
    * @return int 
    */
    public static function statuses( $strStatusArg='RES_ACT_DEF' ) 
    {
        if ( empty( $strStatusArg ) ) { $strStatusArg = 'RES_ACT_DEF'; }
        if (!array_key_exists($strStatusArg, self::$enumResultAction)) { $strStatusArg = 'RES_ACT_DEF'; }
        return (int)self::$enumResultAction[$strStatusArg];
    }

   /**
    * Сохраняем экземпляр выполненного контролера в FrameworkRegistry
    */
    protected final function saveController ()
    {
        if ( !$this->isStoredController ) {
            FrameworkRegistry::setExecutedController($this);
        }
        $this->isStoredController = true;
    }
    
    /**
    * Вызов соотв. представления. Вызывается в действии контролера
    * в случае если необходимо вызвать шаблон, указанный в таблице маршрутизации.
    *
    * @param array $arrViewData - массив с данными для заполнения шаблона
    */
    protected final function getView( $arrViewData = array() ) 
    { 
        $this->saveController();
        $this->getViewByNameTemplate( $this->routing->getViewName(), $arrViewData ); 
    }
    
   /**
    * Вызов явно заданного представления. Вызывается в действии контролера
    * в случае если необходимо вызвать именованный шаблон.
    *
    * @param string $nameTemplateView - имя вызываемого шаблона
    * @param array $arrViewData       - массив с данными для заполнения шаблона
    */
    protected final function getViewByNameTemplate( $nameTemplateView, $arrViewData ) 
    { 
        if ( BaseService::isNotString($nameTemplateView) ) {
            throw new FrmworkExcep\InvalidArgumentException('Неверный аргумент метода.',
                                                            ControllerBase::class, __METHOD__, '$nameTemplateView', 'string');
        }
        (new Response())->preparePage($nameTemplateView, $arrViewData)->sendPage(); 
    }
    
}

