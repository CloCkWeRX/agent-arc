<?php
class IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
        //Find all available modules

        $modules = array();
        $modules[] = 'AddressBook';
        $modules[] = 'FoafInference';

        // Approach 1: fail
        /*
        $modules = array();
        foreach (get_declared_classes() as $class_name) {
            if ($class_name instanceOf AgentModuleInterface) {
                $modules[] = $class_name;
            }
        }
        */

        //Make glorious links
        $this->view->modules = $modules;
    }
}

