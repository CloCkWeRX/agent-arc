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


                                SELECT ?person ?name ?mbox_sha1sum

                                WHERE {
                                    ?person foaf:mbox_sha1sum "%s" .
                                    ?person foaf:mbox_sha1sum ?mbox_sha1sum .
                                    ?person foaf:name ?name .
                                }';


            $query = sprintf($query, sha1('mailto:' . $email));

            $result = $store->query($query);

            $this->view->result = $result['result'];
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


                                SELECT ?person ?name ?url

                                WHERE {
                                    ?person foaf:homepage <%s> .
                                    ?person foaf:homepage ?url .
                                    ?person foaf:name ?name .
                                }';


            $query = sprintf($query, $url);

            $result = $store->query($query);

            $this->view->url = $url;
            $this->view->result = $result['result'];
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


                                SELECT ?person ?name ?mbox_sha1sum

                                WHERE {
                                    ?person foaf:mbox_sha1sum "%s" .
                                    ?person foaf:mbox_sha1sum ?mbox_sha1sum .
                                    ?person foaf:name ?name .
                                }';


            $query = sprintf($query, sha1('mailto:' . $email));

            $result = $store->query($query);

            $this->view->result = $result['result'];
        }
    }
}

