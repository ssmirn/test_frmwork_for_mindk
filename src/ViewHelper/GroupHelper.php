<?php

namespace src\ViewHelper;

use src\DomainModel\Faculty;
use framework\engine\utils\BaseService;
use src\DomainModel\Group;
use src\DomainModel\NullObjects\NullGroup;

/**
* Template View для отображения данных о группах.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
final class GroupHelper
{
   /**
    * Возвращает таблицу с факультетами и их группами.
    * 
    * @param int $idSelectedGroup - id выделенной группы
    * @return string таблица факультетов и их групп в формате HTML
    */
    public static function getGroups( $idSelectedGroup )  
    { 
        if ( ! BaseService::isId($idSelectedGroup) ){
           $idSelectedGroup = 0; 
        }
        try {
            $numFacult = 0;
            $result = '';
            foreach ( Faculty::getAllItems() as $eachFaculty){
                $result .= '<tr><td id="s' . ++$numFacult . '"   headers="seq" colspan="2"> <span style="font-weight: bold;">' . 
                            $eachFaculty->getName() . '</span></td></tr>';
                $numGroup = 0;
                foreach ( $eachFaculty->getGroups() as $eachGroup){
                    $tagGroup = ($idSelectedGroup == $eachGroup->getId()) ? '&nbsp;&nbsp; <b>' . $eachGroup->getName() . ' »</b>' 
                                                                          :  $eachGroup->getName() . ' »';
                    $suffix = ( $eachGroup instanceof NullGroup ) 
                              ? $tagGroup . '</td></tr>'
                              : '<a href="home?id_group=' . $eachGroup->getId() . '" >' . $tagGroup . '</a></td></tr>';
                    $result .= '<tr class="sub"><td id="s' . $numFacult . '_' . ++$numGroup . '" headers="seq s' . $numFacult . '">' . $suffix;
                }
            }
        } catch (FrameworkException $frmExcep) {
            $frmExcep->redirectToExcepPage();
        }  
        return $result; 
    }
    
   /**
    * Возвращает таблицу с группами.
    * 
    * @return string таблица с группами в формате HTML
    */
    public static function getGroupsWithActions( )  
    { 
        try {
            $numFacult = 0;
            $result = '';
            foreach ( Faculty::getAllItems() as $eachFaculty){
                $result .= '<tr><td id="s' . ++$numFacult . '"   headers="seq" colspan="2">' . $eachFaculty->getName() . '</td></tr>';
                $numGroup = 0;
                $groups = $eachFaculty->getGroups();
                foreach ( $eachFaculty->getGroups() as $eachGroup){
                    $suffix = ( $eachGroup instanceof NullGroup ) 
                              ? '</tr>'
                              : '<td align="center"><a href="editgroup?id=' . $eachGroup->getId() . '&submit_val=edit">Редактировать</a> &nbsp;'.
                                '<a href="deletegroup?id=' . $eachGroup->getId() . '" onClick="return confirm (\'Вы хотите удалить данную группу?\');">Удалить</a></td></tr>';
                    $result .= '<tr class="sub"><td id="s' . $numFacult . '_' . ++$numGroup . '" headers="seq s' . 
                               $numFacult . '">' . $eachGroup->getName() . '</td>' . $suffix;
                }
            }
        } catch (FrameworkException $frmExcep) {
            $frmExcep->redirectToExcepPage();
        }  
        return $result; 
    }
    
    
   /**
    * Возвращает список с факультетами и их группами.
    * 
    * @param int $idSelectedGroup - id выделенной записи
    * @return string список факультетов и их групп в формате HTML
    */
    public static function getGroupsAndFaculties(  $idSelectedGroup = 0 )  
    { 
        if ( ! BaseService::isId($idSelectedGroup) ){
           $idSelectedGroup = 0; 
        }
        try {
            $result = '<select name="id_group" size="15">';
            foreach ( Faculty::getAllItems() as $eachFaculty){
                $result .= '<optgroup label="' . $eachFaculty->getName() . '">' ;
                foreach ( $eachFaculty->getGroups() as $eachGroup){
                    if ( $eachGroup instanceof NullGroup ){
                        continue;
                    }
                    $selected = ($idSelectedGroup == $eachGroup->getId()) ? ' selected ' : '';
                    $result .= '<option value="' . $eachGroup->getId() . '"' . $selected . '>' . $eachGroup->getName() . '</option>' ;
                }
                $result .= '</optgroup>';
            }
            $result .= '</select>';
        } catch (FrameworkException $frmExcep) {
            $frmExcep->redirectToExcepPage();
        }  
        return $result; 
    }
}

