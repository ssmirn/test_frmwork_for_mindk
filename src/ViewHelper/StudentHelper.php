<?php
namespace src\ViewHelper;

use src\DomainModel\Faculty;
use src\DomainModel\Group;
use src\DomainModel\NullObjects\NullGroup;
use src\DomainModel\Student;
use src\DomainModel\NullObjects\NullStudent;
use framework\engine\utils\BaseService;
use framework\exceptions as FrmworkExcep;

/**
* Template View для отображения данных о студентах.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class StudentHelper
{
    /**
    * Возвращает номера страниц для отображения студентов.
    * Текущая страница выделяется.
    * 
    * @param int $idGroup - id группы
    * @param int $maxRowsOnPage - max записей на странице
    * @param int $numCurrentPage - номер страницы
    * @return string номера страниц в формате HTML
    */
    public static function getCountPagesStudents( $idGroup, $maxRowsOnPage, $numCurrentPage )  
    { 
        $result = '';
        if ( ! BaseService::isId($idGroup) ){
           return $result; 
        }
        if ($maxRowsOnPage < 1) {
            throw new FrmworkExcep\RunTimeException('Недопустимое значение переменной  $maxRowsOnPage - "' . $maxRowsOnPage . '".',
                                                     StudentHelper::class, __METHOD__, 'Проверьте клиентский код вызова метода getCountPagesStudents.');
        }
        if ( ! BaseService::isId($numCurrentPage) ){
           $numCurrentPage = 1; 
        }
        try {
            $group = Group::getSelfById ($idGroup);
        } catch (FrameworkException $frmExcep) {
            $frmExcep->redirectToExcepPage();
        }  
        if ( $group instanceof NullGroup ){
            return $result; 
        }
        $countPages = (int)ceil(sizeof($group->getStudents()) / $maxRowsOnPage);
        $i = 0;
        while ($countPages >= ++$i) {
            $tagNumPage = ($numCurrentPage == $i) ? '<b> - ' . $i . ' -</b>' : $i;
            $result .= '<a href="home?id_group=' . $idGroup . '&number_page_student=' . $i . '" >' . $tagNumPage . '</a>&nbsp;&nbsp;';
        }
        return $result; 
    }
    
    /**
    * Возвращает таблицу со студентами для выделенной группы.
    * 
    * @param int $idGroup - id группы
    * @param int $numberPage - номер страницы
    * @param int $maxRowsOnPage - max записей на странице
    * @return string таблица со студентами для выделенной группы в формате HTML
    */
    public static function getStudentsFromGroup( $idGroup, $numberPage,  $maxRowsOnPage )  
    { 
        $result = '';
        if ( ! BaseService::isId($idGroup) ){
            return $result;  
        }
        if ($maxRowsOnPage < 1) {
            throw new FrmworkExcep\RunTimeException('Недопустимое значение переменной  $maxRowsOnPage - "' . $maxRowsOnPage . '".',
                                                     StudentHelper::class, __METHOD__, 'Проверьте клиентский код вызова метода getCountPagesStudents.');
        }
        if ( ! BaseService::isId( $numberPage ) ){
           $numberPage = 1; 
        }
        $startRowIndex = ($numberPage - 1) * $maxRowsOnPage + 1;
        $endRowIndex   = $startRowIndex    + $maxRowsOnPage - 1;
        try {
            $group  = Group::getSelfById ($idGroup);
         } catch (FrameworkException $frmExcep) {
            $frmExcep->redirectToExcepPage();
        }  
        if ( $group instanceof NullGroup ){
            return $result; 
        }
        $i = 0;
        foreach ( $group->getStudents() as $eachStudent){
            $i++;
            if ( $startRowIndex <= $i && $endRowIndex >= $i ){
                if ( $eachStudent instanceof NullStudent ){
                    $prefix = '<tr><td align="center"></td><td>';
                    $suffix = '</tr>';
                } else {
                    $prefix = '<tr><td align="center">' . $i . '</td><td>';
                    $suffix = '<td align="center"><a href="editstudent?id=' . $eachStudent->getId()  . 
                            '&submit_val=edit">Редактировать</a> &nbsp;<a href="deletestudent?id=' . 
                             $eachStudent->getId() . '" onClick="return confirm (\'Вы хотите удалить студента?\');">Удалить</a> </td></tr>';
                }
                $result .= $prefix . $eachStudent->getName(). '</td><td td align="center">' . 
                           $eachStudent->getSex(). '</td><td td align="center">' . $eachStudent->getAge(). '</td>' . $suffix;
            }
        }
        return $result; 
    }
    
   /**
    * Возвращает переключатели для пола студента.
    * 
    * @param string код пола для выделения
    * @return string переключатели для пола студента в формате HTML
    */
    public static function getRadio( $nameSelected ) 
    {
        if ( strcasecmp($nameSelected, 'female') == 0 ){
            $female = 'checked';
        } else {
            $male   = 'checked';
        }
        $result = '<input type="radio" name="stud_sx" value="female" ' . $female . 
                  ' >жен. &nbsp;<input type="radio" name="stud_sx" value="male" ' . $male . ' >муж.';
        return $result; 
    }
    
    
}
