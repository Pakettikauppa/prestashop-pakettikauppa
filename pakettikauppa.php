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

include_once(dirname(__FILE__) . '/init.php');

class Pakettikauppa extends CarrierModule
{
    protected $config_form = false;
    protected $core;

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

        $this->core = new PS_Pakettikauppa();
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

        $this->core->sql->install();

        if (empty(Configuration::get('PAKETTIKAUPPA_API_KEY'))) {
            Configuration::updateValue('PAKETTIKAUPPA_API_KEY', '00000000-0000-0000-0000-000000000000');
            Configuration::updateValue('PAKETTIKAUPPA_SECRET', '1234567890ABCDEF');
            Configuration::updateValue('PAKETTIKAUPPA_MODE', '1');
        }

        Configuration::updateValue('PAKETTIKAUPPA_LIVE_MODE', false);
        Configuration::updateValue('PAKETTIKAUPPA_SHIPPING_STATE', NULL);

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('updateCarrier') && $this->registerHook('actionValidateOrder') && $this->registerHook('actionOrderStatusUpdate') && $this->installModuleTab() &&
            $this->registerHook('displayCarrierList') && $this->registerHook('displayCarrierExtraContent');
    }

    public function uninstall()
    {
        Configuration::deleteByName('PAKETTIKAUPPA_LIVE_MODE');
        Configuration::deleteByName('PAKETTIKAUPPA_SHIPPING_STATE');

        $this->delete_carriers();
        $this->uninstallModuleTab();

        $this->core->sql->uninstall();

        return parent::uninstall();
    }

    private function delete_carriers()
    {
        $carriers = DB::getInstance()->ExecuteS("Select id_carrier, id_reference from " . _DB_PREFIX_ . "carrier where external_module_name='" . $this->name . "'");

        $deleted_references = array();
        foreach ($carriers as $carrier) {
            $delete_carrier = new Carrier($carrier['id_carrier']);
            $delete_carrier->delete();

            if (!in_array($carrier['id_reference'], $deleted_references)) {
                $this->core->sql->delete_row(array(
                    'table' => 'methods',
                    'where' => array(
                        'id_carrier_reference' => $carrier['id_reference'],
                    ),
                ));
                $deleted_references[] = $carrier['id_reference'];
            }
        }
    }

    /*
    Install module Tab
    */
    public function installModuleTab()
    {
        $parent_tab = Tab::getIdFromClassName('AdminParentShipping') ? Tab::getIdFromClassName('AdminParentShipping') : Tab::getIdFromClassName('Shipping');
        $tab = new Tab;
        $langs = language::getLanguages();
        foreach ($langs as $lang)
            $tab->name[$lang['id_lang']] = 'Pakettikauppa';
        $tab->module = $this->name;
        $tab->id_parent = $parent_tab;
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
        $carriers = DB::getInstance()->ExecuteS("SELECT `id_reference`,`name` FROM `" . _DB_PREFIX_ . "carrier` WHERE is_module=1 and `external_module_name`='pakettikauppa' and deleted=0");
        /**
         * If values have been submitted in the form, process.
         */
        if (((bool)Tools::isSubmit('submitPakettikauppaModule')) == true) {
            $this->postProcess();
        }

        if (((bool)Tools::isSubmit('submitPakettikauppaShippingLabels')) == true) {
            Configuration::updateValue('PAKETTIKAUPPA_SHIPPING_STATE', Tools::getValue('shipping_state'));
            Configuration::updateValue('PAKETTIKAUPPA_LABEL_COMMENT', Tools::getValue('label_comment'));
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

        if (((bool)Tools::isSubmit('submitPakettikauppaFront')) == true) {
            Configuration::updateValue('PAKETTIKAUPPA_MAX_PICKUPS', Tools::getValue('pickup_points_count'));
        }

        if (((bool)Tools::isSubmit('submitPakettikauppaAPI')) == true) {
            $old_mode = Configuration::get('PAKETTIKAUPPA_MODE');
            
            Configuration::updateValue('PAKETTIKAUPPA_API_KEY', Tools::getValue('api_key'));
            Configuration::updateValue('PAKETTIKAUPPA_SECRET', Tools::getValue('secret'));
            Configuration::updateValue('PAKETTIKAUPPA_MODE', Tools::getValue('modes'));

            if ($old_mode != Tools::getValue('modes')) {
                $this->delete_carriers();
            }

            $api_configs = array(
                'test_mode' => (Tools::getValue('modes') == 1),
                'api_key' => Tools::getValue('api_key'),
                'secret' => Tools::getValue('secret'),
            );
            $client = new \Pakettikauppa\Client($api_configs);

            $shipping_methods = $client->listShippingMethods();
            $carriers_count = 0;
            if (is_array($shipping_methods)) {
                foreach ($shipping_methods as $shipping_method) {
                    $exists = $this->core->carrier->check_if_association_exist($shipping_method->shipping_method_code);
                    if (!$exists) {
                        $carrier = $this->addCarrier($shipping_method->name, $shipping_method->shipping_method_code);
                        $this->addZones($carrier);
                        $this->addGroups($carrier);
                        $this->addRanges($carrier);
                        $carriers_count++;

                        $this->core->carrier->associate_method_with_carrier($shipping_method, $carrier->id);
                    }
                }
            } else {
                $this->context->cookie->__set('error_msg', $this->l('Failed to create carriers due to invalid list received'));
            }

            if ($carriers_count > 0) {
                if ($old_mode != Tools::getValue('modes')) {
                    $this->context->cookie->__set('success_msg', sprintf($this->l('Saved successfully, deleted old carriers and created new %s carriers'), $carriers_count));
                } else {
                    $this->context->cookie->__set('success_msg', sprintf($this->l('Saved successfully and created %s carriers'), $carriers_count));
                }
            } else {
                $this->context->cookie->__set('success_msg', $this->l('Saved successfully'));
            }
        }

        $this->context->smarty->assign('module_dir', $this->_path);
        $warehouses = array(); //TODO: Need to make
        //$warehouses = Warehouse::getWarehouses();
        $selected_carriers = array(); //TODO: Need to make

        $this->context->smarty->assign(array(
            'warehouses' => $warehouses,
            'selected_carriers' => $selected_carriers,
            'carriers' => $carriers,
            'order_statuses' => OrderState::getOrderStates((int)Context::getContext()->language->id),
            'token' => Tools::getValue('token'),
            'shipping_state' => Configuration::get('PAKETTIKAUPPA_SHIPPING_STATE'),
            'countries' => Country::getCountries($this->context->language->id),
            'label_comment_variables' => array(
                '{order_id}' => $this->l('Order ID'),
                '{order_reference}' => $this->l('Order reference'),
            ),
        ));
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $this->show_msg() . $output;
    }

    protected function show_msg()
    {
        $output = '';

        if (isset($this->context->cookie->success_msg)) {
            $msg = $this->context->cookie->success_msg;
            // delete old messages
            unset($this->context->cookie->success_msg);
            $output .= $this->displayConfirmation($msg);
        }
        if (isset($this->context->cookie->error_msg)) {
            $msg = $this->context->cookie->error_msg;
            // delete old messages
            unset($this->context->cookie->error_msg);
            $output .= $this->displayError($msg);
        }

        return $output;
    }


    /**
     * Save form data.
     */
    protected function postProcess()
    {
        $this->context->cookie->__set('success_msg', $this->l('Save successfully.'));
        Tools::redirectAdmin($this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name . '&token=' . Tools::getAdminTokenLite('AdminModules'));

    }

    public function getOrderShippingCost($params, $shipping_cost)
    {
        return $shipping_cost;
    }

    public function getOrderShippingCostExternal($params)
    {
        return false;
    }

    protected function addCarrier($name, $code)
    {
        $carrier = new Carrier();

        $carrier->name = $name . " [" . $code . "]";
        $carrier->is_module = true;
        $carrier->active = 0;
        //$carrier->range_behavior = 1;
        $carrier->need_range = 1;
        $carrier->shipping_external = true;
        $carrier->range_behavior = 0;
        $carrier->external_module_name = $this->name;
        $carrier->shipping_method = 2;
        $carrier->url = 'https://www.pakettikauppa.fi/seuranta/?';

        foreach (Language::getLanguages() as $lang)
            $carrier->delay[$lang['id_lang']] = $this->l('Super fast delivery');

        if ($carrier->add() == true) {
            @copy(dirname(__FILE__) . '/views/img/carrier_image.jpg', _PS_SHIP_IMG_DIR_ . '/' . (int)$carrier->id . '.jpg');
            Configuration::updateValue('PAKETTIKAUPPA_CARRIER_ID', (int)$carrier->id);
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
            $this->context->controller->addJquery();
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

    public function hookDisplayCarrierExtraContent($params)
    {
        return $this->hookDisplayCarrierList($params);
    }

    public function hookDisplayCarrierList($params)
    {
        $display = "none";
        $pickup_points = array();

        $client = $this->core->api->load_client();

        $address = new Address($params['cart']->id_address_delivery);
        $country_iso = Country::getIsoById($address->id_country);
        $carrier = new Carrier($params['cart']->id_carrier);

        $ship_detail = $this->core->sql->get_rows(array(
          'table' => 'methods',
          'get_values' => array(
            'code' => 'method_code',
            'has_pp' => 'has_pp',
          ),
          'where' => array(
            'id_carrier_reference' => $carrier->id_reference,
          ),
        ));
        $selected_method = (isset($ship_detail[0])) ? $ship_detail[0] : false;

        if ($selected_method === false) {
          return;
        }

        if ($selected_method['has_pp']) {
          $display = "block";
          $pickup_points = $client->searchPickupPoints($address->postcode, null, $country_iso, $selected_method['code'], 5);
        }

        $this->core->sql->insert_row(array(
            'table' => 'main',
            'values' => array(
                'id_cart' => $params['cart']->id,
                'id_carrier' => $params['cart']->id_carrier,
                'method_code' => $selected_method['code'],
            ),
            'on_duplicate' => array(
                'id_carrier' => $params['cart']->id_carrier,
                'method_code' => $selected_method['code'],
            ),
        ));
        
        $pickup_point_id = 0;
        if (count($pickup_points) > 0) {
            $pickup_point_id = $pickup_points[0]->pickup_point_id;
        }
        $this->core->sql->update_row(array(
            'table' => 'main',
            'update' => array(
                'pickup_point_id' => $pickup_point_id,
            ),
            'where' => array(
                'id_cart' => $params['cart']->id,
            ),
        ));

        $this->context->smarty->assign(array(
            'pick_up_points' => $pickup_points,
            'module_dir' => $this->_path,
            'id_cart' => $params['cart']->id,
            'display' => $display
        ));
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/front/carrier_list.tpl');
        
        return $output;
    }
}
