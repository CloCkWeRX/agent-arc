<?php
class InstallController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $flash = array();

        $config = Zend_Controller_Front::getInstance()->getParam('config');

        //ARC
        //list($flash, $db)        = $this->setupDB($flash, $config);
        list($flash, $store)     = $this->setupARC($flash, $config);
        list($flash, $xml_grddl) = $this->setupXMLGRDDL($flash);



        $this->view->messages  = $flash;
        $this->view->config    = $config;
        $this->view->store     = $store;
        $this->view->xml_grddl = $xml_grddl;
        $this->view->db        = $db;
    }

    /**
     * Checks ARC2 is correctly installed
     */
    protected function setupARC($flash, $config) {
        if (!@include_once 'ARC2.php') {
            $flash[] = "Could not load ARC2 - is it in your include path correctly?";
        }

        if (class_exists('ARC2')) {
            $store = ARC2::getStore(array(
                                    'db_name' => $config->arc->db->name,
                                    'db_user' => $config->arc->db->username,
                                    'db_pwd' =>  $config->arc->db->password,
                                    'db_host' =>  $config->arc->db->host,
                                    'store_name' => $config->arc->db->name
                                    ));

            if (!$store->isSetUp()) {
                $flash[] = "ARC2 store does not seem set up - trying to install schema.";
                $store->setUp();

                $flash[] = "ARC2 setup " . ($store->isSetUp()? "passed" : "failed");
            }
        }

        return array($flash, $store);
    }

    /**
     * Checks XML_GRDDL is correctly installed
     */
    protected function setupXMLGRDDL($flash) {
        $xml_grddl = true;

        if (!@include_once 'XML/GRDDL.php') {
            $flash[] = "Could not load XML_GRDDL - Have you do a PEAR install?";

            $xml_grddl = false;
        }

        return array($flash, $xml_grddl);
    }

    /**
     * Checks DB configuration
     */
    /*
    protected function setupDB($flash, $config) {

        require_once 'Zend/Db.php';

        $connection = false;
        try {
            $db = Zend_Controller_Front::getInstance()->getParam('db');
            $db->getConnection();

            $connection = true;
        } catch (Zend_Db_Adapter_Exception $e) {
            $flash[] = $e->getMessage();
        } catch (Zend_Exception $e) {
            $flash[] = $e->getMessage();
        }

        return array($flash, $connection);
    }
    */
}

