<?php
namespace PS_Pakettikauppa_Core;

if (!defined('_PS_VERSION_')) {
  exit;
}

if ( ! class_exists(__NAMESPACE__ . '\Api') ) {
  class Api
  {
    private $core = null;

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

    public function get_additional_services($shipping_method = false, $allow_exclude = true)
    {
      $additional_services = array();
      $exclude_services = array('2106');

      $client = $this->load_client();
      $all_additional_services = $client->listAdditionalServices();

      if (!is_array($all_additional_services)) {
        return false;
      }

      foreach ($all_additional_services as $service) {
        if ($allow_exclude && in_array($service->service_code, $exclude_services)) {
          continue;
        }
        if ($shipping_method && $service->shipping_method_code != $shipping_method) {
          continue;
        }

        $service_name = $this->translate_additional_service($service->service_code);
        $service_name = (!$service_name) ? $service->name : $service_name;

        $additional_services[$service->service_code] = (object)array(
          'name' => $service_name,
          'specifiers' => $service->specifiers,
        );
      }

      return $additional_services;
    }

    private function translate_additional_service($service_code)
    {
      global $cookie;
      $iso_code = strtoupper(\Language::getIsoById((int)$cookie->id_lang));

      if (isset($this->core->configs->services_translates[$service_code]) && $iso_code != 'FI') {
        return $this->core->configs->services_translates[$service_code];
      }
      
      return false;
    }
  }
}
