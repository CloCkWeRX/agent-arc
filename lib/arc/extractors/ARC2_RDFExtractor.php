<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 RDF Extractor
author:   Benjamin Nowack
version:  2008-03-03 (Fix: cdata was sometimes truncated in getPlainContent method)
*/

ARC2::inc('Class');

class ARC2_RDFExtractor extends ARC2_Class {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_RDFExtractor($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {
    parent::__init();
    $this->nodes = $this->caller->getNodes();
    $this->index = $this->caller->getNodeIndex();
    $this->bnode_prefix = $this->v('bnode_prefix', 'arc' . substr(md5(uniqid(rand())), 0, 4) . 'b', $this->a);
    $this->bnode_id = 0;
  }

  /*  */
  
  function x($re, $v, $options = 'si') {
    return ARC2::x($re, $v, $options);
  }

  function camelCase($v) {
    $r = ucfirst($v);
    while (preg_match('/^(.*)[\-\_ ](.*)$/', $r, $m)) {
      $r = $m[1] . ucfirst($m[2]);
    }
    return $r;
  }

  function createBnodeID(){
    $this->bnode_id++;
    return '_:' . $this->bnode_prefix . $this->bnode_id;
  }

  /*  */
  
  function extractRDF() {
  }

  /*  */
  
  function addTs($ts) {
    foreach ($ts as $t) {
      $this->caller->addT($t);
    }
  }
  
  function addT($t) {
    return $this->caller->addT($t);
  }
  
  /*  */
  
  function getSubNodes($n) {
    return $this->v($n['id'], array(), $this->index);
  }
  
  function getParentNode($n) {
    return isset($this->nodes[$n['p_id']]) ? $this->nodes[$n['p_id']] : 0;
  }

  /*  */
  
  function getSubNodesByClass($n, $cls, $skip_self = 0) {
    if (!$skip_self && $this->hasClass($n, $cls)) {
      return array($n);
    }
    $r = array();
    $sns = $this->getSubNodes($n);
    foreach ($sns as $sn) {
      if ($sub_r = $this->getSubNodesByClass($sn, $cls, 0)) {
        $r = array_merge($r, $sub_r);
      }
    }
    return $r;
  }
  
  function getSubNodeByClass($n, $cls, $skip_self = 0) {
    if (!$skip_self && $this->hasClass($n, $cls)) {
      return $n;
    }
    $sns = $this->getSubNodes($n);
    foreach ($sns as $sn) {
      if ($sub_r = $this->getSubNodeByClass($sn, $cls, 0)) {
        return $sub_r;
      }
    }
    return 0;
  }
  
  function getParentNodeByClass($n, $cls, $skip_self = 0) {
    if (!$skip_self && $this->hasClass($n, $cls)) {
      return $n;
    }
    if ($pn = $this->getParentNode($n)) {
      if ($sub_r = $this->getParentNodeByClass($pn, $cls, 0)) {
        return $sub_r;
      }
    }
    return 0;
  }
  
  /*  */
  
  function hasAttribute($a, $n, $v) {
    $vs = is_array($v) ? $v : array($v);
    $a_vs = $this->v($a . ' m', array(), $n['a']);
    return array_intersect($vs, $a_vs) ? 1 : 0;
  }
  
  function hasClass($n, $v) {
    return $this->hasAttribute('class', $n, $v);
  }

  function hasRel($n, $v) {
    return $this->hasAttribute('rel', $n, $v);
  }

  /*  */

  function getDocBase() {
    $root_node = $this->getRootNode();
    $r = $root_node['doc_base'];
    foreach ($this->getSubNodes($root_node) as $root_child) {
      if ($root_child['tag'] == 'head') {
        foreach ($this->getSubNodes($root_child) as $head_child) {
          if ($head_child['tag'] == 'base') {
            $r = $head_child['a']['href'];
            break;
          }
        }
      }
    }
    return $r;
  }
  
  /*  */
  
  function getPlainContent($n, $trim = 1) {
    if ($n['tag'] == 'cdata') {
      $r = $n['a']['val'];
    }
    elseif (trim($this->v('cdata', '', $n))) {
      $r = $n['cdata'];
      $sub_nodes = $this->getSubNodes($n);
      foreach ($sub_nodes as $sub_n) {
        $r .= $this->getPlainContent($sub_n, 0);
      }
    }
    elseif (($n['tag'] == 'img') && isset($n['a']['alt'])) {
      $r = $n['a']['alt'];
    }
    else {
      $r = '';
      $sub_nodes = $this->getSubNodes($n);
      foreach ($sub_nodes as $sub_n) {
        $r .= $this->getPlainContent($sub_n, 0);
      }
    }
    $r = preg_replace('/\s/s', ' ', $r);
    $r = preg_replace('/\s\s*/s', ' ', $r);
    return $trim ? trim($r) : $r;
  }
  
  function getContent($n, $outer = 0, $trim = 1) {
    if ($n['tag'] == 'cdata') {
      $r = $n['a']['val'];
    }
    else {
      $r = '';
      if ($outer) {
        $r .= '<' . $n['tag'];
        foreach ($n['a'] as $a => $val) {
          $r .= preg_match('/^[^\s]+$/', $a) && !is_array($val) ? ' ' . $a . '="' . addslashes($val) . '"' : '';
        }
        $r .= $n['empty'] ? '/>' : '>';
      }
      if (!$n['empty']) {
        $r .= $this->v('cdata', '', $n);
        $sub_nodes = $this->getSubNodes($n);
        foreach ($sub_nodes as $sub_n) {
          $r .= $this->getContent($sub_n, 1, 0);
        }
        if ($outer) {
          $r .= '</' . $n['tag'] . '>';
        }
      }
    }
    return $trim ? trim($r) : $r;
  }
  
  /*  */
  
  function getDocID($n) {
    $id = $n['id'];
    $k = 'doc_' . $id;
    if (!isset($this->caller->cache[$k])) {
      $this->caller->cache[$k] = $n['doc_base'];
    }
    return $this->caller->cache[$k];
  }

  function getDocOwnerID($n) {
    return '_:owner_of_' . $this->normalize($this->getDocID($n));
  }
  
  /*  */

  function normalize($v) {
    $v = preg_replace('/[\W\s]+/is', '_', strip_tags(strtolower($v)));
    $v = preg_replace('/http/', '', $v);
    $v = preg_replace('/[\_]+/', '_', $v);
    //$v = substr($v, 0, 30);
    $v = trim($v, '_');
    return $v;
  }

  /*  */
  
}
