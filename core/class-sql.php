<?php
namespace PS_Pakettikauppa_Core;

if (!defined('_PS_VERSION_')) {
  exit;
}

if ( ! class_exists(__NAMESPACE__ . '\Sql') ) {
  class Sql
  {
    public $core = null;
    
    public $table_main = _DB_PREFIX_ . 'pakettikauppa';
    public $table_methods = _DB_PREFIX_ . 'pakettikauppa_methods';

    public function __construct(Core $module)
    {
      $this->core = $module;
    }

    public function install()
    {
      $sql = array();

      $sql[] = 'CREATE TABLE IF NOT EXISTS `' . $this->table_main . '` (
        `id` int(11) NOT NULL AUTO_INCREMENT COMMENT "ID in this table",
        `id_cart` int(10) unsigned NOT NULL COMMENT "Prestashop cart ID",
        `id_carrier` int(11) NOT NULL COMMENT "Prestashop carrier ID",
        `method_code` int(10) NOT NULL COMMENT "Pakettikauppa method code",
        `pickup_point_id` int(11) NOT NULL COMMENT "Pakettikauppa pickup point id",
        `track_number` varchar(50) NOT NULL COMMENT "Shipment tracking number",
        PRIMARY KEY (`id`),
        UNIQUE (`id_cart`)
      ) ENGINE=' . _MYSQL_ENGINE_ . '  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21;';

      $sql[] = 'CREATE TABLE IF NOT EXISTS `' . $this->table_methods . '` (
        `id` int(11) NOT NULL AUTO_INCREMENT COMMENT "ID in this table",
        `id_carrier_reference` int(11) NOT NULL COMMENT "Prestashop carrier reference ID",
        `method_code` int(10) NOT NULL COMMENT "Pakettikauppa method code",
        `has_pp` int(1) NOT NULL DEFAULT "0" COMMENT "Method has pickup points",
        `services` text COMMENT "This shipping method services",
        PRIMARY KEY (`id`),
        UNIQUE (`id_carrier_reference`)
      ) ENGINE=' . _MYSQL_ENGINE_ . '  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21;';

      foreach ($sql as $query) {
        if ($this->exec_query($query) == false) {
          return false;
        }
      }

      return true;
    }

    public function uninstall()
    {
      $sql = array();

      foreach ($sql as $query) {
        if ($this->exec_query($query) == false) {
          return false;
        }
      }

      return true;
    }

    public function insert_row($params)
    {
      if (!isset($params['table']) || empty($params['values']) || !is_array($params['values'])) {
        return false;
      }

      $table = $this->get_table_name($params['table']);

      $sql_query_keys = '';
      $sql_query_values = '';
      foreach ($params['values'] as $key => $value) {
        if (!empty($sql_query_keys)) {
          $sql_query_keys .= ',';
          $sql_query_values .= ',';
        }
        $sql_query_keys .= $key;
        $sql_query_values .= "'" . $value . "'";
      }

      $sql_query = sprintf('INSERT INTO %1$s (%2$s) VALUES(%3$s)', $table, $sql_query_keys, $sql_query_values);

      if (!empty($params['on_duplicate'])) {
        $sql_query .= ' ON DUPLICATE KEY UPDATE ';
        $duplicates = '';
        foreach ($params['on_duplicate'] as $key => $value) {
          if (!empty($duplicates)) {
            $duplicates .= ',';
          }
          $duplicates .= $key . '=' . $value;
        }
        $sql_query .= $duplicates;
      }

      return $this->exec_query($sql_query);
    }

    public function update_row($params)
    {
      if (!isset($params['table'])
        || empty($params['update']) || !is_array($params['update'])
        || empty($params['where']) || !is_array($params['where'])
      ) {
        return false;
      }

      $table = $this->get_table_name($params['table']);

      $set_values = '';
      foreach ($params['update'] as $key => $value) {
        if (!empty($set_values)) {
          $set_values .= ',';
        }
        $set_values .= $key . "='" . $value . "'";
      }

      $condition = (isset($params['condition'])) ? $params['condition'] : 'AND';
      $where_values = $this->get_query_where($params['where'], $condition);

      $sql_query = sprintf('UPDATE %1$s SET %2$s WHERE %3$s', $table, $set_values, $where_values);

      return $this->exec_query($sql_query);
    }

    public function delete_row($params)
    {
      if (!isset($params['table']) || empty($params['where']) || !is_array($params['where'])) {
        return false;
      }

      $table = $this->get_table_name($params['table']);

      $condition = (isset($params['condition'])) ? $params['condition'] : 'AND';
      $where_values = $this->get_query_where($params['where'], $condition);

      $sql_query = sprintf('DELETE FROM %1$s WHERE %2$s', $table, $where_values);

      return $this->exec_query($sql_query);
    }

    public function get_rows($params)
    {
      if (!isset($params['table'])) {
        return false;
      }

      $table = $this->get_table_name($params['table']);

      $get_values = '*';
      if (!empty($params['get_values']) && is_array($params['get_values'])) {
        $get_values = '';
        foreach ($params['get_values'] as $key => $column) {
          if (!empty($get_values)) {
            $get_values .= ',';
          }
          $get_values .= $column;
          if (!is_numeric($key)) {
            $get_values .= ' as ' . $key;
          }
        }
      }

      $sql_query = sprintf('SELECT %1$s FROM %2$s', $get_values, $table);

      if (!empty($params['where']) && is_array($params['where'])) {
        $condition = (isset($params['condition'])) ? $params['condition'] : 'AND';
        $where_values = $this->get_query_where($params['where'], $condition);

        $sql_query .= sprintf(' WHERE %3$s', $get_values, $table, $where_values);
      }

      return $this->get_by_query($sql_query);
    }

    public function get_single_row($params)
    {
      $all_rows = $this->get_rows($params);

      return (isset($all_rows[0])) ? $all_rows[0] : false;
    }

    public function exec_query($sql_query)
    {
      return \DB::getInstance()->Execute($sql_query);
    }

    public function get_by_query($sql_query)
    {
      return \DB::getInstance()->ExecuteS($sql_query);
    }

    private function get_table_name($key)
    {
      switch ($key) {
        case 'main':
          $table_name = $this->table_main;
          break;
        case 'methods':
          $table_name = $this->table_methods;
          break;
        default:
          $table_name = $key;
      }

      return $table_name;
    }

    private function get_query_where($values, $condition = 'AND')
    {
      $where_values = '';
      foreach ($values as $key => $value) {
        if (!empty($where_values)) {
          $where_values .= ' ' . $condition . ' ';
        }
        $where_values .= $key . "='" . $value . "'";
      }

      return $where_values;
    }
  }
}
