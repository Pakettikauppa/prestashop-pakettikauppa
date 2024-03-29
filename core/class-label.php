<?php
namespace PS_Pakettikauppa_Core;

if (!defined('_PS_VERSION_')) {
  exit;
}

if ( ! class_exists(__NAMESPACE__ . '\Label') ) {
  class Label
  {
    private $core = null;
    private $trans = array();

    public function __construct(Core $module)
    {
      $this->core = $module;
      $this->trans = $this->core->configs->translates;
    }

    public function generate_shipment($id_order, $regenerate = false)
    {
      $order = new \Order((int)$id_order);

      if (!\Validate::isLoadedObject($order)) {
        return array('status' => 'error', 'msg' => $this->trans['error_order_object']);
      }

      $custom_state_success = \Configuration::get('PAKETTIKAUPPA_CUSTOM_STATE_READY');
      $custom_state_error = \Configuration::get('PAKETTIKAUPPA_CUSTOM_STATE_ERROR');

      $ship_detail = $this->core->sql->get_single_row(array(
        'table' => 'orders',
        'get_values' => array(
          'point' => 'pickup_point_id',
          'method' => 'method_code',
          'carrier' => 'id_carrier',
          'services' => 'additional_services',
        ),
        'where' => array(
          'id_cart' => $order->id_cart,
        ),
      ));

      if (empty($ship_detail)) {
        return array('status' => 'error', 'msg' => $this->trans['error_ship_not_found']);
      }

      $order_invoice_collection = $order->getInvoicesCollection();

      if (empty(\Configuration::get('PAKETTIKAUPPA_POSTCODE'))) {
        return array('status' => 'error', 'msg' => $this->trans['error_required_postcode']);
      }

      $address = new \Address($order->id_address_delivery);
      $customer_data = new \Customer($order->id_customer);
      $customer_country = $this->core->sql->get_single_row(array(
        'table' => _DB_PREFIX_ . 'country',
        'get_values' => array('iso_code'),
        'where' => array(
          'id_country' => $address->id_country,
        ),
      ))['iso_code'];
      $currency = new \CurrencyCore($order->id_currency);

      /*** START Additional services ***/
      $all_additional_services = $this->core->api->get_additional_services($ship_detail['method']);
      $additional_services = array();

      $selected_services = $this->core->services->get_order_services(false, $ship_detail['services']);

      /* Pickup points */
      if (!empty($ship_detail['point'])) {
        $service = $this->core->services->get_service_params($ship_detail['method'], 'pickup_point', array( 'pickup_point' => $ship_detail['point'] ));
        foreach ($service['params'] as $service_param_key => $service_param_value) {
          $additional_services[$service['service']][$service_param_key] = $service_param_value;
        }
      }

      /* COD */
      $service_code = $this->core->services->get_service_code('cod');
      $amount = (!empty($selected_services[$service_code])) ? $selected_services[$service_code] : $order->getOrdersTotalPaid();
      $service = $this->core->services->get_service_params($ship_detail['method'], 'cod', array( 'payment_module' => $order->module, 'amount' => $amount, 'check_payment' => false ));
      foreach ($service['params'] as $service_param_key => $service_param_value) {
        if (isset($selected_services[$service['service']])) {
          $additional_services[$service['service']][$service_param_key] = $service_param_value;
        }
      }

      /* Multiple shipments */
      $service_code = $this->core->services->get_service_code('multiple');
      $total_shipments = (!empty($selected_services[$service_code])) ? $selected_services[$service_code] : 1;
      if ($total_shipments > 1) {
        $service = $this->core->services->get_service_params($ship_detail['method'], 'multiple', array( 'count' => $total_shipments ));
        foreach ($service['params'] as $service_param_key => $service_param_value) {
          if (isset($selected_services[$service['service']])) {
            $additional_services[$service['service']][$service_param_key] = $service_param_value;
          }
        }
      }

      /* Dangerous goods */
      $service_code = $this->core->services->get_service_code('dangerous');
      $dg_weight = (!empty($selected_services[$service_code])) ? $selected_services[$service_code] : '';
      $service = $this->core->services->get_service_params($ship_detail['method'], 'dangerous', array( 'order' => $order, 'weight' => $dg_weight ));
      foreach ($service['params'] as $service_param_key => $service_param_value) {
        if (isset($selected_services[$service['service']])) {
          $additional_services[$service['service']][$service_param_key] = $service_param_value;
        }
      }
      
      /* Add other services */
      foreach ($all_additional_services as $service_code => $service_params) {
        if (isset($additional_services[$service_code])) {
          continue;
        }
        if (isset($selected_services[$service_code])) {
          $additional_services[$service_code] = array();
        }
      }
      /*** END Additional services ***/
      
      $total_weight = $this->core->sql->get_by_query('SELECT o.reference,sum(od.product_weight) as weight FROM `' . _DB_PREFIX_ . 'order_detail` od left join ps_orders o on od.id_order=o.id_order WHERE o.id_order=' . $id_order);
      
      $label_comment = \Configuration::get('PAKETTIKAUPPA_LABEL_COMMENT');
      $label_comment = str_replace('{order_id}', $id_order, $label_comment);
      $label_comment = str_replace('{order_reference}', $order->reference, $label_comment);

      $params = array(
        'sender' => array(
          'name1' => \Configuration::get('PAKETTIKAUPPA_STORE_NAME'),
          'addr1' => \Configuration::get('PAKETTIKAUPPA_STORE_ADDRESS'),
          'postcode' => \Configuration::get('PAKETTIKAUPPA_POSTCODE'),
          'city' => \Configuration::get('PAKETTIKAUPPA_CITY'),
          'phone' => \Configuration::get('PAKETTIKAUPPA_PHONE'),
          'country' => \Configuration::get('PAKETTIKAUPPA_COUNTRY'),
        ),
        'receiver' => array(
          'name1' => $address->firstname . " " . $address->lastname,
          'addr1' => $address->address1 . " " . $address->address2,
          'postcode' => $address->postcode,
          'city' => $address->city,
          'country' => $customer_country,
          'email' => $customer_data->email,
          'phone' => $address->phone,
        ),
        'info' => array(
          'reference' => $id_order,
          //'reference' => $order->reference, //Or reference
          'currency' => $currency->iso_code,
        ),
        'additional_services' => $additional_services,
        'parcel' => array(
          'reference' => $total_weight[0]['reference'],
          'weight' => $total_weight[0]['weight'],
          'contents' => $label_comment,
        ),
        'shipment' => array(
          'method' => $ship_detail['method'],
        ),
      );
      
      if (!$regenerate) {
        $tracking_code = $this->get_tracking_number_from_db($order->id_cart);
        if (!empty($tracking_code)) {
          $shipment = $this->get_label_pdf($tracking_code);
          if (empty($shipment['shipping_label'])) {
            $regenerate = true;
          }
        } else {
          $regenerate = true;
        }
      }

      if ($regenerate) {
        $shipment = $this->register_shipment($params);
      }

      if (empty($shipment['tracking_code'])) {
        $this->change_order_state($id_order, $custom_state_error);
        if (!empty($shipment['status']) && $shipment['status'] === 'error') {
          return $shipment;
        }
        return array('status' => 'error', 'msg' => $this->trans['error_tracking_empty']);
      }

      if ($regenerate) {
        $this->save_tracking_number_to_db($order->id_cart, $shipment['tracking_code']);
      }

      $this->set_tracking_number_to_order($id_order, $shipment['tracking_code']);

      $this->change_order_state($id_order, $custom_state_success);
      return $shipment;
    }

    public function generate_label_pdf($id_order, $regenerate = false)
    {
      $shipment = $this->generate_shipment($id_order, $regenerate);

      if (!empty($shipment['shipping_label'])) {
        try {
          $content_disposition = 'inline';
          $filename = $shipment['tracking_code'];
          
          header('Content-Type: application/pdf');
          header('Content-Description: File Transfer');
          header('Content-Transfer-Encoding: binary');
          header("Content-Disposition: $content_disposition;filename=\"{$filename}.pdf\"");
          header('Content-Length: ' . strlen($shipment['shipping_label']));
          
          die($shipment['shipping_label']);
        } catch (Exception $ex) {
          die($ex->getMessage());
        }
      }

      if (!empty($shipment['status']) && $shipment['status'] === 'error' && !empty($shipment['msg'])) {
        die($shipment['msg']);
      }

      die($this->trans['error_label_pdf_empty']);
    }

    public function register_shipment($params = array())
    {
      $tracking_code = false;
      $tracking_url = false;
      $shipping_label = false;

      $params = $this->prepare_shipment_params($params);
      $client = $this->core->api->load_client();

      $sender = new \Pakettikauppa\Shipment\Sender();
      $sender->setName1($params['sender']['name1']);
      $sender->setAddr1($params['sender']['addr1']);
      $sender->setPostcode($params['sender']['postcode']);
      $sender->setCity($params['sender']['city']);
      $sender->setPhone($params['sender']['phone']);
      $sender->setCountry($params['sender']['country']);

      $receiver = new \Pakettikauppa\Shipment\Receiver();
      $receiver->setName1($params['receiver']['name1']);
      $receiver->setAddr1($params['receiver']['addr1']);
      $receiver->setPostcode($params['receiver']['postcode']);
      $receiver->setCity($params['receiver']['city']);
      $receiver->setCountry($params['receiver']['country']);
      $receiver->setEmail($params['receiver']['email']);
      $receiver->setPhone($params['receiver']['phone']);

      $info = new \Pakettikauppa\Shipment\Info();
      $info->setReference($params['info']['reference']);
      $info->setCurrency($params['info']['currency']);

      $shipment = new \Pakettikauppa\Shipment();
      $shipment->setShippingMethod($params['shipment']['method']);
      $shipment->setSender($sender);
      $shipment->setReceiver($receiver);
      $shipment->setShipmentInfo($info);

      $total_parcels = (! empty($params['additional_services']['3102']['count'])) ? $params['additional_services']['3102']['count'] : 1;
      for ($i = 1; $i <= $total_parcels; $i++) {
        $weight = (float)$params['parcel']['weight'] / (int)$total_parcels;

        $parcel = new \Pakettikauppa\Shipment\Parcel();
        $parcel->setReference($params['parcel']['reference']);
        $parcel->setWeight($weight);
        $parcel->setContents($params['parcel']['contents']);
        $shipment->addParcel($parcel);
      }
    
      foreach ($params['additional_services'] as $service_key => $service_params) {
        $additional_service = new \Pakettikauppa\Shipment\AdditionalService();
        $additional_service->setServiceCode($service_key);
        foreach ($service_params as $service_param_key => $service_value) {
          $additional_service->addSpecifier($service_param_key, $service_value);
        }
        $shipment->addAdditionalService($additional_service);
      }

      try {
        if ($client->createTrackingCode($shipment)) {
          $tracking_info = $shipment->getTrackingCode();
          $tracking_code = (string)$tracking_info;
          $tracking_url = (string)$tracking_info->attributes()->tracking_url;

          if ($client->fetchShippingLabel($shipment)) {
            $shipping_label = base64_decode($shipment->getPdf());
          }
        } else {
          return array('status' => 'error', 'msg' => $this->trans['error_failed_get_tracking']);
        }
      } catch (\Exception $ex) {
        return array('status' => 'error', 'msg' => $ex->getMessage());
      }

      return array('status' => 'success', 'tracking_code' => $tracking_code, 'tracking_url' => $tracking_url, 'shipping_label' => $shipping_label);
    }

    public function get_label_pdf($tracking_codes)
    {
      if (!is_array($tracking_codes)) {
        $tracking_codes = array($tracking_codes);
      }

      $client = $this->core->api->load_client();

      $contents = $client->fetchShippingLabels($tracking_codes);
      if ( ! $contents ) {
        return array('status' => 'error', 'msg' => $this->trans['error_label_pdf_empty']);
      }

      $shipping_label = base64_decode($contents->{'response.file'});

      return array('status' => 'success', 'tracking_code' => $tracking_codes[0], 'tracking_url' => false, 'shipping_label' => $shipping_label);
    }

    public function save_tracking_number_to_db($id_cart, $tracking_number)
    {
      $this->core->sql->update_row(array(
        'table' => 'orders',
        'update' => array(
          'track_number' => $tracking_number,
        ),
        'where' => array(
          'id_cart' => $id_cart,
        ),
      ));
    }

    public function get_tracking_number_from_db($id_cart)
    {
      $result = $this->core->sql->get_single_row(array(
        'table' => 'orders',
        'get_values' => array('tracking_number' => 'track_number'),
        'where' => array(
          'id_cart' => $id_cart,
        ),
      ));

      return (!empty($result['tracking_number'])) ? $result['tracking_number'] : false;
    }

    public function set_tracking_number_to_order($id_order, $tracking_number)
    {
      $this->core->sql->update_row(array(
        'table' => _DB_PREFIX_ . 'order_carrier',
        'update' => array(
          'tracking_number' => $tracking_number,
        ),
        'where' => array(
          'id_order' => $id_order,
        ),
      ));
    }

    private function prepare_shipment_params($params)
    {
      $required_params = array(
        'sender' => array('name1', 'addr1', 'postcode', 'city', 'phone', 'country'),
        'receiver' => array('name1', 'addr1', 'postcode', 'city', 'country', 'email', 'phone'),
        'info' => array('reference', 'currency'),
        'additional_services' => array(),
        'parcel' => array('reference', 'weight', 'contents'),
        'shipment' => array('method'),
      );

      foreach ($required_params as $section => $section_keys) {
        if (empty($section_keys) && !isset($params[$section])) {
          $params[$section] = array();
        }
        foreach ($section_keys as $key) {
          $params[$section][$key] = $this->get_shipment_param($params, $section, $key);
        }
      }

      return $params;
    }

    private function get_shipment_param($params, $section, $item)
    {
      if (isset($params[$section][$item])) {
        return $params[$section][$item];
      }

      return '';
    }

    private function change_order_state($order_id, $status_id)
    {
      if (empty($order_id) || empty($status_id)) {
        return;
      }

      if (\Configuration::get('PAKETTIKAUPPA_USE_CUSTOM_STATES') != '1') {
        return;
      }

      $order = new \Order((int) $order_id);

      if ($order->current_state != $status_id) {
        $history = new \OrderHistory();
        $history->id_order = (int) $order_id;
        $history->id_employee = (int) \Context::getContext()->employee->id;
        $history->changeIdOrderState((int) $status_id, $order_id);
        $history->add();
      }
    }
  }
}
