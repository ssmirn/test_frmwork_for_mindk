<?php
namespace framework\engine\routing;

use framework\engine\registry\FrameworkRegistry; 
use framework\exceptions as FrmworkExcep;
use framework\controllers\ControllerBase;

/**
* Считывает глобальные настройки и таблицу маршрутизации из config.xml.
* Полученные данные сохраняет в реестре FrameworkRegistry (если тот пуст).
* В случае если в FrameworkRegistry уже есть данные - ничего не делает.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class RouteMapLoader 
{
    private static $instance;
       
    private function __construct() {}

   /**
    * Реализуем Singleton.
    */
    public static function getInstance( )
    { 
        if ( ! isset(self::$instance) ){
            self::$instance = new self();
        }
        return  self::$instance; 
    }

   /**
    * Если реестр FrameworkRegistry не заполнен настройками, 
    * то вызываем метод getSettingsFromConfigFile.
    */
    public function initialize() 
    {
        if ( ! FrameworkRegistry::getHost() || ! FrameworkRegistry::getBaseUrl() ) {
                $this->getSettingsFromConfigFile();
        }
    }

    /**
    * Считываем глобальные настройки и таблицу маршрутизации из config.xml,
    * полученные данные помещаем в реестр FrameworkRegistry.
    * 
    * Note: Таблица маршрутизации сохраняется в экземпляре RouteMap, который
    *       в свою очередь сохраняется в FrameworkRegistry.
    */
    private function getSettingsFromConfigFile()
    {
        $nameCfgFile = 'config.xml';
        $cfgPathFile = '..'     . DIRECTORY_SEPARATOR . 'framework'. DIRECTORY_SEPARATOR . 'app'. DIRECTORY_SEPARATOR . 
                       'config' . DIRECTORY_SEPARATOR . $nameCfgFile;
        if( ! file_exists($cfgPathFile) ) {
            throw new FrmworkExcep\FileNotFoundException ('Конфиг. файлa "' . $nameCfgFile .  '" нет.', RouteMapLoader::class, __METHOD__);
        }
        $settings = @SimpleXml_load_file( $cfgPathFile );
        
        $host = (string)$settings->host; 
        FrameworkRegistry::setHost( $host ); // сохраняем наименование хоста
        
        $baseUrl = (string)$settings->baseUrl; 
        FrameworkRegistry::setBaseUrl( $baseUrl );// сохраняем путь к index.php
        
        $connDB = (string)$settings->connDB; 
        FrameworkRegistry::setConnStringDB( $connDB );// сохраняем путь к БД
            
        $msgForException = 'Ошибка в "' . $nameCfgFile . '".';
        if ( !$host || !$baseUrl ){
            throw new FrmworkExcep\FileFormatException($msgForException, RouteMapLoader::class, __METHOD__, 'Проверьте теги: host, baseUrl.');
        }
        FrameworkRegistry::setStartRoute( (string)$settings->startRoute );// сохраняем маршрут к стартовой странице приложения
        FrameworkRegistry::setExcepPage ( (string)$settings->excepPage ); // сохраняем путь к странице с описанием возникших исключений
        
        $routing = new Routing( new RouteMapManager() ); //@var Routing агрегирует экземпляр RouteMap, в который сохраняем маршруты
        $strStatusVal = ''; //@var string результат выполнения действия ('RES_ACT_DEF', 'RES_ACT_OK'...).
                
        if ( ! $settings->default ){
           throw new FrmworkExcep\FileFormatException($msgForException, RouteMapLoader::class, __METHOD__, 'Проверьте тег default.');
        }
        // считываем и сохраняем данные о default-странице фраймворка:
        foreach ( $settings->default as $eachDef ) {
            if ( ! $eachDef->action ) {
                throw new FrmworkExcep\FileFormatException($msgForException, RouteMapLoader::class, __METHOD__, 'Проверьте тег default\\action.');
            }
            $routing->addActionNameToRoute( 'default', (string)$eachDef->action );
            if ( ! $eachDef->view ) {
                throw new FrmworkExcep\FileFormatException($msgForException, RouteMapLoader::class, __METHOD__, 'Проверьте тег default\\view.');
            }
            $routing->addViewToRoute( 'default', 0, (string)$eachDef->view );
        }
        if ( ! $settings->route ){
            throw new FrmworkExcep\FileFormatException($msgForException, RouteMapLoader::class, __METHOD__, 'Проверьте теги route.');
        }
        // считываем и сохраняем данные о маршрутах (route):
        foreach ( $settings->route as $eachRoute ) {
            if ( ! $eachRoute['name'] ){
                throw new FrmworkExcep\FileFormatException($msgForException, RouteMapLoader::class, __METHOD__, 'Проверьте атрибут name тегa route.');
            }
            $nameRoute = (string)$eachRoute['name']; //@var string наименование маршрута.
            if ( ! $eachRoute->controller ){
                throw new FrmworkExcep\FileFormatException($msgForException, RouteMapLoader::class, __METHOD__, 'Проверьте тег controller.');
            }
            if ( ! $eachRoute->controller['name'] ){
                throw new FrmworkExcep\FileFormatException($msgForException, RouteMapLoader::class, __METHOD__, 'Проверьте атрибут name тегa controller.');
            }
            $nameController = (string)$eachRoute->controller['name']; //@var string наименование контроллера.
            if ( ! $eachRoute->action ){
                throw new FrmworkExcep\FileFormatException($msgForException, RouteMapLoader::class, __METHOD__, 'Проверьте тег action.');
            }
            if ( ! $eachRoute->action['name'] ){
                 throw new FrmworkExcep\FileFormatException($msgForException, RouteMapLoader::class, __METHOD__, 'Проверьте атрибут name тегa action.');
            }
            $nameAction = (string)$eachRoute->action['name'];//@var string наименование действия.
            foreach ( $eachRoute->action->param as $eachParam ){
                $routing->addParamToAction( $nameRoute, (string)$eachParam, trim($eachParam['limitationrule']), trim($eachParam['defaultvalue']) ); // сохранение в ControllerMap параметров действия.
            }
            $routing->addControllerNameToRoute( $nameRoute, $nameController );
            $routing->addActionNameToRoute( $nameRoute, $nameAction );
            if ($eachRoute->view){
                $strStatusVal = '';
                // считываем и сохраняем данные о представлениях и соотв. им результатах выполнения действий контролера:
                foreach ( $eachRoute->view as $eachView ){
                    $nameView = (string)$eachView; //@var string наименование представления.
                    $strStatusVal = trim($eachView['status']);
                    $routing->addViewToRoute( $nameRoute, ControllerBase::statuses( $strStatusVal ), $nameView ); // сохраняем текущий маршрут, результат выполения действия и соотв. представление
                }
            }
            $strStatusVal = '';
            if ($eachRoute->status){
                $strStatusVal = trim($eachRoute->status['value']);
                foreach ( $eachRoute->status->redirect as $eachRedirect ){
                    // считываем и сохраняем данные о форвард-маршруте, который будет вызван
                    // из текущего маршрута, в случае установки соотв. статуса в действии контролера:
                    $routing->addRedirectToRoute( $nameRoute, ControllerBase::statuses( $strStatusVal ), (string)$eachRedirect );
                }
            } 
        } //foreach ( $settings->cmd as $eachController ) {
        FrameworkRegistry::setRouteMap( $routing->getRouteMapManager() ); // сохраняем таблицу маршрутизации в реестре FrameworkRegistry
    }

}

