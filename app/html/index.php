<?php
require_once 'Zend/Loader.php';
require_once dirname(__FILE__) . '/../../lib/agent/SPARQL.php';

spl_autoload_register(array('Zend_Loader', 'autoload'));


$config_path = dirname(__FILE__) . '/../config/default.ini';
$config = new Zend_Config_Ini($config_path, 'default');




$store = ARC2::getStore(array(
                        'db_name' => $config->arc->db->name,
                        'db_user' => $config->arc->db->username,
                        'db_pwd' =>  $config->arc->db->password,
                        'db_host' =>  $config->arc->db->host,
                        'store_name' => $config->arc->db->name
                        ));

/** @todo   Work out if I need a normal database */
/*
$db = Zend_Db::factory($config->db->type, array(
    'host'     => $config->db->host,
    'username' => $config->db->username,
    'password' => $config->db->password,
    'dbname'   => $config->db->name
));

Zend_Db_Table::setDefaultAdapter($db);
*/
Zend_Layout::startMvc();

$front = Zend_Controller_Front::getInstance();
$front->setParam('db', $db);
$front->setParam('config', $config);
$front->setParam('store', $store);


$front->addControllerDirectory(dirname(__FILE__) . '/../controllers');
$front->addModuleDirectory(dirname(__FILE__) . '/../modules');

$front->throwExceptions(true);
$front->dispatch();

