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

require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Client.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment/Sender.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment/Receiver.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment/AdditionalService.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment/Info.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment/Parcel.php');



class AdminPakettikauppaController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'pakettikauppa';
        $this->allow_export = true;
        $this->_defaultOrderBy = 'id_pakettikauppa';
        $this->_defaultOrderWay = 'DESC';
        $this->list_no_link = true;


        $this->context = Context::getContext();
        date_default_timezone_set("Asia/Calcutta");
        $this->_select = "o.id_order,concat(c.firstname,' ',c.lastname) as customer_name,ROUND(o.total_paid,2) as total,a.id_track,a.id_cart as PDF";

        $this->_join = 'inner join ' . _DB_PREFIX_ . 'orders o on a.id_cart= o.id_cart inner join ' . _DB_PREFIX_ . 'customer c on o.id_customer=c.id_customer';

        $this->fields_list = array(


            'id_order' => array(
                'title' => $this->l('Order ID'),
                'width' => 'auto',
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
                'width' => 'auto',
                'type' => 'text',
                'search' => true,
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
            'PDF' => array(
                'title' => $this->l('PDF'),
                'width' => 'auto',
                'type' => 'text',
                'callback' => 'printPDFIcons',
                'search' => true,
                'align' => 'center',
                'havingFilter' => true
            ),

        );

        parent::__construct();

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
        $id_order = DB::getInstance()->ExecuteS("Select id_order from " . _DB_PREFIX_ . "orders where id_cart=" . Tools::getValue('id_cart'));
        if (Tools::getValue('submitAction') == 'generateShippingSlipPDF') {
            $order = new Order((int)$id_order[0]['id_order']);
            if (!Validate::isLoadedObject($order)) {
                throw new PrestaShopException('Can\'t load Order object');
            }
            $order_invoice_collection = $order->getInvoicesCollection();

            if (Configuration::get('PAKETTIKAUPPA_COUNTRY') == 1) {
                $client = new \Pakettikauppa\Client(array('test_mode' => true));
            } else {
                $client = new \Pakettikauppa\Client(array('api_key' => Configuration::get('PAKETTIKAUPPA_API_KEY'), 'api_secret' => Configuration::get('PAKETTIKAUPPA_SECRET')));
            }

            $sender = new Sender();
            $sender->setName1(Configuration::get('PAKETTIKAUPPA_STORE_NAME'));
            $sender->setAddr1(Configuration::get('PAKETTIKAUPPA_STORE_ADDRESS'));
            $sender->setPostcode(Configuration::get('PAKETTIKAUPPA_POSTCODE'));
            $sender->setCity(Configuration::get('PAKETTIKAUPPA_CITY'));
            $sender->setPhone(Configuration::get('PAKETTIKAUPPA_PHONE'));
            $sender->setCountry(Configuration::get('PAKETTIKAUPPA_COUNTRY'));


            $receiver = new Receiver();
            $address = new Address($params["cart"]->id_address_delivery);
            $customer_email = new Customer($params["cart"]->id_customer);
            $receiver->setName1($address->firstname . " " . $address->lastname);
            $receiver->setAddr1($address->address1 . " " . $address->address2);
            $receiver->setPostcode($address->postcode);
            $receiver->setCity($address->city);
            $receiver->setCountry(DB::getInstance()->ExecuteS('select iso_code from ' . _DB_PREFIX_ . 'country where id_country=' . $address->id_country)[0]['iso_code']);
            $receiver->setEmail($customer_email->email);
            $receiver->setPhone($address->phone);


            $total_weight = DB::getInstance()->ExecuteS('SELECT o.reference,sum(od.product_weight) as weight FROM `ps_order_detail` od left join ps_orders o on od.id_order=o.id_order WHERE o.id_order=' . $params['id_order']);

            $info = new Info();
            $info->setReference('12344');

            $ship_detail = DB::getInstance()->ExecuteS('SELECT p.`id_pickup_point`,p.`shipping_method_code`,substring_index(substring_index(c.name, "[", -1),"]", 1) as code FROM `' . _DB_PREFIX_ . 'pakettikauppa` p left join ' . _DB_PREFIX_ . 'carrier c on p.`shipping_method_code`=c.id_carrier WHERE `id_cart`=' . $params["cart"]->id);

            $additional_service = new AdditionalService();
            $additional_service->addSpecifier('pickup_point_id', $ship_detail[0]['id_pickup_point']);

            $parcel = new Parcel();
            $parcel->setReference($total_weight[0]['reference']);
            $parcel->setWeight($total_weight[0]['weight']); // kg
            $parcel->setContents('Stuff and thingies');

            $shipment = new Shipment();
            $shipment->setShippingMethod($ship_detail[0]['code']); // shipping_method_code that you can get by using listShippingMethods()
            $shipment->setSender($sender);
            $shipment->setReceiver($receiver);
            $shipment->setShipmentInfo($info);
            $shipment->addParcel($parcel);
            $shipment->addAdditionalService($additional_service);

            if (Configuration::get('PAKETTIKAUPPA_COUNTRY') == 1) {
                $client = new \Pakettikauppa\Client(array('test_mode' => true));
            } else {
                $client = new \Pakettikauppa\Client(array('api_key' => Configuration::get('PAKETTIKAUPPA_API_KEY'), 'api_secret' => Configuration::get('PAKETTIKAUPPA_SECRET')));
            }

            try {
                if ($client->createTrackingCode($shipment)) {
                    DB::getInstance()->Execute('update ' . _DB_PREFIX_ . 'pakettikauppa set id_track="' . $shipment->getTrackingCode() . '" where id_cart=' . $params["cart"]->id);
                    if ($client->fetchShippingLabel($shipment)) {
                        // TODO set the headers
                        echo base64_decode($shipment->getPdf());
                    }
                }
            } catch (Exception $ex) {
                //echo $ex->getMessage();
            }
        }
    }

    public function setMedia()
    {
        parent::setMedia(); // JS files

        Tools::addJS(_PS_MODULE_DIR_ . 'purchaseorder/views/js/back.js');

    }


    public function printPDFIcons($order, $tr)
    {
        $this->context->smarty->assign(array(
            'order' => $order,
            'tr' => $tr
        ));
        return $this->context->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/admin/_print_pdf_icon_pakettikauppa.tpl');
    }
}


