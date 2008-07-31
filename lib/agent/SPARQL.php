<?php
/**
 * A SPARQL helper
 */
class Agent_SPARQL {

    public static function getDefaultPrefixes() {
        return 'PREFIX app: <http://www.radarnetworks.com/shazam#> .
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
        PREFIX ya: <http://blogs.yandex.ru/schema/foaf/> .';
    }

    public static function query($store, $query_fragment, $substitutions = array(), $prefixes = null) {
        $log = Log::singleton('firebug');

        if (empty($prefixes)) {
            $prefixes = self::getDefaultPrefixes();
        }


        $query = $prefixes . "\n" . $query_fragment;
        $log->log($query);
        $result = $store->query($query);

        return $result['result'];
    }
}