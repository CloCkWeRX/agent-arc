<?php
class AddressBook_SettingsController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $config = Zend_Controller_Front::getInstance()->getParam('config');

        $this->view->settings = $config->addressbook;
    }
}

