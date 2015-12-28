<?php
namespace src\ViewHelper;

use src\DomainModel\Faculty;
use src\DomainModel\NullObjects\NullFaculty;
use framework\engine\utils\BaseService;
use framework\exceptions\FrameworkException;

/**
* Template View для отображения данных о факультетах.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class FacultyHelper
{
   /**
    * Возвращает список факультетов.
    * 
    * @param int $idSelectedFaculty - id выделенной записи
    * @return string список факультетов в формате HTML
    */
    public static function getComboBox( $idSelectedFaculty = 0 )  
    { 
        if ( ! BaseService::isId($idSelectedFaculty) ){
           $idSelectedFaculty = 0; 
        }
        $result = '<select name="id_facul">';
        try {
            foreach ( Faculty::getAllItems() as $eachFaculty){
                if ( $eachFaculty instanceof NullFaculty ){
                    continue;
                }
                $selected = ($idSelectedFaculty == $eachFaculty->getId()) ? ' selected ' : '';
                $result .= '<option value="' . $eachFaculty->getId() . '"' . $selected . '>' . $eachFaculty->getName() . '</option>' ;
            }
            $result .= '</select>';
        } catch (FrameworkException $frmExcep) {
            $frmExcep->redirectToExcepPage();
        }  
        return $result; 
    }
    
    
   /**
    * Возвращает таблицу с факультетами.
    * 
    * @return string таблица факультетов в формате HTML
    */
    public static function getTable( )  
    { 
        $i = 0;
        $result = '';
        try {
            foreach ( Faculty::getAllItems() as $eachFaculty){
                $suffix = ( $eachFaculty instanceof NullFaculty ) 
                          ? '</tr>'
                          : '<td align="center"><a href="editfaculty?id=' . $eachFaculty->getId()  . '&submit_val=edit">Редактировать</a> &nbsp;  ' .
                            '<a href="deletefaculty?id=' . $eachFaculty->getId() . '" onClick="return confirm (\'Вы хотите удалить данный факультет?\');">Удалить</a> </td></tr>';
                $result .= '<tr><td align="center">' . ++$i . '</td><td>' . $eachFaculty->getName(). '</td>' . $suffix;
            }
        } catch (FrameworkException $frmExcep) {
            $frmExcep->redirectToExcepPage();
        }  
        return $result; 
    }
    
}
