<?php
/*
homepage: http://arc.semsol.org/
license:  http://arc.semsol.org/license

class:    ARC2 RDF Store LOAD Query Handler
author:   Benjamin Nowack
version:  2008-02-15 (Addition: Support for RSS Parser)
*/

ARC2::inc('StoreQueryHandler');

class ARC2_StoreLoadQueryHandler extends ARC2_StoreQueryHandler {

  function __construct($a = '', &$caller) {/* caller has to be a store */
    parent::__construct($a, $caller);
  }
  
  function ARC2_StoreLoadQueryHandler($a = '', &$caller) {
    $this->__construct($a, $caller);
  }

  function __init() {/* db_con, store_log_inserts */
    parent::__init();
    $this->store =& $this->caller;
  }

  /*  */
  
  function runQuery($infos, $data = '', $keep_bnode_ids = 0) {
    $url = $infos['query']['url'];
    $graph = $infos['query']['target_graph'];
    $this->target_graph = $graph ? $graph : $url;
    $this->keep_bnode_ids = $keep_bnode_ids;
    /* reader */
    ARC2::inc('Reader');
    $reader =& new ARC2_Reader($this->a, $this);
    $reader->activate($url, $data);
    /* format detection */
    $mappings = array(
      'rdfxml' => 'RDFXML', 
      'turtle' => 'Turtle', 
      'ntriples' => 'Turtle', 
      'rss' => 'RSS',
      'n3' => 'Turtle', 
      'html' => 'SemHTML'
    );
    $format = $reader->getFormat();
    if (!$format || !isset($mappings[$format])) {
      return $this->addError('No loader available for "' .$url. '": ' . $format);
    }
    /* format loader */
    $suffix = 'Store' . $mappings[$format] . 'Loader';
    ARC2::inc($suffix);
    $cls = 'ARC2_' . $suffix;
    $loader =& new $cls($this->a, $this);
    $loader->setReader($reader);
    /* table lock */
    if (!$this->store->lockTables()) {
      $this->addError('Could not lock tables.');
      return array();
    }
    /* logging */
    $this->t_count = 0;
    $this->t_start = ARC2::mtime();
    $this->log_inserts = $this->v('store_log_inserts', 0, $this->a);
    if ($this->log_inserts) {
      @unlink("arc_insert_log.txt");
      $this->inserts = array();
      $this->insert_times = array();
      $this->t_prev = $this->t_start;
      $this->t_count_prev = 0 ;
    }
    /* load and parse */
    $this->max_term_id = $this->getMaxTermID();
    $this->max_triple_id = $this->getMaxTripleID();
    $this->term_ids = array();
    $this->triple_ids = array();
    $this->sql_buffers = array();
    $r = $loader->parse($url, $data);
    /* done */
    $this->checkSQLBuffers(1);
    if ($this->log_inserts) {
      $this->logInserts();
    }
    $this->store->unlockTables();
    if ((rand(1, 50) == 1)) $this->store->optimizeTables();
    $t2 = ARC2::mtime();
    $dur = round($t2 - $this->t_start, 4);
    $r = array(
      't_count' => $this->t_count,
      'load_time' => $dur,
    );
    if ($this->log_inserts) {
      $r['inserts'] = $this->inserts;
      $r['insert_times'] = $this->insert_times;
    }
    return $r;
  }
  
  /*  */

  function addT($s, $p, $o, $s_type, $o_type, $o_dt = '', $o_lang = '') {
    $type_ids = array ('iri' => '0', 'bnode' => '1' , 'literal' => '2');
    $g = $this->getTermID($this->target_graph, '0', 'id');
    $s = (($s_type == 'bnode') && !$this->keep_bnode_ids) ? '_:b' . abs(crc32($g . $s)) . '_' . substr(substr($s, 2), -10) : $s;
    $o = (($o_type == 'bnode') && !$this->keep_bnode_ids) ? '_:b' . abs(crc32($g . $o)) . '_' . substr(substr($o, 2), -10) : $o;
    /* triple */
    $t = array(
      's' => $this->getTermID($s, $type_ids[$s_type], 's'),
      'p' => $this->getTermID($p, '0', 'id'),
      'o' => $this->getTermID($o, $type_ids[$o_type], 'o'),
      'o_lang_dt' => $this->getTermID($o_dt . $o_lang, $o_dt ? '0' : '2', 'id'),
      'o_comp' => $this->getOComp($o),
      's_type' => $type_ids[$s_type], 
      'o_type' => $type_ids[$o_type],
    );
    $t['t'] = $this->getTripleID($t);
    $this->bufferTripleSQL($t);
    /* triple_backup */
    $tb = array(
      't' => $t['t'],
      'data' => serialize(array($t['t'], $t['s'], $t['p'], $t['o'], $t['o_lang_dt'], $t['s_type'], $t['o_type']))
    );
    $this->bufferTripleBackupSQL($tb);
    /* g2t */
    $g2t = array('g' => $g, 't' => $t['t']);
    $this->bufferGraphSQL($g2t);
    $this->t_count++;
    if (($this->t_count % 1000) == 0) {
      $force_write = 0;
      $reset_buffers = 0;
      $refresh_lock = 0;
      $split_tables = 0;
      if (($this->t_count % 5000) == 0) {
        $force_write = 1;
        $reset_buffers = 1;
        if (($this->t_count % 50000) == 0) {
          $refresh_lock = 1;
          $split_tables = 1;
        }
        if ($this->log_inserts) {
          $this->logInserts();
        }
      }
      $this->checkSQLBuffers($force_write, $reset_buffers, $refresh_lock, $split_tables);
    }
  }

  /*  */
  
  function getMaxTermID() {
    $con = $this->store->getDBCon();
    $sql = '';
    foreach (array('id2val', 's2val', 'o2val') as $tbl) {
      $sql .= $sql ? ' UNION ' : '';
      $sql .= "(SELECT MAX(id) as `id` FROM " . $this->store->getTablePrefix() . $tbl . ')';
    }
    $r = 0;
    if (($rs = mysql_query($sql)) && mysql_num_rows($rs)) {
      while ($row = mysql_fetch_array($rs)) {
        $r = ($r < $row['id']) ? $row['id'] : $r;
      }
    }
    return $r + 1;
  }
  
  function getMaxTripleID() {
    $con = $this->store->getDBCon();
    $sql = "SELECT MAX(t) AS `id` FROM " . $this->store->getTablePrefix() . "triple";
    if (($rs = mysql_query($sql)) && mysql_num_rows($rs) && ($row = mysql_fetch_array($rs))) {
      return $row['id'] + 1;
    }
    return 1;
  }
  
  function getTermID($val, $type_id, $tbl) {
    $con = $this->store->getDBCon();
    /* buffered */
    if (isset($this->term_ids[$val])) {
      if (!isset($this->term_ids[$val][$tbl])) {
        foreach (array('id', 's', 'o') as $other_tbl) {
          if (isset($this->term_ids[$val][$other_tbl])) {
            $this->term_ids[$val][$tbl] = $this->term_ids[$val][$other_tbl];
            $this->bufferIDSQL($tbl, $this->term_ids[$val][$tbl], $val, $type_id);
            break;
          }
        }
      }
      return $this->term_ids[$val][$tbl];
    }
    /* db */
    $sql = '';
    foreach (array('id2val', 's2val', 'o2val') as $sub_tbl) {
      $sql .= $sql ? ' UNION ' : '';
      $cid_suffix = preg_match('/^(s|o)/', $sub_tbl) ? ', cid AS `cid`' : ', id AS `cid`';
      $sql .= "(SELECT id AS `id`" . $cid_suffix . ", '" . $sub_tbl . "' AS `tbl` FROM " . $this->store->getTablePrefix() . $sub_tbl . " WHERE BINARY val = '" . mysql_real_escape_string($val) . "')";
    }
    if (($rs = mysql_query($sql . ' LIMIT 1')) && mysql_num_rows($rs) && ($row = mysql_fetch_array($rs))) {
      $this->term_ids[$val] = array($tbl => isset($row['cid']) ? $row['cid'] : $row['id']);
      if ($row['tbl'] != $tbl) {
        $this->bufferIDSQL($tbl, $row['id'], $val, $type_id);
      }
    }
    /* new */
    else {
      $this->term_ids[$val] = array($tbl => $this->max_term_id);
      $this->bufferIDSQL($tbl, $this->max_term_id, $val, $type_id);
      $this->max_term_id++;
    }
    return $this->term_ids[$val][$tbl];
  }
 
  function getTripleID($t) {
    $con = $this->store->getDBCon();
    $val = print_r($t, 1);
    /* buffered */
    if (isset($this->triple_ids[$val])) {
      return $this->triple_ids[$val];
    }
    /* db */
    $sql = "SELECT t FROM " . $this->store->getTablePrefix() . "triple WHERE 
      s = " . $t['s'] . " AND p = " . $t['p'] . " AND o = " . $t['o'] . " AND o_lang_dt = " . $t['o_lang_dt'] . " AND s_type = " . $t['s_type'] . " AND o_type = " . $t['o_type'] . "
      LIMIT 1
    ";
    if (($rs = mysql_query($sql)) && mysql_num_rows($rs) && ($row = mysql_fetch_array($rs))) {
      $this->triple_ids[$val] = $row['t'];
    }
    /* new */
    else {
      $this->triple_ids[$val] = $this->max_triple_id;
      $this->max_triple_id++;
    }
    return $this->triple_ids[$val];
  }
  
  function getOComp($val) {
    /* try date (e.g. 21 August 2007) */
    if (preg_match('/^[0-9]{1,2}\s+[a-z]+\s+[0-9]{4}/i', $val) && ($uts = strtotime($val)) && ($uts !== -1)) {
      return date("Y-m-d\TH:i:s", $uts);
    }
    if (preg_match('/^[0-9]{4}[0-9\-\:\T\Z\+]+([a-z]{2,3})?$/i', $val)) {
      return $val;
    }
    if (is_numeric($val)) {
      $val = sprintf("%f", $val);
      if (preg_match("/([\-\+])([0-9]*)\.([0-9]*)/", $val, $m)) {
        return $m[1] . sprintf("%018s", $m[2]) . "." . sprintf("%-015s", $m[3]);
      }
      if (preg_match("/([0-9]*)\.([0-9]*)/", $val, $m)) {
        return "+" . sprintf("%018s", $m[1]) . "." . sprintf("%-015s", $m[2]);
      }
      return $val;
    }
		/* any other string: remove tags, linebreaks etc.  */
    return substr(trim(preg_replace('/[\W\s]+/is', '-', strip_tags($val))), 0, 35);
  }
  
  /*  */
  
  function bufferTripleSQL($t) {
    $tbl = 'triple';
    $sql = ", ";
    if (!isset($this->sql_buffers[$tbl])) {
      $this->sql_buffers[$tbl] = array("INSERT IGNORE INTO " . $this->store->getTablePrefix() . $tbl . " (t, s, p, o, o_lang_dt, o_comp, s_type, o_type) VALUES");
      $sql = " ";
    }
    $sql .= "(" . $t['t'] . ", " . $t['s'] . ", " . $t['p'] . ", " . $t['o'] . ", " . $t['o_lang_dt'] . ", '" . mysql_real_escape_string($t['o_comp']) . "', " . $t['s_type'] . ", " . $t['o_type'] . ")";
    $this->sql_buffers[$tbl][] = $sql;
  }
  
  function bufferTripleBackupSQL($tb) {
    $tbl = 'triple_backup';
    $sql = ", ";
    if (!isset($this->sql_buffers[$tbl])) {
      $this->sql_buffers[$tbl] = array("INSERT IGNORE INTO " . $this->store->getTablePrefix() . $tbl . " (t, data) VALUES");
      $sql = " ";
    }
    $sql .= "(" . $tb['t'] . ", '" . mysql_real_escape_string($tb['data']) . "')";
    $this->sql_buffers[$tbl][] = $sql;
  }
  
  function bufferGraphSQL($g2t) {
    $tbl = 'g2t';
    $sql = ", ";
    if (!isset($this->sql_buffers[$tbl])) {
      $this->sql_buffers[$tbl] = array("INSERT IGNORE INTO " . $this->store->getTablePrefix() . $tbl . " (g, t) VALUES");
      $sql = " ";
    }
    $sql .= "(" . $g2t['g'] . ", " . $g2t['t'] . ")";
    $this->sql_buffers[$tbl][] = $sql;
  }
  
  function bufferIDSQL($tbl, $id, $val, $val_type) {
    $tbl = $tbl . '2val';
    $sql = ", ";
    if (!isset($this->sql_buffers[$tbl])) {
      $cols = ($tbl == 'id2val') ? "id, val, val_type" : "id, cid, val";
      $this->sql_buffers[$tbl] = array("INSERT IGNORE INTO " . $this->store->getTablePrefix() . $tbl . "(" . $cols . ") VALUES");
      $sql = " ";
    }
    if ($tbl == 'id2val') {
      $sql .= "(" . $id . ", '" . mysql_real_escape_string($val) . "', " . $val_type . ")";
    }
    else {
      $sql .= "(" . $id . ", " . $id . ", '" . mysql_real_escape_string($val) . "')";
    }
    $this->sql_buffers[$tbl][] = $sql;
  }
  
  /*  */

  function checkSQLBuffers($force_write = 0, $reset_id_buffers = 0, $refresh_lock = 0, $split_tables = 0) {
    $con = $this->store->getDBCon();
  	@set_time_limit($this->v('time_limit', 60, $this->a));
    foreach (array('triple' => 500, 'triple_backup' => 500, 'g2t' => 1000, 'id2val' => 500, 's2val' => 500, 'o2val' => 500) as $tbl => $max) {
      $buffer_size = isset($this->sql_buffers[$tbl]) ? count($this->sql_buffers[$tbl]) : 0;
      if ($buffer_size && ($force_write || $buffer_size > $max)) {
        $t1 = ARC2::mtime();
        mysql_query(join("", $this->sql_buffers[$tbl]));
        /* table error */
        if ($er = mysql_error()) {
          echo "\n" . $er . ":\n" . join("", $this->sql_buffers[$tbl]) . "\n\n";
          if (preg_match('/\/([a-z0-9\_\-]+)\' .+ should be repaired/i', $er, $m)) {
            mysql_query('REPAIR TABLE ' . rawurlencode($m[1]));
            echo "\n Tried to repair table '" . $m[1]. "'.";
          }
        }
        unset($this->sql_buffers[$tbl]);
        if ($this->log_inserts) {
          $t2 = ARC2::mtime();
          $this->inserts[$tbl] = $this->v($tbl, 0, $this->inserts) + max(0, mysql_affected_rows());
          $dur = round($t2 - $t1, 4);
          $this->insert_times[$tbl] = isset($this->insert_times[$tbl]) ? $this->insert_times[$tbl] : array('min' => $dur, 'max' => $dur, 'sum' => $dur);
          $this->insert_times[$tbl] = array('min' => min($dur, $this->insert_times[$tbl]['min']), 'max' => max($dur, $this->insert_times[$tbl]['max']), 'sum' => $dur + $this->insert_times[$tbl]['sum']);
        }
        /* reset term id buffers */
        if ($reset_id_buffers) {
          $this->term_ids = array();
          $this->triple_ids = array();
        }
        /* refresh lock */
        if ($refresh_lock) {
          $this->store->unlockTables();
          if (!$this->store->lockTables()) {
            $this->addError('Could not lock tables.');
          }
        }
      }
    }
    return 1;
  }

  /* speed log */
  
  function logInserts() {
    $t_start = $this->t_start;
    $t_prev = $this->t_prev;
    $t_now = ARC2::mtime();
    $tc_prev = $this->t_count_prev;
    $tc_now = $this->t_count;
    $tc_diff = $tc_now - $tc_prev;
    
    $dur_full = $t_now - $t_start;
    $dur_diff = $t_now - $t_prev;

    $speed_full = round($tc_now / $dur_full);
    $speed_now = round($tc_diff / $dur_diff);

    $r = $tc_diff . ' in ' . round($dur_diff, 5) . ' = ' . $speed_now . ' t/s  (' .$tc_now. ' in ' . round($dur_full, 5). ' = ' . $speed_full . ' t/s )'; 
    $fp = @fopen("arc_insert_log.txt", "a");
    @fwrite($fp, $r . "\r\n");
    @fclose($fp);
    
    $this->t_prev = $t_now;
    $this->t_count_prev = $tc_now;
  }

}
