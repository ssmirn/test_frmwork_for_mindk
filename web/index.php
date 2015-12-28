<?php 
require_once( "../framework/ClassLoader.php");


use framework\engine\FrontController;

framework\ClassLoader::onAutoLoad();
FrontController::main();
