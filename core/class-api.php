<?php
namespace PS_Pakettikauppa_Core;

if (!defined('_PS_VERSION_')) {
  exit;
}

if ( ! class_exists(__NAMESPACE__ . '\Api') ) {
  class Api
  {
    public $core = null;

    public function __construct(Core $module)
    {
      $this->core = $module;
    }

    public function load_client()
    {
      if (\Configuration::get('PAKETTIKAUPPA_MODE') == 1) {
        $client = new \Pakettikauppa\Client(array('test_mode' => true));
      } else {
        $client = new \Pakettikauppa\Client(array('api_key' => \Configuration::get('PAKETTIKAUPPA_API_KEY'), 'secret' => \Configuration::get('PAKETTIKAUPPA_SECRET')));
      }

      return $client;
    }

    public function get_pickup_info($point_id, $service)
    {
      $client = $this->load_client();
      $pickup_point = $client->getPickupPointInfo($point_id, $service);

      return $pickup_point;
    }
  }
}
