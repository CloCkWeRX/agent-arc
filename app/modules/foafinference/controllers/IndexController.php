<?php
class FoafInference_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $config = Zend_Controller_Front::getInstance()->getParam('config');
        $smush = $config->foafinference->smush;

        $store = Zend_Controller_Front::getInstance()->getParam('store');


        $ifps = array('http://xmlns.com/foaf/0.1/homepage');
        if ($smush) {

            foreach ($ifps as $ifp) {
                $store->consolidateIFP($ifp);
            }
        }

        $this->view->ifps = $ifps;
        $this->view->smush = $smush;
    }

}

