<?php 
 
	error_reporting(E_ALL|E_STRICT); 
	ini_set('display_errors', 1); 
	date_default_timezone_set('America/Los_Angeles'); 
 
	// directory setup and class loading 
	set_include_path('.' . PATH_SEPARATOR . '../../../../lib' 
	     . PATH_SEPARATOR . '../../applications/bandspace/models' 
	     . PATH_SEPARATOR . get_include_path()); 
	
	include "Zend/Loader.php"; 
	Zend_Loader::registerAutoload();
	
	// load configuration
	$config = new Zend_Config_Ini('../../applications/bandspace/config.ini', 'general');
	$registry = Zend_Registry::getInstance();
	$registry->set('config', $config);
	
	//setup database
	$db = Zend_Db::factory($config->db);
	Zend_Db_Table::setDefaultAdapter($db);
	
	$registry->set('db', $db);
 
	// setup controller 
	$frontController = Zend_Controller_Front::getInstance(); 
	$frontController->throwExceptions(true); 
	$frontController->setControllerDirectory('../../applications/bandspace/controllers'); 
	Zend_Layout::startMvc(array('layoutPath'=>'../../applications/bandspace/layouts'));
 
	// run! 
	$frontController->dispatch();