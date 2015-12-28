<?php

namespace framework\controllers;

use src\DomainModel\Group;
use src\ViewHelper\GroupHelper;
use src\DomainModel\NullObjects\NullGroup;
use src\DomainModel\Student;
use src\ViewHelper\StudentHelper;
use framework\engine\utils\BaseService;
use framework\exceptions as FrmworkExcep;
use framework\exceptions\FrameworkException;

/**
* Контролер для работы со студентами.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
class StudentController extends ControllerBase 
{
    const MAX_ROWS_ON_PAGE = 10; //@const max кол-во студентов на странице
    
    /**
    * Маршрут home ( <route name="home">)
    * 
    * Вызов представления (home) для отображения студентов
    * @param int $id_group - id группы
    * @param int $number_page_student - номер страницы со студентами
    */
    public function index($id_group, $number_page_student)
    {
        if ( ! BaseService::isId($id_group) ){
           $id_group = 0; 
        }
        try {
            $group = Group::getSelfById ($id_group);
            $nameGroup = ( $group instanceof NullGroup )
                         ? ''
                         : 'группы ' . $group->getName() . ' (кол-во студентов - ' . $group->getCountStudents() . ').';
            $this->getView( array('tblstudents'         => StudentHelper::getStudentsFromGroup($id_group, $number_page_student, self::MAX_ROWS_ON_PAGE), 
                                  'name_group'          => $nameGroup,
                                  'tblgroups'           => GroupHelper::getGroups($id_group),
                                  'number_page_student' => StudentHelper::getCountPagesStudents($id_group, self::MAX_ROWS_ON_PAGE, $number_page_student),
                                  'id_group'            => $id_group));
        } catch (FrameworkException $frmExcep) {
            $this->status = self::$enumResultAction['RES_ACT_ERR'];
            $frmExcep->redirectToExcepPage();
        }  
    }
    
   /**
    * Маршрут addstudent ( <route name="addstudent">)
    * 
    * Создание студента
    * 
    * @param int $id_group - id группы
    * @param string $s_name - фамилия
    * @param string $f_name - имя
    * @param string $stud_sx -  пол
    * @param int $age - возраст
    * @param string $submit_val - режим выполнения
    */
    public function insert($id_group, $s_name, $f_name, $stud_sx, $age, $submit_val)
    {
        if ( ! BaseService::isId($id_group) ){
           $id_group = 0; 
        }
        switch ($submit_val) {
            case 'start':
                // вызов заданного в табл. маршрутизации представления (student) для ввода данных:
                $this->getView(array('val_group_name' => GroupHelper::getGroupsAndFaculties($id_group),
                                     's_name'         => '',
                                     'f_name'         => '',
                                     'stud_sx'        => StudentHelper::getRadio($stud_sx),
                                     'age'            => $age,
                                     'msg_validate'   => ''));
                break;
            case 'Сохранить':
                $sex =($stud_sx=='female') ? 0 : 1;
                try {
                    $student = new Student ($s_name, $f_name, $sex, $age, $id_group );
                    if ($student->save()){
                        //Студент успешно сохранен.
                        //Задаем статус 'RES_ACT_OK' результата выполнения действия,
                        //для автоматического редиректа на маршрут home,
                        //который вызовет свое представление с табл. студентов:
                        $this->status = self::$enumResultAction['RES_ACT_OK'];
                        //вносим параметры для маршрута home:
                        $this->addParametersForAutoRedirectRoute(array('id_group' => $id_group));
                    } else {
                        // студент не прошел проверку правил,
                        // поэтому вызываем заданное в табл. маршрутизации 
                        // представление (student) для ввода данных, 
                        // с описанием нарушенного правила:
                        $this->getView(array('val_group_name' => GroupHelper::getGroupsAndFaculties($id_group),
                                             's_name'         => $s_name,
                                             'f_name'         => $f_name,
                                             'stud_sx'        => StudentHelper::getRadio($stud_sx),
                                             'age'            => $age,
                                             'msg_validate'   => 'Внимание: ' . $student->getBrokenRulesTotalMessage()));
                    }
                } catch (FrameworkException $frmExcep) {
                    $this->status = self::$enumResultAction['RES_ACT_ERR'];
                    $frmExcep->redirectToExcepPage();
                }  
                break;
            case 'Отмена':
                //Задаем статус 'RES_ACT_OK' результата выполнения действия,
                //для автоматического редиректа на маршрут home,
                //который вызовет представление с табл. студентов:
                $this->status = self::$enumResultAction['RES_ACT_OK'];
                break;
            default:
                throw new FrmworkExcep\RunTimeException('Аргумент $submit_val содержит недопустимое значение - "' . $submit_val . '".',
                                                     GroupController::class, __METHOD__, 'Клиент изменил значение аргумента на ошибочное.');
        } 
    }
    
   /**
    * Маршрут editstudent ( <route name="editstudent">)
    * 
    * Редактирование студента
    * 
    * @param int $id - id студента
    * @param int $id_group - id группы
    * @param string $s_name - фамилия
    * @param string $f_name - имя
    * @param string $stud_sx -  пол
    * @param int $age - возраст
    * @param string $submit_val - режим выполнения
    */
    public function update($id, $id_group, $s_name, $f_name, $stud_sx, $age, $submit_val)
    {
        if ( ! BaseService::isId($id) ){
           $id = 0; 
        }
        if ( ! BaseService::isId($id_group) ){
           $id_group = 0; 
        }
        switch ($submit_val) {
            case 'edit':
                try {
                    $student = Student::getSelfById ($id);
                    $idGroup = $student->getGroup()->getId();
                    // вызов заданного в табл. маршрутизации представления (student)
                    // c внесенными данными для редактирования:
                    $this->getView(array('val_group_name' => GroupHelper::getGroupsAndFaculties($idGroup),
                                         's_name'         => $student->getSurname(),
                                         'f_name'         => $student->getFirstname(),
                                         'stud_sx'        => StudentHelper::getRadio(($student->getSex() == 'жен.') ? 'female' : 'male'),
                                         'age'            => $student->getAge(),
                                         'id'             => $id,
                                         'msg_validate'   => ''));
                } catch (FrameworkException $frmExcep) {
                    $this->status = self::$enumResultAction['RES_ACT_ERR'];
                    $frmExcep->redirectToExcepPage();
                }  
                break;
            case 'Сохранить':
                $sex =($stud_sx=='female') ? 0 : 1;
                try {
                    $student = new Student ($s_name, $f_name, $sex, $age, $id_group, $id);
                    if ($student->save()){
                        //Студент успешно сохранен.
                        //Задаем статус 'RES_ACT_OK' результата выполнения действия,
                        //для автоматического редиректа на маршрут home,
                        //который вызовет свое представление с табл. студентов:
                        $this->status = self::$enumResultAction['RES_ACT_OK'];
                        //вносим параметры для маршрута home:
                        $this->addParametersForAutoRedirectRoute(array('id_group' => $id_group));
                    } else {
                        $this->getView(array('val_group_name' => GroupHelper::getGroupsAndFaculties($id_group),
                                             's_name'         => $s_name,
                                             'f_name'         => $f_name,
                                             'stud_sx'        => StudentHelper::getRadio($stud_sx),
                                             'age'            => $age,
                                             'id'             => $id,
                                             'msg_validate'   => 'Внимание: ' . $student->getBrokenRulesTotalMessage()));
                    }
                } catch (FrameworkException $frmExcep) {
                    $this->status = self::$enumResultAction['RES_ACT_ERR'];
                    $frmExcep->redirectToExcepPage();
                }  
                break;
            case 'Отмена':
                //Задаем статус 'RES_ACT_OK' результата выполнения действия,
                //для автоматического редиректа на маршрут home,
                //который вызовет свое  представление с табл. студентов:
                $this->status = self::$enumResultAction['RES_ACT_OK'];
                break;
            default:
                throw new FrmworkExcep\RunTimeException('Аргумент $submit_val содержит недопустимое значение - "' . $submit_val . '".',
                                                     GroupController::class, __METHOD__, 'Клиент изменил значение аргумента на ошибочное.');
        } 
    }
    
   /**
    * Маршрут deletestudent ( <route name="deletestudent">)
    * 
    * Удаление студента
    * 
    * @param int $id - id студента
    */
    public function delete( $id )
    {
        if ( ! BaseService::isId($id) ){
           $id = 0; 
        }
        try {
            $student = Student::getSelfById ($id);
            $idGroup = $student->getGroup()->getId();
            if ($student->delete()){
               // Студент успешно удален.
               //Автоматический редирект на маршрут home:
               $this->status = self::$enumResultAction['RES_ACT_OK'];
               //вносим параметры для маршрута home:
               $this->addParametersForAutoRedirectRoute(array('id_group' => $idGroup));
            } else {
                // студент не прошел проверку правил,
                // поэтому вызываем заданное в табл. маршрутизации 
                // представление (home) с описанием нарушенного правила:
                $this->status = self::$enumResultAction['RES_ACT_ERR'];
                $number_page_student = 1;
                $this->getView( array('tblstudents'     => StudentHelper::getStudentsFromGroup($idGroup, $number_page_student, self::MAX_ROWS_ON_PAGE), 
                                  'name_group'          => $student->getGroup()->getName(),
                                  'tblgroups'           => GroupHelper::getGroups($idGroup),
                                  'number_page_student' => StudentHelper::getCountPagesStudents($idGroup, self::MAX_ROWS_ON_PAGE, $number_page_student),
                                  'id_group'            => $idGroup,
                                  'msg_validate'        => 'Внимание, удаление студента невозможно:  ' . $student->getBrokenRulesTotalMessage()));
            }
        } catch (FrameworkException $frmExcep) {
            $this->status = self::$enumResultAction['RES_ACT_ERR'];
            $frmExcep->redirectToExcepPage();
        }  
    }
}
