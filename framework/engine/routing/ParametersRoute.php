<?php

namespace framework\engine\routing;

/**
* Проверяет, обрабатывает и подготавливает параметры для заданого действия контролера. 
* Параметры из Request сверяются с соответствующими параметрами из таблицы маршрутизации
* RouteMapManager (config.xml). В результате получаем массив параметров для передачи в соответствующее
* действие контролера.
*
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class ParametersRoute
{
    private $paramsRequest       = array (); //@var array массив параметров из Request 
    private $paramsRoutingMap    = array (); //@var array массив параметров из таблицы маршрутизации  
    private $paramsCallingAction = array (); //@var array результат - массив параметров для передачи действию контролера  
    
    protected static $enumResultCheck = array (
        'CHECK_OK'      => 0,
        'CHECK_ERR'     => 1,
        'NO_PARAMETERS' => 2
    );//@var array перечисление с результатами выполнения проверки параметров из Request
    
    private $status = 0; //@var int здесь храним установленное значение из $enumResultCheck
    
   /**
    * Возвращаем установленный результат выполнения проверки параметров из Request
    * 
    * @return int 
    */
    public function getStatus() 
    { 
        return (int)$this->status;
    }
    
    public function __construct( $arrParamsFromRequest, $arrParamsFromRoutingMap ) 
    {
        if ( is_array($arrParamsFromRequest))   { $this->paramsRequest    = $arrParamsFromRequest; }    // передаем параметры из Request
        if ( is_array($arrParamsFromRoutingMap)){ $this->paramsRoutingMap = $arrParamsFromRoutingMap; } // передаем параметры из табл.маршр. 
    }
    
    /**
    * Возвращаем соотв. переданному параметру $strStatusArg
    * целочисленное значение из перечисления $enumResultCheck
    *
    * @param string $strStatusArg - наименование соотв. одному из ключей $enumResultCheck
    * @return int 
    */
    public static function statuses(  $strStatusArg = 'NO_PARAMETERS' ) 
    {
        if ( ! $strStatusArg ) {  $strStatusArg = 'NO_PARAMETERS'; }
        if (!array_key_exists($strStatusArg, self::$enumResultCheck)) { $strStatusArg = 'NO_PARAMETERS'; }
        return (int)self::$enumResultCheck[$strStatusArg];
    }
       
   /**
    * Возвращаем статус результата проверки параметров и
    * массив параметров для действия контролера.
    *
    * @return array возвращает: 1. array (self::statuses('NO_PARAMETERS'), null) - если параметров нет;
    *                           2. array (self::statuses('CHECK_OK'), $this->paramsCallingAction) - проверка не выявила ошибок;
    *                           3. array (self::statuses('CHECK_ERR'), null) - проверка обнаружила ошибки;
    */
    public function getParamsForCallingAction () 
    { 
        if (sizeof( $this->paramsRequest)    == 0 && 
            sizeof( $this->paramsRoutingMap) == 0) {
                $this->status = self::statuses('NO_PARAMETERS');
                return array ($this->status, null);
        }
        $this->checkAndCreateParameters();
        if ($this->status == self::statuses('CHECK_OK')){
            // в результате проверки параметров в методе checkAndCreateParameters() ошибок
            // не обнаружено, статус проверки равен 'CHECK_OK' - 
            // возвращаем self::statuses('CHECK_OK') и $this->paramsCallingAction:
            return array ($this->status, $this->paramsCallingAction);
        } 
        return array ($this->status, null);
    }
    
    /**
    * Выполняем проверку параметров, устанавливаем статус результата проверки
    * 'CHECK_OK' или 'CHECK_ERR', формируем paramsCallingAction[] - результирующий массив 
    * параметров для действия контролера.
    */
    private function checkAndCreateParameters()
    {
        foreach ( $this->paramsRoutingMap as $nameParamFromRouteMap => $eachAttribParam) {
            // обходим параметры из табл.маршрутизации для заданного контролера:
            //проверяем есть ли текущий параметр в массиве параметров из Request:
            if ( array_key_exists($nameParamFromRouteMap, $this->paramsRequest)) {
                // получаем значение из Request для текущего параметра:
                $valParam = $this->paramsRequest[$nameParamFromRouteMap];
                // из табл.маршрутизации для текущего параметра
                // возвращаем ограничение на значение параметра (регулярное выражение):
                $eachRuleParam = trim($eachAttribParam['limitrule']);
                if (!is_string($eachRuleParam) || strlen($eachRuleParam) == 0){
                    // если ограничение не определено, то значение параметра
                    // сохраняется в результирующем массиве параметров для действия:
                    $this->paramsCallingAction[] = $valParam;
                    continue;
                }
                if ( preg_match($eachRuleParam, $valParam) === 1) {
                    // если значение соответствует регулярному выражению ограничения,
                    // то значение параметра сохраняется в результирующем массиве параметров:
                    $this->paramsCallingAction[] = $valParam;
                } else {
                    // в противном случае проверка завершается со статусом 'CHECK_ERR':
                    $this->status = self::statuses('CHECK_ERR');
                    return;
                }
            } else {
                // из табл.маршрутизации для текущего параметра возвращаем значение по умолчанию:
                $eachDefaultValueParam = trim((string)$eachAttribParam['defaultvalue']);
                if (is_string($eachDefaultValueParam) && strlen($eachDefaultValueParam) > 0){
                     // если в качестве значения по умолчанию указано 'null', то в
                     // результирующем массиве параметров  сохраняется пустая строка,
                     // в противном случае - значение  из табл.маршр:
                    if ( strcasecmp($eachDefaultValueParam, 'null') == 0 ) {
                        $this->paramsCallingAction[] = '';
                    } else {
                        $this->paramsCallingAction[] = $eachDefaultValueParam;
                    }
                    continue;
                } else {
                    $this->status = self::statuses('CHECK_ERR');
                    return;
                }
            } // if ( array_key_exists($nameParamFromRouteMap, $this->paramsRequest)) {
        } //foreach ( $this->paramsRoutingMap as $nameParamFromRouteMap => $eachAttribParam) {
        //устанавливаем статус результата проверки параметров:
        $this->status = (sizeof($this->paramsCallingAction) == sizeof($this->paramsRoutingMap))  
                         ? self::statuses('CHECK_OK') 
                         : self::statuses('CHECK_ERR');
        return;
    }
    
}
