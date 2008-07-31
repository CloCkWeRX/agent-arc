<?php
class AddressBook_SearchController extends Zend_Controller_Action
{
    protected function buildSearchQuery() {
        $email      = $this->_request->getParam('email');
        $nickname   = $this->_request->getParam('nickname');
        $name       = $this->_request->getParam('name');

        $this->view->email = $email;
        $this->view->nickname = $nickname;
        $this->view->name = $name;

        $pattern = array();

        if (!empty($email)) {
            $mailto = 'mailto:' . $email;

            $pattern[] = sprintf('?person foaf:mbox_sha1sum "%s" .', sha1($mailto));
        }

        if (!empty($nickname)) {
            $pattern[] = sprintf('?person foaf:nick "%s" .', $nickname);
        }

        if (!empty($name)) {
            $pattern[] = sprintf('?person foaf:name "%s" .', $name);
        }


        return $pattern;
    }

    public function indexAction()
    {
        $pattern = implode("\n", $this->buildSearchQuery());

        if (!empty($pattern)) {
            $store = Zend_Controller_Front::getInstance()->getParam('store');



            $query = 'SELECT ?person ?name ?email ?url ?nick ?gender ?blog ?jabber

                        WHERE {
                            ?person a foaf:Person .
                            ?person foaf:name ?name .
                            ' . $pattern . '

                            OPTIONAL {
                                ?person foaf:mbox ?email .
                            }
                            OPTIONAL {
                                ?person foaf:homepage ?url .
                            }
                            OPTIONAL {
                                ?person foaf:weblog ?blog .
                            }
                            OPTIONAL {
                                ?person foaf:nick ?nick .
                            }
                            OPTIONAL {
                                ?person foaf:jabberID ?jabber
                            }
                            OPTIONAL {
                                ?person foaf:gender ?gender
                            }
                        }

                        LIMIT 50
                        ';

            $result = Agent_SPARQL::query($store, $query);
            $this->view->result = $result;
        } else {
            $this->messages[] = "Not enough info supplied?";
        }
    }


}

