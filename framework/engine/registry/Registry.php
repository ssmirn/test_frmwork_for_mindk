<?php

namespace framework\engine\registry;

/**
* Класс для реализации реестров.
* 
* @author Смирнов С.Л.
* @version 1.0.0.1
* @copyright none
*/
abstract class Registry 
{
    abstract public static function getInstance();
    abstract protected function get( $key );
    abstract protected function set( $key, $val );
} 
