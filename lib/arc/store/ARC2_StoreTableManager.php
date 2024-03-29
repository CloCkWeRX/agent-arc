<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 RDF Store Table Manager
author:   Benjamin Nowack
version:  2008-01-21 (Tweak: made t in triple tabel UNIQUE, testing)
*/

ARC2::inc('Store');

class ARC2_StoreTableManager extends ARC2_Store {

  function __construct($a = '', &$caller) {
    parent::__construct($a, $caller);
  }
  
  function ARC2_StoreTableManager($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* db_con */
    parent::__init();
  }

  /*  */
  
  function getTableOptionsCode() {
    $v = $this->getDBVersion();
    $r = "";
    $r .= (($v < '04-01-00') && ($v >= '04-00-18')) ? 'ENGINE' : (($v >= '04-01-02') ? 'ENGINE' : 'TYPE');
    $r .= "=MyISAM";
    $r .= ($v >= '04.00.00') ? " CHARACTER SET utf8" : "";
    $r .= ($v >= '04.01.00') ? " COLLATE utf8_unicode_ci" : "";
    $r .= " DELAY_KEY_WRITE = 1";
    return $r;
  }
  
  /*  */
  
  function createTables() {
    if(!$this->createTripleTable()) {
      return $this->addError('Could not create "triple" table (' . mysql_error() . ').');
    }
    if(!$this->createTripleBackupTable()) {
      return $this->addError('Could not create "triple_backup" table (' . mysql_error() . ').');
    }
    if(!$this->createG2TTable()) {
      return $this->addError('Could not create "g2t" table (' . mysql_error() . ').');
    }
    if(!$this->createID2ValTable()) {
      return $this->addError('Could not create "id2val" table (' . mysql_error() . ').');
    }
    if(!$this->createS2ValTable()) {
      return $this->addError('Could not create "s2val" table (' . mysql_error() . ').');
    }
    if(!$this->createO2ValTable()) {
      return $this->addError('Could not create "o2val" table (' . mysql_error() . ').');
    }
    if(!$this->createSettingTable()) {
      return $this->addError('Could not create "setting" table (' . mysql_error() . ').');
    }
    return 1;
  }
  
  /*  */
  
  function createTripleTable() {
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "triple (
        t mediumint UNSIGNED NOT NULL,
        s mediumint UNSIGNED NOT NULL,
        p mediumint UNSIGNED NOT NULL,
        o mediumint UNSIGNED NOT NULL,
        o_lang_dt mediumint UNSIGNED NOT NULL,
        o_comp char(35) NOT NULL,                   /* normalized value for ORDER BY operations */
        s_type tinyint(1) NOT NULL default 0,       /* iri/bnode/literal => 0/1/2 */
        o_type tinyint(1) NOT NULL default 0,       /* iri/bnode/literal => 0/1/2 */
        misc tinyint(1) NOT NULL default 0,         /* temporary flags */
        UNIQUE KEY (t), KEY spo (s,p,o), KEY os (o,s), KEY po (p,o)
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql);
  }  
  
  /*  */
  
  function createTripleBackupTable() {
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "triple_backup (
        t mediumint UNSIGNED NOT NULL,
        data varchar(255) NOT NULL default '',      /* id-based triple elements, comma-separated (s,p,o,o_lang_dt,s_type,o_type) */
        misc tinyint(1) NOT NULL default 0,         /* temporary flags */
        PRIMARY KEY (t)
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql);
  }  
  
  /*  */
  
  function createG2TTable() {
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "g2t (
        g mediumint UNSIGNED NOT NULL,
        t mediumint UNSIGNED NOT NULL,
        KEY gt (g,t), KEY tg (t,g)
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql);
  }  
  
  /*  */
  
  function createID2ValTable() {
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "id2val (
        id mediumint UNSIGNED NOT NULL,
        misc tinyint(1) NOT NULL default 0,
        val text NOT NULL,
        val_type tinyint(1) NOT NULL default 0,     /* iri/bnode/literal => 0/1/2 */
        UNIQUE KEY (id,val_type), KEY v (val(64))
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql);
  }  
  
  /*  */
  
  function createS2ValTable() {
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "s2val (
        id mediumint UNSIGNED NOT NULL,
        cid mediumint UNSIGNED NOT NULL,
        misc tinyint(1) NOT NULL default 0,
        val text NOT NULL,
        PRIMARY KEY (id), KEY (cid), KEY v (val(64))
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql);
  }  
  
  /*  */
  
  function createO2ValTable() {
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "o2val (
        id mediumint UNSIGNED NOT NULL,
        cid mediumint UNSIGNED NOT NULL,
        misc tinyint(1) NOT NULL default 0,
        val text NOT NULL,
        PRIMARY KEY (id), KEY (cid), KEY v (val(64))
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql);
  }  
  
  /*  */
  
  function createSettingTable() {
    $sql = "
      CREATE TABLE IF NOT EXISTS " . $this->getTablePrefix() . "setting (
        k char(32) NOT NULL,
        val text NOT NULL,
        PRIMARY KEY (k)
      ) ". $this->getTableOptionsCode() . "
    ";
    return mysql_query($sql);
  }  
  
  /*  */
  
}
