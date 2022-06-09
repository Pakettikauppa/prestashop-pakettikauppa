<?php
namespace PS_Pakettikauppa_Core;

if (!defined('_PS_VERSION_')) {
  exit;
}

if ( ! class_exists(__NAMESPACE__ . '\Carrier') ) {
  class Carrier
  {
    private $core = null;

    public function __construct(Core $module)
    {
      $this->core = $module;
    }

    /**
     * Save Pakettikauppa method information to database
     *
     * @param (object) $shipping_method - Single Pakettikauppa shipping method object from API
     * @param (integer) $carrier_reference - Prestashop carrier reference ID
     */
    public function associate_method_with_carrier($shipping_method, $carrier_reference)
    {
      if (!is_object($shipping_method)) {
        return false;
      }

      $services = array();
      if (!empty($shipping_method->additional_services)) {
        foreach ($shipping_method->additional_services as $service) {
          if (!empty($service->service_code)) {
            $services[] = $service->service_code;
          }
        }
      }

      $this->core->sql->insert_row(array(
        'table' => 'methods',
        'values' => array(
          'id_carrier_reference' => $carrier_reference,
          'method_code' => $shipping_method->shipping_method_code,
          'has_pp' => (!empty($shipping_method->has_pickup_points)) ? $shipping_method->has_pickup_points : 0,
          'countries' => implode(',', $shipping_method->supported_countries),
          'services' => implode(',', $services),
        ),
        'on_duplicate' => array(
          'method_code' => $shipping_method->shipping_method_code,
          'has_pp' => (!empty($shipping_method->has_pickup_points)) ? $shipping_method->has_pickup_points : 0,
        ),
      ));

      return true;
    }

    public function check_if_association_exist($method_code)
    {
      $carriers = $this->core->sql->get_rows(array(
        'table' => _DB_PREFIX_ . 'carrier',
        'get_values' => array(
          'id_reference',
          'name'
        ),
        'where' => array(
          'is_module' => 1,
          'external_module_name' => 'pakettikauppa',
          'deleted' => 0
        )
      ));

      $associations = $this->core->sql->get_rows(array(
        'table' => 'methods'
      ));

      foreach ($carriers as $carrier) {
        foreach ($associations as $association) {
          if ($carrier['id_reference'] == $association['id_carrier_reference'] && $method_code == $association['method_code']) {
            return true;
          }
        }
      }

      return false;
    }
  }
}
