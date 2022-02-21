<?php
/**
 * 2017-2018 Pakettikauppa
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    Pakettikauppa <asiakaspalvelu@pakettikauppa.fi>
 * @copyright 2017- Pakettikauppa Oy
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 *
 */

include_once(dirname(__FILE__) . '/../../init.php');

class AdminPakettikauppaController extends ModuleAdminController
{
    protected $core;
    protected $empty_value = '—';

    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'pakettikauppa';
        $this->allow_export = true;
        $this->_defaultOrderBy = 'id';
        $this->_defaultOrderWay = 'DESC';
        $this->list_no_link = true;
        $this->addRowAction('Pdf');
        $this->identifier = 'id_cart';

        parent::__construct();

        $this->context = Context::getContext();
        date_default_timezone_set("Asia/Calcutta");

        $this->core = new PS_Pakettikauppa();

        $this->_select = "o.id_order,
            concat(c.firstname,' ',c.lastname) as customer_name,
            ROUND(o.total_paid,2) as total,
            a.track_number as id_track,
            a.id_cart as pickup_point,
            a.id_carrier as id_carrier";
        $this->_join = 'inner join ' . _DB_PREFIX_ . 'orders o on a.id_cart= o.id_cart inner join ' . _DB_PREFIX_ . 'customer c on o.id_customer=c.id_customer';
        $this->_orderBy = 'id_order';
        $this->_orderWay = 'DESC';

        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->l('Order ID'),
                'class' => 'fixed-width-xs',
                'type' => 'text',
                'search' => true,
                'align' => 'center',
                'havingFilter' => true
            ),
            'customer_name' => array(
                'title' => $this->l('Customer'),
                'width' => 'auto',
                'type' => 'text',
                'search' => true,
                'align' => 'center',
                'havingFilter' => true
            ),
            'total' => array(
                'title' => $this->l('Total Amount'),
                'class' => 'fixed-width-xs',
                'type' => 'text',
                'search' => false,
                'align' => 'center',
                'havingFilter' => true
            ),
            'id_carrier' => array(
                'title' => $this->l('Carrier'),
                'width' => 'auto',
                'type' => 'text',
                'callback' => 'getCarrierName',
                'search' => false, //Disabled, because search with callback not working
                'align' => 'center',
                'havingFilter' => true
            ),
            'pickup_point' => array(
                'title' => $this->l('Pickup point'),
                'width' => 'auto',
                'type' => 'text',
                'callback' => 'getPickupName',
                'search' => false, //Disabled, because search with callback not working
                'align' => 'center',
                'havingFilter' => true
            ),
            'id_track' => array(
                'title' => $this->l('Shipping Track ID'),
                'width' => 'auto',
                'type' => 'text',
                'search' => true,
                'align' => 'center',
                'havingFilter' => true
            ),
        );
    }

    public function initToolbar()
    {
        parent::initToolbar();

        unset($this->toolbar_btn['new']);
    }

    public function init()
    {
        parent::init();
        $this->bootstrap = true;

        if (Tools::getValue('submitAction') == 'generateShippingSlipPDF') {
            $id_order = DB::getInstance()->ExecuteS("Select id_order from " . _DB_PREFIX_ . "orders where id_cart=" . Tools::getValue('id_cart'));
            $order = new Order((int)$id_order[0]['id_order']);
            if (!Validate::isLoadedObject($order)) {
                throw new PrestaShopException('Can\'t load Order object');
            }
            $order_invoice_collection = $order->getInvoicesCollection();

            $client = $this->core->api->load_client();

            if (empty(Configuration::get('PAKETTIKAUPPA_POSTCODE'))) {
                die($this->l('Sender postcode is required'));
            }

            $sender = new \Pakettikauppa\Shipment\Sender();
            $sender->setName1(Configuration::get('PAKETTIKAUPPA_STORE_NAME'));
            $sender->setAddr1(Configuration::get('PAKETTIKAUPPA_STORE_ADDRESS'));
            $sender->setPostcode(Configuration::get('PAKETTIKAUPPA_POSTCODE'));
            $sender->setCity(Configuration::get('PAKETTIKAUPPA_CITY'));
            $sender->setPhone(Configuration::get('PAKETTIKAUPPA_PHONE'));
            $sender->setCountry(Configuration::get('PAKETTIKAUPPA_COUNTRY'));


            $receiver = new \Pakettikauppa\Shipment\Receiver();
            $address = new Address($order->id_address_delivery);
            $customer_data = new Customer($order->id_customer);
            $receiver->setName1($address->firstname . " " . $address->lastname);
            $receiver->setAddr1($address->address1 . " " . $address->address2);
            $receiver->setPostcode($address->postcode);
            $receiver->setCity($address->city);
            $receiver->setCountry(DB::getInstance()->ExecuteS('select iso_code from ' . _DB_PREFIX_ . 'country where id_country=' . $address->id_country)[0]['iso_code']);
            $receiver->setEmail($customer_data->email);
            $receiver->setPhone($address->phone);


            $total_weight = DB::getInstance()->ExecuteS('SELECT o.reference,sum(od.product_weight) as weight FROM `ps_order_detail` od left join ps_orders o on od.id_order=o.id_order WHERE o.id_order=' . $order->id);

            $info = new \Pakettikauppa\Shipment\Info();
            $info->setReference($order->id);
            //$info->setReference($order->reference); //Or reference
            $currency = new CurrencyCore($order->id_currency);
            $info->setCurrency($currency->iso_code);

            $ship_detail = DB::getInstance()->ExecuteS('SELECT p.`pickup_point_id`,p.`id_carrier`,substring_index(substring_index(c.name, "[", -1),"]", 1) as code FROM `' . _DB_PREFIX_ . 'pakettikauppa` p left join ' . _DB_PREFIX_ . 'carrier c on p.`id_carrier`=c.id_carrier WHERE `id_cart`=' . $order->id_cart);

            $additional_service = new \Pakettikauppa\Shipment\AdditionalService();
            if (!empty($ship_detail[0]['pickup_point_id'])) {
                $additional_service->addSpecifier('pickup_point_id', $ship_detail[0]['pickup_point_id']);
                $additional_service->setServiceCode('2106');
            }

            $parcel = new \Pakettikauppa\Shipment\Parcel();
            $parcel->setReference($total_weight[0]['reference']);
            $parcel->setWeight($total_weight[0]['weight']); // kg
            //$parcel->setContents('Stuff and thingies'); //TODO: Make comment

            $shipment = new \Pakettikauppa\Shipment();
            $shipment->setShippingMethod($ship_detail[0]['code']); // shipping_method_code that you can get by using listShippingMethods()
            $shipment->setSender($sender);
            $shipment->setReceiver($receiver);
            $shipment->setShipmentInfo($info);
            $shipment->addParcel($parcel);
            $shipment->addAdditionalService($additional_service);

            try {
                if ($client->createTrackingCode($shipment)) {
                    $tracking_code = $shipment->getTrackingCode();
                    DB::getInstance()->Execute('update ' . _DB_PREFIX_ . 'pakettikauppa set track_number="' . $shipment->getTrackingCode() . '" where id_cart=' . $order->id_cart);
                    if ($client->fetchShippingLabel($shipment)) {
                        $pdf = base64_decode($shipment->getPdf());
                        $content_disposition = 'inline';
                        $filename = $tracking_code;
                        
                        header('Content-Type: application/pdf');
                        header('Content-Description: File Transfer');
                        header('Content-Transfer-Encoding: binary');
                        header("Content-Disposition: $content_disposition;filename=\"{$filename}.pdf\"");
                        header('Content-Length: ' . strlen($pdf));
                        
                        die($pdf);
                    }
                }
            } catch (Exception $ex) {
                die($ex->getMessage());
            }
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia(); // JS files

        //$this->context->controller->addJS(_PS_MODULE_DIR_ . 'pakettikauppa/views/js/back.js');
    }

    public function getCarrierName($id_carrier, $tr)
    {
        $carrier = new Carrier($id_carrier);

        return (!empty($carrier->name)) ? $carrier->name : $this->empty_value;
    }

    public function getPickupName($id_cart, $tr)
    {
        $method = $this->core->sql->get_single_row(array(
            'table' => 'main',
            'where' => array(
                'id_cart' => $id_cart
            ),
        ));

        if (empty($method)) {
            return $this->empty_value;
        }

        $pickup_point = json_decode($this->core->api->get_pickup_info($method['pickup_point_id'], $method['method_code']));
        if (empty($pickup_point->name)) {
            return $this->empty_value;
        }

        return $pickup_point->name . '<br/><small>' . $pickup_point->street_address . ', ' . $pickup_point->city . ', ' . $pickup_point->postcode . ' ' . $pickup_point->country . '</small>';
    }

    public function displayPdfLink($token, $cart_id)
    {
        $this->context->smarty->assign(array(
            'order' => $cart_id,
        ));

        return $this->context->smarty->fetch($this->core->configs->module_dir . '/views/templates/admin/_print_pdf_icon_pakettikauppa.tpl');
    }
}


