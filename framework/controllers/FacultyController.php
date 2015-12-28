<?php

namespace framework\controllers;

use src\DomainModel\Faculty;
use src\ViewHelper\FacultyHelper;
use framework\engine\utils\BaseService;
use framework\exceptions as FrmworkExcep;
use framework\exceptions\FrameworkException;

/**
* Контролер для работы с факультетами.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class FacultyController extends ControllerBase 
{
    /**
    * Маршрут listfaculty (<route name="listfaculty">)
    * Вызов представления (facultylist) для отображения факультетов
    */
    public function listFaculty()
    {
        $this->getView( array('tblfacul' =>FacultyHelper::getTable()) );
    }
    
   /**
    * Маршрут addfaculty (<route name="addfaculty">)
    * Создание факультета
    * 
    * @param string $faculty_name - наименование факультета
    * @param string $submit_val - режим выполнения
    */
    public function insert($faculty_name, $submit_val)
    {
        switch ($submit_val) {
            case 'start': 
                // вызов заданного в табл. маршрутизации представления (faculty) для ввода данных:
                $this->getView(array('val_faculty_name' => '',
                                     'msg_validate'     => ''));
                break;
            case 'Сохранить':
                try {
                    $faculty = new Faculty ($faculty_name);
                    if ( $faculty->save() ){
                        //Факультет успешно сохранен.
                        //Задаем статус 'RES_ACT_OK' результата выполнения действия,
                        //для автоматического редиректа на маршрут listfaculty,
                        //который вызовет свое представление с табл. факультетов:
                        $this->status = self::$enumResultAction['RES_ACT_OK'];
                    } else {
                        // имя факультета не прошло проверку правил,
                        // поэтому вызываем заданное в табл. маршрутизации 
                        // представление (faculty) для ввода данных, 
                        // с описанием нарушенного правила:
                        $this->getView(array('val_faculty_name' => '',
                                             'msg_validate'     => 'Внимание: ' . $faculty->getBrokenRulesTotalMessage()));
                    }
                } catch (FrameworkException $frmExcep) {
                    $this->status = self::$enumResultAction['RES_ACT_ERR'];
                    $frmExcep->redirectToExcepPage();
                }     
                break;
            case 'Отмена':
                //задаем статус 'RES_ACT_OK' результата выполнения действия,
                //для автоматического редиректа на маршрут listfaculty,
                //который вызовет свое представление с табл. факультетов:
                $this->status = self::$enumResultAction['RES_ACT_OK'];
                break;
            default:
                throw new FrmworkExcep\RunTimeException('Аргумент $submit_val содержит недопустимое значение - "' . $submit_val . '".',
                                                        FacultyController::class, __METHOD__, 'Клиент изменил значение аргумента на ошибочное.');
        } 
    }
    
    /**
    * Маршрут editfaculty (<route name="editfaculty">)
    * Редактирование факультета
    * 
    * @param int $id - id факультета
    * @param string $faculty_name - наименование факультета
    * @param string $submit_val - режим выполнения
    */
    public function update($id, $faculty_name, $submit_val)
    {
        if ( ! BaseService::isId($id) ){
           $id = 0; 
        }
        switch ($submit_val) {
            case 'edit':
                try {
                    $faculty = Faculty::getSelfById ($id);
                    // вызов заданного в табл. маршрутизации представления (faculty)
                    // c внесенными данными для редактирования:
                    $this->getView(array('val_faculty_name' => $faculty->getName(),
                                         'msg_validate'     => '',
                                         'id'               => $id));
                } catch (FrameworkException $frmExcep) {
                    $this->status = self::$enumResultAction['RES_ACT_ERR'];
                    $frmExcep->redirectToExcepPage();
                }  
                break;
            case 'Сохранить':
                try {
                    $faculty = new Faculty($faculty_name, $id);
                    if ($faculty->save()){
                        //Факультет успешно сохранен.
                        //Задаем статус 'RES_ACT_OK' результата выполнения действия,
                        //для автоматического редиректа на маршрут listfaculty,
                        //который вызовет  свое представление с табл. факультетов:
                        $this->status = self::$enumResultAction['RES_ACT_OK'];
                    } else {
                        // имя факультета не прошло проверку правил,
                        // поэтому вызываем заданное в табл. маршрутизации 
                        // представление (faculty) для ввода данных, 
                        // с описанием нарушенного правила:
                        $this->getView(array('val_faculty_name' => '',
                                             'msg_validate'     => 'Внимание: ' . $faculty->getBrokenRulesTotalMessage(),
                                             'id'               => $id));
                    }
                } catch (FrameworkException $frmExcep) {
                    $this->status = self::$enumResultAction['RES_ACT_ERR'];
                    $frmExcep->redirectToExcepPage();
                }  
                break;
            case 'Отмена':
                 //задаем статус 'RES_ACT_OK' результата выполнения действия,
                 //для автоматического редиректа на маршрут listfaculty,
                 //который вызовет свое представление с табл. факультетов:
                $this->status = self::$enumResultAction['RES_ACT_OK'];
                break;
            default:
                throw new FrmworkExcep\RunTimeException('Аргумент $submit_val содержит недопустимое значение - "' . $submit_val . '".',
                                                     FacultyController::class, __METHOD__, 'Клиент изменил значение аргумента на ошибочное.');
        } 
    }
    
   /**
    * Маршрут deletefaculty (<route name="deletefaculty">)
    * Удаление факультета
    * 
    * @param int $id - id факультета
    */
    public function delete( $id )
    {
        if ( ! BaseService::isId($id) ){
           $id = 0; 
        }
        try {
            $faculty = Faculty::getSelfById ($id);
            if ($faculty->delete()){
                // Факультет успешно удален.
                //Задаем статус 'RES_ACT_OK' результата выполнения действия,
                //для автоматического редиректа на маршрут listfaculty,
                //который вызовет  свое представление с табл. факультетов:
                $this->status = self::$enumResultAction['RES_ACT_OK'];
            } else {
                // факультет не прошел проверку правил,
                // поэтому вызываем заданное в табл. маршрутизации 
                // представление (facultylist) с описанием нарушенного правила:
                $this->getView(array('tblfacul' =>FacultyHelper::getTable(),
                                     'msg_validate' => 'Внимание, удаление факультета невозможно:  ' . $faculty->getBrokenRulesTotalMessage()));
            }
        } catch (FrameworkException $frmExcep) {
            $this->status = self::$enumResultAction['RES_ACT_ERR'];
            $frmExcep->redirectToExcepPage();
        }  
        
    }
    
       
}
