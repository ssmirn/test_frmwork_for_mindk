<?php

namespace framework\controllers;

use src\DomainModel\Group;
use framework\engine\utils\BaseService;
use src\ViewHelper\FacultyHelper;
use src\ViewHelper\GroupHelper;
use framework\exceptions as FrmworkExcep;
use framework\exceptions\FrameworkException;

/**
* Контролер для работы с группами.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class GroupController extends ControllerBase 
{
   /**
    * Маршрут listgroup (<route name="listgroup">)
    * Вызов представления (grouplist) для отображения групп
    */
    public function listGroup()
    {
        $this->getView( array('tblgroups' =>GroupHelper::getGroupsWithActions()) );
    }
    
    /**
    * Маршрут addgroup (<route name="addgroup">)
    * Создание группы
    * 
    * @param int $id_facul - id факультета
    * @param string $group_name - наименование группы
    * @param string $submit_val - режим выполнения
    */
    public function insert($id_facul, $group_name, $submit_val)
    {
        if ( ! BaseService::isId($id_facul) ){
           $id_facul = 0; 
        }
        switch ($submit_val) {
            case 'start':
                // вызов заданного в табл. маршрутизации представления (group) для ввода данных:
                $this->getView(array('cmb_faq'        => FacultyHelper::getComboBox(),
                                     'val_group_name' => '',
                                     'msg_validate'   => ''));
                break;
            case 'Сохранить':
                try {
                    $group = new Group ($group_name, $id_facul);
                    if ($group->save()){
                        //Группа успешно сохранена.
                        //Задаем статус 'RES_ACT_OK' результата выполнения действия,
                        //для автоматического редиректа на маршрут listgroup,
                        //который вызовет свое представление с табл. групп:
                        $this->status = self::$enumResultAction['RES_ACT_OK'];
                    } else {
                        // имя группы не прошло проверку правил,
                        // поэтому вызываем заданное в табл. маршрутизации 
                        // представление (group) для ввода данных, 
                        // с описанием нарушенного правила:
                        $this->getView(array('cmb_faq'        => FacultyHelper::getComboBox($id_facul),
                                             'val_group_name' => '',
                                             'msg_validate'   => 'Внимание: ' . $group->getBrokenRulesTotalMessage()));
                    }
                } catch (FrameworkException $frmExcep) {
                    $this->status = self::$enumResultAction['RES_ACT_ERR'];
                    $frmExcep->redirectToExcepPage();
                }  
                break;
            case 'Отмена':
                //задаем статус 'RES_ACT_OK' результата выполнения действия,
                //для автоматического редиректа на маршрут listgroup,
                //который вызовет представление с табл. групп:
                $this->status = self::$enumResultAction['RES_ACT_OK'];
                break;
            default:
                throw new FrmworkExcep\RunTimeException('Аргумент $submit_val содержит недопустимое значение - "' . $submit_val . '".',
                                                     GroupController::class, __METHOD__, 'Клиент изменил значение аргумента на ошибочное.');
        } 
    }
    
    /**
    * Маршрут editgroup (<route name="editgroup">)
    * Редактирование групп
    * 
    * @param int $id - id группы
    * @param int $id_facul - id факультета
    * @param string $group_name - наименование группы
    * @param string $submit_val - режим выполнения
    */
    public function update($id, $id_facul, $group_name, $submit_val)
    {
        if ( ! BaseService::isId($id) ){
           $id = 0; 
        }
        if ( ! BaseService::isId($id_facul) ){
           $id_facul = 0; 
        }
        switch ($submit_val) {
            case 'edit':
                try {
                    $group = Group::getSelfById ($id);
                    $idFacultet = $group->getFaculty()->getId();
                    // вызов заданного в табл. маршрутизации представления (group)
                    // c внесенными данными для редактирования:
                    $this->getView(array('cmb_faq'        => FacultyHelper::getComboBox($idFacultet),
                                         'val_group_name' => $group->getName(),
                                         'msg_validate'   => '',
                                         'id'             => $id));
                } catch (FrameworkException $frmExcep) {
                    $this->status = self::$enumResultAction['RES_ACT_ERR'];
                    $frmExcep->redirectToExcepPage();
                }  
                break;
            case 'Сохранить':
                try {
                    $group = new Group ($group_name, $id_facul, $id);
                    if ($group->save()){
                        //Группа успешно сохранена.
                        //Задаем статус 'RES_ACT_OK' результата выполнения действия,
                        //для автоматического редиректа на маршрут listgroup,
                        //который вызовет свое представление с табл. групп:
                        $this->status = self::$enumResultAction['RES_ACT_OK'];
                    } else {
                        // имя группы не прошло проверку правил,
                        // поэтому вызываем заданное в табл. маршрутизации 
                        // представление (group) для ввода данных, 
                        // с описанием нарушенного правила:
                        $this->getView(array('cmb_faq'        => FacultyHelper::getComboBox($id_facul),
                                             'val_group_name' => '',
                                             'msg_validate'   => 'Внимание: ' . $group->getBrokenRulesTotalMessage(),
                                             'id'             => $id));
                    }
                } catch (FrameworkException $frmExcep) {
                    $this->status = self::$enumResultAction['RES_ACT_ERR'];
                    $frmExcep->redirectToExcepPage();
                }  
                break;
            case 'Отмена':
                //задаем статус 'RES_ACT_OK' результата выполнения действия,
                //для автоматического редиректа на маршрут listgroup,
                //который вызовет представление с табл. групп:
                $this->status = self::$enumResultAction['RES_ACT_OK'];
                break;
            default:
                throw new FrmworkExcep\RunTimeException('Аргумент $submit_val содержит недопустимое значение - "' . $submit_val . '".',
                                                     GroupController::class, __METHOD__, 'Клиент изменил значение аргумента на ошибочное.');
        } 
    }
    
   /**
    * Маршрут deletegroup (<route name="deletegroup">)
    * Удаление факультета
    * 
    * @param int $id - id группы
    */
    public function delete( $id )
    {
        if ( ! BaseService::isId($id) ){
           $id = 0; 
        }
        try {
            $group = Group::getSelfById ($id);
            if ($group->delete()){
                // Группа успешно удалена.
                //Задаем статус 'RES_ACT_OK' результата выполнения действия,
                //для автоматического редиректа на маршрут listgroup,
                //который вызовет представление с табл. групп:
                $this->status = self::$enumResultAction['RES_ACT_OK'];
            } else {
                // группа не прошла проверку правил,
                // поэтому вызываем заданное в табл. маршрутизации 
                // представление (grouplist) с описанием нарушенного правила:
                $this->getView(array('tblgroups' =>GroupHelper::getGroupsWithActions(),
                                     'msg_validate' => 'Внимание, удаление группы невозможно:  ' . $group->getBrokenRulesTotalMessage()));
            }
        } catch (FrameworkException $frmExcep) {
            $this->status = self::$enumResultAction['RES_ACT_ERR'];
            $frmExcep->redirectToExcepPage();
        }  
        
    }
   
}
