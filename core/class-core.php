<?php
namespace PS_Pakettikauppa_Core;

if (!defined('_PS_VERSION_')) {
  exit;
}

if ( ! class_exists(__NAMESPACE__ . '\Core') ) {
  abstract class Core
  {
    public static $instance; // The class is a singleton.

    public $configs;

    // Classes
    public $sql;
    public $api;
    public $carrier;
    public $label;

    public function __construct($configs = array())
    {    
      self::$instance = $this;

      $this->configs = $this->get_configs($configs);
      //Load classes
      $this->sql = $this->load_sql_class();
      $this->api = $this->load_api_class();
      $this->carrier = $this->load_carrier_class();
      $this->label = $this->load_label_class();
    }

    public static function get_instance() {
      return self::$instance;
    }

    private function get_configs($configs = array())
    {
      $default = array(
        'module_dir' => _PS_MODULE_DIR_ . 'pakettikauppa',
        'translates' => array(),
      );

      foreach ($default as $key => $value) {
        $configs[$key] = (isset($configs[$key])) ? $configs[$key] : $value;
      }

      return (object) $configs;
    }

    protected function load_sql_class()
    {
      require_once($this->configs->module_dir . '/core/class-sql.php');

      $class = new Sql($this);

      return $class;
    }

    protected function load_api_class()
    {
      require_once($this->configs->module_dir . '/core/class-api.php');

      $class = new Api($this);

      return $class;
    }

    protected function load_carrier_class()
    {
      require_once($this->configs->module_dir . '/core/class-carrier.php');

      $class = new Carrier($this);

      return $class;
    }

    protected function load_label_class()
    {
      require_once($this->configs->module_dir . '/core/class-label.php');

      $class = new Label($this);

      return $class;
    }
  }
}
