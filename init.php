<?php
if ( ! defined('_PS_VERSION_') ) {
    exit;
}

if ( ! class_exists('\Pakettikauppa\Client') ) {
  require_once(dirname(__FILE__) . '/vendor/autoload.php');
}

if ( ! class_exists('PS_Pakettikauppa') ) {
  require_once(dirname(__FILE__) . '/core/class-core.php');
  
  class PS_Pakettikauppa extends PS_Pakettikauppa_Core\Core {
    public function __construct($configs = array())
    {
      $required_configs = array(
        'module_dir' => dirname(__FILE__),
        'module_name' => 'pakettikauppa',
        'translates' => array(),
      );

      foreach ($required_configs as $req_config_key => $req_config_value) {
        if (!isset($configs[$req_config_key])) {
          $configs[$req_config_key] = $req_config_value;
        }
        if (is_array($req_config_value)) {
          foreach ($req_config_value as $element_key => $element_value) {
            if (!isset($configs[$req_config_key][$element_key])) {
              $configs[$req_config_key][$element_key] = $element_value;
            }
          }
        }
      }
      parent::__construct($configs);
    }
  }
}
