<?php
class AddressBook_IndexController extends Zend_Controller_Action
{
    public function indexAction()
    {
    }

    protected function buildSearchQuery() {
        $email      = $this->_request->getParam('email');
        $nickname   = $this->_request->getParam('nickname');

        $pattern = array();

        if (!empty($email)) {
            $mailto = 'mailto:' . $email;

            $pattern[] = sprintf('?person foaf:mbox_sha1sum "%s" .', sha1($mailto));
        }

        if (!empty($nickname)) {
            $pattern[] = sprintf('?person foaf:nick "%s" .', $nickname);
        }

        return $pattern;
    }

    public function searchAction()
    {
        $pattern = implode("\n", $this->buildSearchQuery());

        if (!empty($pattern)) {
            $store = Zend_Controller_Front::getInstance()->getParam('store');



            $query = 'PREFIX app: <http://www.radarnetworks.com/shazam#> .
                            PREFIX basic: <http://www.radarnetworks.com/2007/09/12/basic#> .
                            PREFIX bio: <http://purl.org/vocab/bio/0.1/> .
                            PREFIX dataview: <http://www.w3.org/2003/g/data-view#> .
                            PREFIX dc: <http://purl.org/dc/elements/1.1/> .
                            PREFIX foaf: <http://xmlns.com/foaf/0.1/> .
                            PREFIX geo: <http://www.w3.org/2003/01/geo/wgs84_pos#> .
                            PREFIX h: <http://www.w3.org/1999/xhtml> .
                            PREFIX lfm: <http://purl.org/ontology/last-fm/> .
                            PREFIX mail: <http://www.radarnetworks.com/shazam/mail#> .
                            PREFIX mo: <http://purl.org/ontology/mo/> .
                            PREFIX ns0: <http://gmpg.org/xfn/11#> .
                            PREFIX owl: <http://www.w3.org/2002/07/owl#> .
                            PREFIX radar: <http://www.radarnetworks.com/core#> .
                            PREFIX rdf: <http://www.w3.org/1999/02/22-rdf-syntax-ns#> .
                            PREFIX rdfs: <http://www.w3.org/2000/01/rdf-schema#> .
                            PREFIX web: <http://www.radarnetworks.com/web#> .
                            PREFIX xml: <http://www.w3.org/XML/1998/namespace> .
                            PREFIX xsd: <http://www.w3.org/2001/XMLSchema#> .
                            PREFIX ya: <http://blogs.yandex.ru/schema/foaf/> .

                            SELECT ?person ?name ?email ?url ?nick ?gender ?blog ?jabber

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

            $result = $store->query($query);
            $this->view->result = $result['result'];
        } else {
            $this->messages[] = "Not enough info supplied?";
        }
    }


}

