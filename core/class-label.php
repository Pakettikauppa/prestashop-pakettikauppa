<?php
namespace PS_Pakettikauppa_Core;

if (!defined('_PS_VERSION_')) {
  exit;
}

if ( ! class_exists(__NAMESPACE__ . '\Label') ) {
  class Label
  {
    public $core = null;
    public $trans = array();

    public function __construct(Core $module)
    {
      $this->core = $module;
      $this->trans = $this->core->configs->translates;
    }

    public function generate_shipment($id_order)
    {
      $order = new \Order((int)$id_order);

      if (!\Validate::isLoadedObject($order)) {
        return array('status' => 'error', 'msg' => $this->trans['error_order_object']);
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

      $ship_detail = $this->core->sql->get_single_row(array(
        'table' => 'main',
        'get_values' => array(
          'point' => 'pickup_point_id',
          'method' => 'method_code',
          'carrier' => 'id_carrier',
        ),
        'where' => array(
          'id_cart' => $order->id_cart,
        ),
      ));

      $additional_services = array();
      if (!empty($ship_detail['point'])) {
        $additional_services['2106']['pickup_point_id'] = $ship_detail['point'];
      }
      
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

      $shipment = $this->register_shipment($params);

      if (empty($shipment['tracking_code'])) {
        return array('status' => 'error', 'msg' => $this->trans['error_tracking_empty']);
      }
      $this->save_tracking_number_to_db($order->id_cart, $shipment['tracking_code']);

      return $shipment;
    }

    public function generate_PDF($id_order)
    {
      $shipment = $this->generate_shipment($id_order);

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

      $additional_service = new \Pakettikauppa\Shipment\AdditionalService();
      foreach ($params['additional_services'] as $service_key => $service_params) {
        $additional_service->setServiceCode($service_key);
        foreach ($service_params as $service_param_key => $service_value) {
          $additional_service->addSpecifier($service_param_key, $service_value);
        }
      }

      $parcel = new \Pakettikauppa\Shipment\Parcel();
      $parcel->setReference($params['parcel']['reference']);
      $parcel->setWeight($params['parcel']['weight']);
      $parcel->setContents($params['parcel']['contents']);

      $shipment = new \Pakettikauppa\Shipment();
      $shipment->setShippingMethod($params['shipment']['method']);
      $shipment->setSender($sender);
      $shipment->setReceiver($receiver);
      $shipment->setShipmentInfo($info);
      $shipment->addParcel($parcel);
      $shipment->addAdditionalService($additional_service);

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

    public function save_tracking_number_to_db($id_cart, $tracking_number)
    {
      $this->core->sql->update_row(array(
        'table' => 'main',
        'update' => array(
          'track_number' => $tracking_number,
        ),
        'where' => array(
          'id_cart' => $id_cart,
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
  }
}
