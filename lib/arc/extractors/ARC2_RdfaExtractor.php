<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 RDFa Extractor
author:   Benjamin Nowack
version:  2008-03-14 (Fix: Avoid triple generation from non-curie rel values)
*/

ARC2::inc('RDFExtractor');

class ARC2_RdfaExtractor extends ARC2_RDFExtractor {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_RdfaExtractor($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
  }

  /*  */
  
  function extractRDF() {
    $root_node = $this->getRootNode();
    $base = $this->v('xml:base', $this->getDocBase(), $root_node['a']);
    $context = array(
      'base' => $base,
      'p_s' => $base,
      'p_o' => '',
      'ns' => array(),
      'inco_ts' => array(),
      'lang' => '',
    );
    $this->processNode($root_node, $context, 0);
  }
  
  /*  */
  
  function getRootNode() {
    foreach ($this->nodes as $id => $node) {
      if ($node['tag'] == 'html') {
        return $node;
      }
    }
    return 0;
  }
  
  /*  */

  function processNode($n, $ct, $level) {
    $ts_added = 0;
    /* step 1 */
    $lct = array();
    $lct['recurse'] = 1;
    $lct['skip'] = 0;
    $lct['new_s'] = '';
    $lct['cur_o_res'] = '';
    $lct['inco_ts'] = array();
    //$lct['base'] = $ct['base'];
    $lct['base'] = $this->v('xml:base', $ct['base'], $n['a']);
    /* step 2 */
    $lct['ns'] = array_merge($ct['ns'], $this->v('xmlns', array(), $n['a']));
    /* step 3 */
    $lct['lang'] = $this->v('xml:lang', $ct['lang'], $n['a']);
    /* step 4 */
    $rel_uris = $this->getAttributeURIs($n, $ct, $lct, 'rel');
    $rev_uris = $this->getAttributeURIs($n, $ct, $lct, 'rev');
    if (!$rel_uris && !$rev_uris) {
      foreach (array('about', 'src', 'resource', 'href') as $attr) {
        if (isset($n['a'][$attr]) && (list($uri, $sub_v) = $this->xURI($n['a'][$attr], $lct['base'], $lct['ns'])) && $uri) {
          $lct['new_s'] = $uri;
          break;
        }
      }
      if (!$lct['new_s']) {
        if (preg_match('/(head|body)/i', $n['tag'])) {
          $lct['new_s'] = $lct['base'];
        }
        elseif ($this->getAttributeURIs($n, $ct, $lct, 'instanceof')) {
          $lct['new_s'] = $this->createBnodeID();
        }
        elseif ($ct['p_o']) {
          $lct['new_s'] = $ct['p_o'];
          $lct['skip'] = 1;
        }
      }
    }
    /* step 5 */
    else {
      foreach (array('about', 'src') as $attr) {
        if (isset($n['a'][$attr]) && (list($uri, $sub_v) = $this->xURI($n['a'][$attr], $lct['base'], $lct['ns'])) && $uri) {
          $lct['new_s'] = $uri;
          break;
        }
      }
      if (!$lct['new_s']) {
        if (preg_match('/(head|body)/i', $n['tag'])) {
          $lct['new_s'] = $lct['base'];
        }
        elseif ($this->getAttributeURIs($n, $ct, $lct, 'instanceof')) {
          $lct['new_s'] = $this->createBnodeID();
        }
        elseif ($ct['p_o']) {
          $lct['new_s'] = $ct['p_o'];
        }
      }
      foreach (array('resource', 'href') as $attr) {
        if (isset($n['a'][$attr]) && (list($uri, $sub_v) = $this->xURI($n['a'][$attr], $lct['base'], $lct['ns'])) && $uri) {
          $lct['cur_o_res'] = $uri;
          break;
        }
      }
    }
    /* step 6 */
    if ($lct['new_s']) {
      if ($uris = $this->getAttributeURIs($n, $ct, $lct, 'instanceof')) {
        foreach ($uris as $uri) {
          $this->addT(array(
            's' => $lct['new_s'],
            's_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'iri',
            'p' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 
            'o' => $uri,
            'o_type' => 'iri',
            'o_lang' => '',
            'o_dt' => '',
          ));
          $ts_added = 1;
        }
      }
      /* step 7 */
      if ($lct['cur_o_res']) {
        if ($rel_uris) {
          foreach ($rel_uris as $uri) {
            $this->addT(array(
              's' => $lct['new_s'],
              's_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'iri',
              'p' => $uri, 
              'o' => $lct['cur_o_res'],
              'o_type' => preg_match('/^\_\:/', $lct['cur_o_res']) ? 'bnode' : 'iri',
              'o_lang' => '',
              'o_dt' => '',
            ));
            $ts_added = 1;
          }
        }
        if ($rev_uris) {
          foreach ($rev_uris as $uri) {
            $this->addT(array(
              's' => $lct['cur_o_res'],
              's_type' => preg_match('/^\_\:/', $lct['cur_o_res']) ? 'bnode' : 'iri',
              'p' => $uri, 
              'o' => $lct['new_s'],
              'o_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'iri',
              'o_lang' => '',
              'o_dt' => '',
            ));
            $ts_added = 1;
          }
        }
      }
    }
    /* step 8 */
    if (!$lct['cur_o_res']) {
      if ($rel_uris || $rev_uris) {
        $lct['cur_o_res'] = $this->createBnodeID();
        foreach ($rel_uris as $uri) {
          $lct['inco_ts'][] = array('p' => $uri, 'dir' => 'fwd');
        }
        foreach ($rev_uris as $uri) {
          $lct['inco_ts'][] = array('p' => $uri, 'dir' => 'rev');
        }
      }
    }
    /* step 10 */
    if ($new_s = $lct['new_s']) {// ? 
      if ($uris = $this->getAttributeURIs($n, $ct, $lct, 'property')) {
        foreach ($uris as $uri) {
          $lct['cur_o_lit'] = $this->getCurrentObjectLiteral($n, $lct, $ct);
          $this->addT(array(
            's' => $lct['new_s'],
            's_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'iri',
            'p' => $uri, 
            'o' => $lct['cur_o_lit']['val'],
            'o_type' => 'literal',
            'o_lang' => $lct['cur_o_lit']['lang'],
            'o_dt' => $lct['cur_o_lit']['dt'],
          ));
          $ts_added = 1;
          if ($lct['cur_o_lit']['dt'] == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral') {
            $lct['recurse'] = 0;
          }
        }
      }
    }
    /* step 11 (10) */
    if ($lct['recurse']) {
      if ($lct['skip']) {
        $new_ct = array_merge($ct, array('base' => $lct['base'], 'lang' => $lct['lang'], 'ns' => $lct['ns']));
      }
      else {
        $new_ct = array(
          'base' => $lct['base'],
          'p_s' => $lct['new_s'] ? $lct['new_s'] : $ct['p_s'],
          'p_o' => $lct['cur_o_res'] ? $lct['cur_o_res'] : ($lct['new_s'] ? $lct['new_s'] : $ct['p_s']),
          'ns' => $lct['ns'],
          'inco_ts' => $lct['inco_ts'],
          'lang' => $lct['lang']
        );
      }
      $sub_nodes = $this->getSubNodes($n);
      $complete_triples = 0;
      foreach ($sub_nodes as $sub_node) {
        if ($this->processNode($sub_node, $new_ct, $level+1)) {
          $complete_triples = 1;
        }
      }
    }
    /* step 12 (11) */
    if ($ts_added || $complete_triples || ($lct['new_s'] && !preg_match('/^\_\:/', $lct['new_s']))) {
    //if (!$lct['skip'] && ($complete_triples || ($lct['new_s'] && !preg_match('/^\_\:/', $lct['new_s'])))) {
      foreach ($ct['inco_ts'] as $inco_t) {
        if ($inco_t['dir'] == 'fwd') {
          $this->addT(array(
            's' => $ct['p_s'],
            's_type' => preg_match('/^\_\:/', $ct['p_s']) ? 'bnode' : 'iri',
            'p' => $inco_t['p'], 
            'o' => $lct['new_s'],
            'o_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'iri',
            'o_lang' => '',
            'o_dt' => '',
          ));
        }
        elseif ($inco_t['dir'] == 'rev') {
          $this->addT(array(
            's' => $lct['new_s'],
            's_type' => preg_match('/^\_\:/', $lct['new_s']) ? 'bnode' : 'iri',
            'p' => $inco_t['p'], 
            'o' => $ct['p_s'],
            'o_type' => preg_match('/^\_\:/', $ct['p_s']) ? 'bnode' : 'iri',
            'o_lang' => '',
            'o_dt' => '',
          ));
        }
      }
    }
    /* step 13 (12) (result flag) */
    if ($ts_added) return 1;
    if ($lct['new_s'] && !preg_match('/^\_\:/', $lct['new_s'])) return 1;
    if ($complete_triples) return 1;
    return 0;
  }
  
  /*  */

  function getAttributeURIs($n, $ct, $lct, $attr) {
    $vals = ($val = $this->v($attr, '', $n['a'])) ? explode(' ', $val) : array();
    $r = array();
    foreach ($vals as $val) {
      if(!trim($val)) continue;
      if ((list($uri, $sub_v) = $this->xURI(trim($val), $lct['base'], $ct['ns'], $attr)) && $uri) {
        $r[] = $uri;
      }
    }
    return $r;
  }
  
  /*  */

  function getCurrentObjectLiteral($n, $lct, $ct) {
    $xml_val = $this->getContent($n);
    $plain_val = $this->getPlainContent($n);
    $dt = $this->v('datatype', '', $n['a']);
    list($dt_uri, $sub_v) = $this->xURI($dt, $lct['base'], $lct['ns']);
    $dt = $dt ? $dt_uri : $dt;
    $r = array('val' => '', 'lang' => $lct['lang'], 'dt' => $dt);
    if (isset($n['a']['content'])) {
      $r['val'] = $n['a']['content'];
    }
    elseif ($xml_val == $plain_val) {
      $r['val'] = $plain_val;
    }
    elseif (!preg_match('/[\<\>]/', $xml_val)) {
      $r['val'] = $xml_val;
    }
    elseif (isset($n['a']['datatype']) && ($dt != 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral')) {
      $r['val'] = $plain_val;
    }
    elseif (!isset($n['a']['datatype']) || ($dt == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral')) {
      $r['val'] = $xml_val;
      $r['dt'] = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral';
    }
    return $r;
  }
  
  /*  */
  
  function xURI($v, $base, $ns, $attr_type = '') {
    if ((list($sub_r, $sub_v) = $this->xBlankCURIE($v, $base, $ns)) && $sub_r) {
      return array($sub_r, $sub_v);
    }
    if ((list($sub_r, $sub_v) = $this->xSafeCURIE($v, $base, $ns)) && $sub_r) {
      return array($sub_r, $sub_v);
    }
    if ((list($sub_r, $sub_v) = $this->xCURIE($v, $base, $ns)) && $sub_r) {
      return array($sub_r, $sub_v);
    }
    if (preg_match('/^(rel|rev)$/', $attr_type) && preg_match('/^\s*(alternate|appendix|bookmark|cite|chapter|contents|copyright|glossary|help|icon|index|last|license|meta|next|p3pv1|prev|role|section|stylesheet|subsection|start|up)(\s|$)/s', $v, $m)) {
      return array('http://www.w3.org/1999/xhtml/vocab#' . $m[1], preg_replace('/^\s*' . $m[1]. '/s', '', $v));
    }
    if (preg_match('/^(rel|rev)$/', $attr_type) && preg_match('/^[a-z0-9\.]+$/i', $v)) {
      return array(0, $v);
    }
    return array($this->calcURI($v, $base), '');
  }
  
  function xBlankCURIE($v, $base, $ns) {
    if ($sub_r = $this->x('\[?(\_\:[a-z0-9\_\-])\]?', $v)) {
      return array($sub_r[1], '');
    }
    return array(0, $v);
  }
  
  function xSafeCURIE($v, $base, $ns) {
    if ($sub_r = $this->x('\[([^\:]*)\:(.*)\]', $v)) {
      if (!$sub_r[1]) return array('http://www.w3.org/1999/xhtml/vocab#' . $sub_r[2], '');
      if (isset($ns[$sub_r[1]])) {
        return array($ns[$sub_r[1]] . $sub_r[2], '');
      }
    }
    return array(0, $v);
  }
  
  function xCURIE($v, $base, $ns) {
    if ($sub_r = $this->x('([a-z0-9\-\_]*)\:([a-z0-9\-\_]+)', $v)) {
      if (!$sub_r[1]) return array('http://www.w3.org/1999/xhtml/vocab#' . $sub_r[2], '');
      if (isset($ns[$sub_r[1]])) {
        return array($ns[$sub_r[1]] . $sub_r[2], '');
      }
    }
    return array(0, $v);
  }
  
  /*  */

}
