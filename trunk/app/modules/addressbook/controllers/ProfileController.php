<?php
class AddressBook_ProfileController extends Zend_Controller_Action
{
    public function indexAction()
    {
        $store = Zend_Controller_Front::getInstance()->getParam('store');

        $config = Zend_Controller_Front::getInstance()->getParam('config');

        $email = $config->addressbook->email;
        if (empty($email)) {
            $this->messages[] = "You have not configured your email yet - add `addressbook.email = you@youremail.com` to your default.ini";
        }

        if (!empty($email)) {
            $query = 'SELECT ?person ?name ?mbox_sha1sum

                        WHERE {
                            ?person foaf:mbox_sha1sum "%s" .
                            ?person foaf:mbox_sha1sum ?mbox_sha1sum .
                            ?person foaf:name ?name .
                        }';


            $query = sprintf($query, sha1('mailto:' . $email));

            $result =  Agent_SPARQL::query($store, $query);

            $this->view->result = $result;
        }
    }

    public function homepageAction()
    {
        $store = Zend_Controller_Front::getInstance()->getParam('store');

        $url = $this->_request->getParam('url');
        if (empty($url)) {
            $this->messages[] = "You have to supply a full URL for this to work.";
        }

        if (!empty($url)) {
            $query = 'SELECT ?person ?name ?url

                        WHERE {
                            ?person foaf:homepage <%s> .
                            ?person foaf:homepage ?url .
                            ?person foaf:name ?name .
                        }';


            $query = sprintf($query, $url);

            $result = Agent_SPARQL::query($store, $query);

            $this->view->url = $url;
            $this->view->result = $result;
        }
    }

    public function mboxsha1sumAction()
    {
        $store = Zend_Controller_Front::getInstance()->getParam('store');

        $email = $this->_request->getParam('email');
        if (empty($email)) {
            $this->messages[] = "You have not configured your email yet - add `addressbook.email = you@youremail.com` to your default.ini";
        }

        if (!empty($email)) {
            $query = 'SELECT ?person ?name ?mbox_sha1sum

                        WHERE {
                            ?person foaf:mbox_sha1sum "%s" .
                            ?person foaf:mbox_sha1sum ?mbox_sha1sum .
                            ?person foaf:name ?name .
                        }';


            $query = sprintf($query, sha1('mailto:' . $email));

            $result = Agent_SPARQL::query($store, $query);

            $this->view->result = $result;
        }
    }
}

