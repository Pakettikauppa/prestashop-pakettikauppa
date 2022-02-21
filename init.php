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
      $all_configs = array_merge_recursive(array(
        'module_dir' => dirname(__FILE__),
      ), $configs);
      parent::__construct($all_configs);
    }
  }
}
