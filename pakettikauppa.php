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

if (!defined('_PS_VERSION_')) {
    exit;
}
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Client.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment/Sender.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment/Receiver.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment/AdditionalService.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment/Info.php');
require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Shipment/Parcel.php');

class Pakettikauppa extends CarrierModule
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'pakettikauppa';
        $this->tab = 'shipping_logistics';
        $this->version = '1.0.0';
        $this->author = 'Pakettikauppa';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Pakettikauppa Shipping Module');
        $this->description = $this->l('Pakettikauppa Shipping Module provide you best shipping service in your country');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        if (extension_loaded('curl') == false) {
            $this->_errors[] = $this->l('You have to enable the cURL extension on your server to install this module');
            return false;
        }

        include(dirname(__FILE__) . '/sql/install.php');

        Configuration::updateValue('PAKETTIKAUPPA_API_KEY', '00000000-0000-0000-0000-000000000000');
        Configuration::updateValue('PAKETTIKAUPPA_SECRET', '1234567890ABCDEF');
        Configuration::updateValue('PAKETTIKAUPPA_BASE_URI', 'https://apitest.pakettikauppa.fi');
        Configuration::updateValue('PAKETTIKAUPPA_MODE', '1');

        Configuration::updateValue('PAKETTIKAUPPA_LIVE_MODE', false);
        Configuration::updateValue('PAKETTIKAUPPA_SHIPPING_STATE', NULL);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('updateCarrier') && $this->registerHook('actionValidateOrder') && $this->registerHook('actionOrderStatusUpdate') && $this->installModuleTab() &&
            $this->registerHook('displayCarrierList');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PAKETTIKAUPPA_LIVE_MODE');
        Configuration::deleteByName('PAKETTIKAUPPA_SHIPPING_STATE');

        $carr = DB::getInstance()->ExecuteS("Select id_carrier from " . _DB_PREFIX_ . "carrier where external_module_name='" . $this->name . "'");

        foreach ($carr as $carrier) {
            $delete_carrier = new Carrier($carrier['id_carrier']);
            $delete_carrier->delete();
        }
        $this->uninstallModuleTab();


        return parent::uninstall();
    }


    /*
    Install module Tab
    */
    public function installModuleTab()
    {
        $tab = new Tab;
        $langs = language::getLanguages();
        foreach ($langs as $lang)
            $tab->name[$lang['id_lang']] = 'Pakettikauppa';
        $tab->module = $this->name;
        $tab->id_parent = 0;
        $tab->class_name = 'AdminPakettikauppa';
        return $tab->save();

    }

    /**
     * Uninstall Module Tab
     */
    public function uninstallModuleTab()
    {
        $id_tab = Tab::getIdFromClassName('AdminPakettikauppa');
        if ($id_tab) {
            $tab = new Tab($id_tab);
            return $tab->delete();
        }
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitPakettikauppaModule')) == true) {
            $this->postProcess();
        }

        if (((bool)Tools::isSubmit('submitPakettikauppaShippingState')) == true) {
            Configuration::updateValue('PAKETTIKAUPPA_SHIPPING_STATE', Tools::getValue('shipping_state'));
        }
        if (((bool)Tools::isSubmit('submitPakettikauppaSender')) == true) {
            Configuration::updateValue('PAKETTIKAUPPA_STORE_NAME', Tools::getValue('store_name'));
            Configuration::updateValue('PAKETTIKAUPPA_STORE_ADDRESS', Tools::getValue('address'));
            Configuration::updateValue('PAKETTIKAUPPA_POSTCODE', Tools::getValue('postcode'));
            Configuration::updateValue('PAKETTIKAUPPA_CITY', Tools::getValue('city'));
            Configuration::updateValue('PAKETTIKAUPPA_PHONE', Tools::getValue('phone'));
            Configuration::updateValue('PAKETTIKAUPPA_COUNTRY', Tools::getValue('country'));
            Configuration::updateValue('PAKETTIKAUPPA_VATCODE', Tools::getValue('vat_code'));
        }

        if (((bool)Tools::isSubmit('submitPakettikauppaAPI')) == true) {
            Configuration::updateValue('PAKETTIKAUPPA_API_KEY', Tools::getValue('api_key'));
            Configuration::updateValue('PAKETTIKAUPPA_SECRET', Tools::getValue('secret'));
            Configuration::updateValue('PAKETTIKAUPPA_MODE', Tools::getValue('modes'));

            $client = new \Pakettikauppa\Client(array('test_mode' => (Tools::getValue('modes') == 1)));
            $result = $client->listShippingMethods();
            $shipping_methods = json_decode($result);
            foreach ($shipping_methods as $shipping_method) {
                $carrier = $this->addCarrier($shipping_method->name, $shipping_method->shipping_method_code);
                $this->addZones($carrier);
                $this->addGroups($carrier);
                $this->addRanges($carrier);
            }
        }

        $this->context->smarty->assign('module_dir', $this->_path);

        /*
        $warehouse = DB::getInstance()->ExecuteS("select w.id_warehouse, CONCAT(reference, ' - ', name) as name from " . _DB_PREFIX_ . "warehouse w inner join " . _DB_PREFIX_ . "warehouse_shop ws on ws.id_warehouse = w.id_warehouse AND ws.id_shop=" . $this->context->shop->id . " where w.deleted=0 order by w.id_warehouse ASC");

        $selected_carriers = DB::getInstance()->ExecuteS('SELECT wc.`id_carrier`,c.name FROM `' . _DB_PREFIX_ . 'warehouse_carrier` wc inner join ' . _DB_PREFIX_ . 'carrier c on wc.`id_carrier`=c.`id_carrier` WHERE wc.`id_warehouse`=' . $warehouse[0]['id_warehouse']);
*/
        $warehouse = array();
        $selected_carriers = array();

        $carriers = DB::getInstance()->ExecuteS("SELECT `id_reference`,`name` FROM `" . _DB_PREFIX_ . "carrier` WHERE is_module=1 and `external_module_name`='pakettikauppa' and deleted=0");

        $this->context->smarty->assign(array(
            'warehouses' => $warehouse,
            'selected_carriers' => $selected_carriers,
            'carriers' => $carriers,
            'order_statuses' => OrderState::getOrderStates((int)Context::getContext()->language->id),
            'token' => Tools::getValue('token'),
            'shipping_state' => Configuration::get('PAKETTIKAUPPA_SHIPPING_STATE'),
            'countries' => Country::getCountries($this->context->language->id)
        ));
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $this->show_msg() . $output;//.$this->renderForm();
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitPakettikauppaModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        $this->fields_form = array('form' => array(
            'legend' => array(
                'title' => $this->l('Settings'),
                'icon' => 'icon-cogs',
            ),
            'input' => array(
                array(
                    'type' => 'select',
                    'label' => $this->l('Select Warehouse'),
                    'name' => 'id_warehouse',
                    'desc' => $this->l('Select Warehouse to assign Pakettikauppa Carriers'),
                    'options' => array(
                        'query' => $warehouse,
                        'id' => 'id_warehouse',
                        'name' => 'name'
                    )
                ),
                array(
                    'type' => 'swap',
                    'label' => $this->l('Carriers'),
                    'name' => 'ids_carriers',
                    'required' => false,
                    'multiple' => true,
                    'options' => array(
                        'query' => Carrier::getCarriers($this->context->language->id, false, false, false, null, Carrier::CARRIERS_MODULE),
                        'id' => 'id_reference',
                        'name' => 'name'
                    ),
                    'hint' => array(
                        $this->l('Associated carriers.'),
                        $this->l('You can choose which carriers can ship orders from particular warehouses.'),
                        $this->l('If you do not select any carrier, all the carriers will be able to ship from this warehouse.'),
                    ),
                    'desc' => $this->l('If no carrier is selected, all the carriers will be allowed to ship from this warehouse. Use CTRL+Click to select more than one carrier.'),
                ),
            ),
            'submit' => array(
                'title' => $this->l('Save'),
            ),
        )
        );

        return $this->fields_form;
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'PAKETTIKAUPPA_LIVE_MODE' => Configuration::get('PAKETTIKAUPPA_LIVE_MODE', true),
            'PAKETTIKAUPPA_ACCOUNT_EMAIL' => Configuration::get('PAKETTIKAUPPA_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'PAKETTIKAUPPA_ACCOUNT_PASSWORD' => Configuration::get('PAKETTIKAUPPA_ACCOUNT_PASSWORD', null),
            'shipping_state' => Configuration::get('PAKETTIKAUPPA_SHIPPING_STATE'),
            'PAKETTIKAUPPA_API_KEY' => Configuration::get('PAKETTIKAUPPA_API_KEY'),
            'PAKETTIKAUPPA_SECRET' => Configuration::get('PAKETTIKAUPPA_SECRET'),
            'PAKETTIKAUPPA_BASE_URI' => Configuration::get('PAKETTIKAUPPA_BASE_URI')

        );
    }

    protected function show_msg()
    {
        if (isset($this->context->cookie->success_msg)) {
            $msg = $this->context->cookie->success_msg;
            // delete old messages
            unset($this->context->cookie->success_msg);
            return $msg;
        } elseif (isset($this->context->cookie->error_msg)) {
            $msg = $this->context->cookie->error_msg;
            // delete old messages
            unset($this->context->cookie->error_msg);
            return $msg;
        }
    }


    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $set_carrier_to_warehouse = new Warehouse(Tools::getValue('id_warehouse'));
        $set_carrier_to_warehouse->setCarriers(Tools::getValue('ids_carriers_selected'));
        $this->context->cookie->__set('success_msg', $this->displayConfirmation($this->l('Save successfully.')));
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'));

        /*
        $form_values = $this->getConfigFormValues();
       
        
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
        */
    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        if (Context::getContext()->customer->logged == true) {
            $id_address_delivery = Context::getContext()->cart->id_address_delivery;
            $address = new Address($id_address_delivery);

            /**
             * Send the details through the API
             * Return the price sent by the API
             */
            return 10;
        }

        return $shipping_cost;
    }

    public function getOrderShippingCostExternal($params)
    {
        return true;
    }

    protected function addCarrier($name, $code)
    {
        $carrier = new Carrier();

        $carrier->name = $this->l($name . " [" . $code . "]");
        $carrier->is_module = true;
        $carrier->active = 1;
        $carrier->range_behavior = 1;
        $carrier->need_range = 1;
        $carrier->shipping_external = true;
        $carrier->range_behavior = 0;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_method = 2;

        foreach (Language::getLanguages() as $lang)
            $carrier->delay[$lang['id_lang']] = $this->l('Super fast delivery');

        if ($carrier->add() == true) {
            @copy(dirname(__FILE__) . '/views/img/carrier_image.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int)$carrier->id . '.jpg');
            Configuration::updateValue('MYSHIPPINGMODULE_CARRIER_ID', (int)$carrier->id);
            return $carrier;
        }

        return false;
    }

    protected function addGroups($carrier)
    {
        $groups_ids = array();
        $groups = Group::getGroups(Context::getContext()->language->id);
        foreach ($groups as $group)
            $groups_ids[] = $group['id_group'];

        $carrier->setGroups($groups_ids);
    }

    protected function addRanges($carrier)
    {
        $range_price = new RangePrice();
        $range_price->id_carrier = $carrier->id;
        $range_price->delimiter1 = '0';
        $range_price->delimiter2 = '10000';
        $range_price->add();

        $range_weight = new RangeWeight();
        $range_weight->id_carrier = $carrier->id;
        $range_weight->delimiter1 = '0';
        $range_weight->delimiter2 = '10000';
        $range_weight->add();
    }

    protected function addZones($carrier)
    {
        $zones = Zone::getZones();

        foreach ($zones as $zone)
            $carrier->addZone($zone['id_zone']);
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        if (Tools::getValue('module_name') == $this->name) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookUpdateCarrier($params)
    {

        /**
         * Not needed since 1.5
         * You can identify the carrier by the id_reference
         */
    }


    public function hookDisplayCarrierList($params)
    {


        $display = "none";
        $client = new Client(array('test_mode' => true));
        //$result = $client->searchPickupPoints($params['address']->postcode);
        $result = $client->searchPickupPoints('00100');
        $methods = $client->listShippingMethods();
        $result = json_decode($result);
        $methods = json_decode($methods);
        $method_id_list = array();
        foreach ($methods as $method) {
            $method_id_list[] = $method->shipping_method_code;
        }
        $ship_detail = DB::getInstance()->ExecuteS('SELECT substring_index(substring_index(name, "[", -1),"]", 1) as code FROM `' . _DB_PREFIX_ . 'carrier` where id_carrier=' . $params['cart']->id_carrier);

        if (in_array($ship_detail[0]['code'], $method_id_list)) {
            $display = "block";
        }


        DB::getInstance()->Execute('INSERT INTO ' . _DB_PREFIX_ . 'pakettikauppa (id_cart,shipping_method_code) VALUES(' . $params['cart']->id . ',' . $params['cart']->id_carrier . ') ON DUPLICATE KEY UPDATE shipping_method_code=' . $params['cart']->id_carrier);
        if (count($result) != 0) {

            DB::getInstance()->Execute('update ' . _DB_PREFIX_ . 'pakettikauppa set id_pickup_point=' . $result[0]->pickup_point_id . ' where id_cart=' . $params['cart']->id);
        }

        $this->context->smarty->assign(array('pick_up_points' => $result, 'module_dir' => $this->_path, 'id_cart' => $params['cart']->id, 'display' => $display));
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/front/carrier_list.tpl');
        return $output;
    }

    public function hookActionValidateOrder($params)
    {
    }

    public function hookActionOrderStatusUpdate($params)
    {

        if ($params["newOrderStatus"]->id == Configuration::get('PAKETTIKAUPPA_SHIPPING_STATE')) {
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

            $client = new Client(array('test_mode' => true));

            try {
                if ($client->createTrackingCode($shipment)) {
                    if ($client->fetchShippingLabel($shipment))
                        file_put_contents($shipment->getTrackingCode() . '.pdf', base64_decode($shipment->getPdf()));
                    DB::getInstance()->Execute('update ' . _DB_PREFIX_ . 'pakettikauppa set id_track="' . $shipment->getTrackingCode() . '" where id_cart=' . $params["cart"]->id);
                }
            } catch (Exception $ex) {
                //echo $ex->getMessage();
            }
        }
    }
}
