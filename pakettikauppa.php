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

        $this->core = new PS_Pakettikauppa(array(
          'translates' => array(
            'error_order_object' => $this->l('Cant load Order object'),
            'error_ship_not_found' => $this->l('Shipment information not found'),
          ),
        ));
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
            $this->registerHook('updateCarrier') && $this->registerHook('actionValidateOrder') && $this->registerHook('actionOrderStatusPostUpdate') && $this->installModuleTab() &&
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
        /* If values have been submitted in the form, process */
        $this->postProcess();

        /* Load template */
        $this->context->smarty->assign(array(
            'token' => Tools::getValue('token'),
            'module_url' => $this->_path,
            'template_parts_path' => $this->local_path . 'views/templates/admin/parts/configure',
            'fields' => $this->get_configuration_fields(),
            'warehouses' => Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'),
        ));
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $this->show_msg() . $output;
    }

    /**
     * Build the configuration fields
     */
    private function get_configuration_fields()
    {
        $options_countries = array();
        $countries_list = Country::getCountries($this->context->language->id);
        foreach ($countries_list as $country) {
            $options_countries[$country['iso_code']] = $country['country'];
        }

        $options_pickups_count = array();
        for ($i=1; $i<=10; $i++) {
           $options_pickups_count[$i] = $i; 
        }

        $options_cod_modules = array();
        foreach (PaymentModule::getInstalledPaymentModules() as $module) {
            $options_cod_modules[$module['id_module']] = $module['name'];
        }

        $options_order_states = array();
        $order_states = OrderState::getOrderStates($this->context->language->id);
        foreach ($order_states as $order_state) {
            $options_order_states[$order_state['id_order_state']] = $order_state['name'];
        }

        $desc_comment_on_label = $this->l('Available variables') . ':';
        $label_comment_variables = array(
            'order_id' => $this->l('Order ID'),
            'order_reference' => $this->l('Order reference'),
        );
        foreach ($label_comment_variables as $var_key => $var_desc) {
            $desc_comment_on_label .= '<span class="variable_row clickable noselect" data-for="label_comment">';
            $desc_comment_on_label .= '<code>{' . $var_key . '}</code> - ' . $var_desc;
            $desc_comment_on_label .= '</span>';
        }

        $options_warehouses = array();
        $warehouses_list = Warehouse::getWarehouses($this->context->language->id);
        foreach ($warehouses_list as $warehouse) {
            $options_warehouses[$warehouse['id_warehouse']] = $warehouse['name'];
        }

        $options_carriers = array();
        $available_carriers = DB::getInstance()->ExecuteS("SELECT `id_reference`,`name` FROM `" . _DB_PREFIX_ . "carrier` WHERE is_module=1 and `external_module_name`='pakettikauppa' and deleted=0");
        foreach ($available_carriers as $carrier) {
            $options_carriers[$carrier['id_reference']] = $carrier['name'];
        }

        $fields = array(
            'api' => array(
                array(
                    'name' => 'api_key',
                    'tpl' => 'text-simple',
                    'label' => $this->l('API key'),
                    'value' => Configuration::get('PAKETTIKAUPPA_API_KEY'),
                    'required' => true,
                ),
                array(
                    'name' => 'secret',
                    'tpl' => 'text-simple',
                    'label' => $this->l('API secret'),
                    'value' => Configuration::get('PAKETTIKAUPPA_SECRET'),
                    'required' => true,
                ),
                array(
                    'name' => 'modes',
                    'tpl' => 'select-simple',
                    'label' => $this->l('Mode'),
                    'value' => array(
                        '0' => $this->l('Production mode'),
                        '1' => $this->l('Test mode'),
                    ),
                    'selected' => Configuration::get('PAKETTIKAUPPA_MODE'),
                    'default' => '1',
                    'onchange' => "alert('" . $this->l('CAUTION! Mode change will delete all existing Pakettikauppa carriers') . "');",
                    'required' => true,
                ),
                array(
                    'tpl' => 'message',
                    'value' => $this->l('Saving the settings in this section creates the missing carriers'),
                ),
            ),
            'store' => array(
                array(
                    'name' => 'store_name',
                    'tpl' => 'text-simple',
                    'label' => $this->l('Store Name'),
                    'value' => Configuration::get('PAKETTIKAUPPA_STORE_NAME'),
                    'required' => true,
                ),
                array(
                    'name' => 'address',
                    'tpl' => 'text-simple',
                    'label' => $this->l('Address'),
                    'value' => Configuration::get('PAKETTIKAUPPA_STORE_ADDRESS'),
                    'required' => true,
                ),
                array(
                    'name' => 'postcode',
                    'tpl' => 'text-simple',
                    'label' => $this->l('Post code'),
                    'value' => Configuration::get('PAKETTIKAUPPA_POSTCODE'),
                    'required' => true,
                ),
                array(
                    'name' => 'city',
                    'tpl' => 'text-simple',
                    'label' => $this->l('City'),
                    'value' => Configuration::get('PAKETTIKAUPPA_CITY'),
                    'required' => true,
                ),
                array(
                    'name' => 'country',
                    'tpl' => 'select-simple',
                    'label' => $this->l('Country'),
                    'value' => $options_countries,
                    'selected' => Configuration::get('PAKETTIKAUPPA_COUNTRY'),
                    'required' => true,
                ),
                array(
                    'name' => 'phone',
                    'tpl' => 'text-simple',
                    'label' => $this->l('Phone'),
                    'value' => Configuration::get('PAKETTIKAUPPA_PHONE'),
                    'required' => true,
                    'description' => $this->l('Phone number in international format.'),
                ),
                array(
                    'name' => 'vat_code',
                    'tpl' => 'text-simple',
                    'label' => $this->l('VAT code'),
                    'value' => Configuration::get('PAKETTIKAUPPA_VATCODE'),
                ),
                array(
                    'name' => 'bank_account',
                    'tpl' => 'text-simple',
                    'label' => $this->l('Bank account number'),
                    'value' => Configuration::get('PAKETTIKAUPPA_BANK_ACCOUNT'),
                    'description' => $this->l('Bank account number in IBAN format.') . ' ' . $this->l('Required if want use "Cash on Delivery" service.'),
                ),
                array(
                    'name' => 'bank_bic',
                    'tpl' => 'text-simple',
                    'label' => $this->l('Bank BIC code'),
                    'value' => Configuration::get('PAKETTIKAUPPA_BANK_BIC'),
                    'description' => $this->l('BIC (Bank Identifier Code) of the named bank (also known as SWIFT code).') . ' ' . $this->l('Required if want use "Cash on Delivery" service.'),
                ),
                array(
                    'name' => 'bank_reference',
                    'tpl' => 'text-simple',
                    'label' => $this->l('Bank reference'),
                    'value' => Configuration::get('PAKETTIKAUPPA_BANK_REFERENCE'),
                    'description' => $this->l('Required if want use "Cash on Delivery" service.'),
                ),
            ),
            'front' => array(
                array(
                    'tpl' => 'message',
                    'value' => $this->l('List of pickup-point providers: activate, shipping price, trigger price, triggered price'),
                ),
                array(
                    'name' => 'pickup_points_count',
                    'tpl' => 'select-simple',
                    'label' => $this->l('Number of pickup points'),
                    'value' => $options_pickups_count,
                    'selected' => Configuration::get('PAKETTIKAUPPA_MAX_PICKUPS'),
                    'default' => 5,
                    'class' => 'fixed-width-xs',
                    'description' => $this->l('How many pickup points are shown.'),
                ),
                array(
                    'name' => 'cod_modules',
                    'tpl' => 'select-checkbox',
                    'label' => $this->l('C.O.D. modules'),
                    'value' => $options_cod_modules,
                    'selected' => unserialize(Configuration::get('PAKETTIKAUPPA_COD_MODULES')),
                    'description' => $this->l('Select payment modules for which need use "Cash on Delivery" service.'),
                ),
            ),
            'labels' => array(
                array(
                    'name' => 'shipping_state',
                    'tpl' => 'select-simple',
                    'id' => 'order_state',
                    'label' => $this->l('Automatically generate when state'),
                    'value' => $options_order_states,
                    'selected' => Configuration::get('PAKETTIKAUPPA_SHIPPING_STATE'),
                    'empty_option' => '--- ' . $this->l('Select order state') . ' ---',
                    'class' => 'fixed-width-xl',
                    'description' => $this->l('Order state on which you want automatically generate shipment.'),
                ),
                array(
                    'name' => 'label_comment',
                    'tpl' => 'textarea',
                    'id' => 'label_comment',
                    'label' => $this->l('Add comment on labels'),
                    'value' => Configuration::get('PAKETTIKAUPPA_LABEL_COMMENT'),
                    'description' => $desc_comment_on_label,
                ),
            ),
            'warehouses' => array(
                array(
                    'name' => 'id_warehouse',
                    'tpl' => 'select-simple',
                    'id' => 'id_warehouse',
                    'label' => $this->l('For warehouse'),
                    'value' => $options_warehouses,
                    'selected' => '',
                    'class' => 'fixed-width-xl',
                    'description' => $this->l('Select Warehouse to assign Pakettikauppa Carriers.'),
                ),
                array(
                    'tpl' => 'select-sides',
                    'label' => $this->l('Warehouse'),
                    'label_explain' => $this->l('Associated carriers'),
                    'side_available' => array(
                        'name' => 'ids_carriers_available',
                        'id' => 'availableSwap',
                        'class' => '',
                        'btn_id' => 'addSwap',
                        'btn_txt' => $this->l('Add'),
                    ),
                    'side_selected' => array(
                        'name' => 'ids_carriers_selected',
                        'id' => 'selectedSwap',
                        'class' => '',
                        'btn_id' => 'removeSwap',
                        'btn_txt' => $this->l('Remove'),
                    ),
                    'value' => $options_carriers,
                    'selected' => array(), // Dont need, because ajax is selecting
                    'description' => $this->l('If no carrier is selected, no carrier will be show on order shipping method. Use CTRL+Click to select more than one carrier.'),
                ),
            ),
        );

        return $this->add_missed_field_parameters($fields);
    }

    /**
     * Add required parameters to the configuration fields
     *
     * @param (array) $all_fields - builded configuration fields
     */
    private function add_missed_field_parameters($all_fields)
    {
      $required_fields = array(
        'tpl' => 'not_exists',
      );

      foreach ($all_fields as $section_name => $section_fields) {
        foreach ($section_fields as $field_key => $field) {
          foreach ($required_fields as $req_key => $default_value) {
            $all_fields[$section_name][$field_key][$req_key] = (isset($field[$req_key])) ? $field[$req_key] : $default_value;
          }
        }
      }

      return $all_fields;
    }

    /**
     * Edit Pakettikauppa carriers in warehouse
     *
     * @param (integer) $warehouse_id - Warehouse ID.
     * @param (array) $selected_carriers - List of selected carriers for Warehouse
     */
    private function update_warehouse_carriers($warehouse_id, $selected_carriers)
    {
        $count_removed = 0;
        $count_added = 0;

        $warehouse_values = DB::getInstance()->ExecuteS("SELECT wc.`id_carrier` FROM `" . _DB_PREFIX_ . "warehouse_carrier` wc inner join " . _DB_PREFIX_ . "carrier c on wc.`id_carrier`=c.`id_reference` WHERE wc.`id_warehouse`='" . $warehouse_id . "' AND c.`external_module_name`='pakettikauppa' AND c.`deleted`=0");

        if (!is_array($warehouse_values)) {
            $warehouse_values = array();
        }

        if (!is_array($selected_carriers)) {
            $selected_carriers = array();
        }

        foreach ($warehouse_values as $key => $value) {
            $warehouse_values[$key] = $value['id_carrier'];
            if (!in_array($value['id_carrier'], $selected_carriers)) {
                $this->core->sql->delete_row(array(
                    'table' => _DB_PREFIX_ . 'warehouse_carrier',
                    'where' => array(
                        'id_carrier' => $value['id_carrier'],
                        'id_warehouse' => $warehouse_id,
                    ),
                ));
                $count_removed++;
            }
        }

        foreach ($selected_carriers as $carrier) {
            if (!in_array($carrier, $warehouse_values)) {
                $this->core->sql->insert_row(array(
                    'table' => _DB_PREFIX_ . 'warehouse_carrier',
                    'values' => array(
                        'id_carrier' => $carrier,
                        'id_warehouse' => $warehouse_id,
                    ),
                ));
                $count_added++;
            }
        }

        return array('removed' => $count_removed, 'added' => $count_added);
    }

    /**
     * Show PS admin message from cookie
     */
    protected function show_msg()
    {
        $output = '';

        if (isset($this->context->cookie->success_msg)) {
            $msg = $this->context->cookie->success_msg;
            /* Delete old messages */
            unset($this->context->cookie->success_msg);
            $output .= $this->displayConfirmation($msg);
        }
        if (isset($this->context->cookie->error_msg)) {
            $msg = $this->context->cookie->error_msg;
            /* Delete old messages */
            unset($this->context->cookie->error_msg);
            $output .= $this->displayError($msg);
        }

        return $output;
    }

    /**
     * Save form data
     */
    protected function postProcess()
    {
        if (((bool)Tools::isSubmit('submitPakettikauppaModule')) == true) {
            $this->update_warehouse_carriers(Tools::getValue('id_warehouse'), Tools::getValue('ids_carriers_selected'));
            
            $this->context->cookie->__set('success_msg', $this->l('Warehouse carriers updated successfully'));
        }

        if (((bool)Tools::isSubmit('submitPakettikauppaShippingLabels')) == true) {
            Configuration::updateValue('PAKETTIKAUPPA_SHIPPING_STATE', Tools::getValue('shipping_state'));
            Configuration::updateValue('PAKETTIKAUPPA_LABEL_COMMENT', Tools::getValue('label_comment'));
            
            $this->context->cookie->__set('success_msg', $this->l('Labels settings saved successfully'));
        }
        if (((bool)Tools::isSubmit('submitPakettikauppaSender')) == true) {
            Configuration::updateValue('PAKETTIKAUPPA_STORE_NAME', Tools::getValue('store_name'));
            Configuration::updateValue('PAKETTIKAUPPA_STORE_ADDRESS', Tools::getValue('address'));
            Configuration::updateValue('PAKETTIKAUPPA_POSTCODE', Tools::getValue('postcode'));
            Configuration::updateValue('PAKETTIKAUPPA_CITY', Tools::getValue('city'));
            Configuration::updateValue('PAKETTIKAUPPA_PHONE', Tools::getValue('phone'));
            Configuration::updateValue('PAKETTIKAUPPA_COUNTRY', Tools::getValue('country'));
            Configuration::updateValue('PAKETTIKAUPPA_VATCODE', Tools::getValue('vat_code'));
            Configuration::updateValue('PAKETTIKAUPPA_BANK_ACCOUNT', Tools::getValue('bank_account'));
            Configuration::updateValue('PAKETTIKAUPPA_BANK_BIC', Tools::getValue('bank_bic'));
            Configuration::updateValue('PAKETTIKAUPPA_BANK_REFERENCE', Tools::getValue('bank_reference'));
            
            $this->context->cookie->__set('success_msg', $this->l('Sender data saved successfully'));
        }

        if (((bool)Tools::isSubmit('submitPakettikauppaFront')) == true) {
            Configuration::updateValue('PAKETTIKAUPPA_MAX_PICKUPS', Tools::getValue('pickup_points_count'));
            Configuration::updateValue('PAKETTIKAUPPA_COD_MODULES', serialize(Tools::getValue('cod_modules')));

            $this->context->cookie->__set('success_msg', $this->l('Checkout settings saved successfully'));
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
        $carrier->url = 'https://www.pakettikauppa.fi/seuranta/?@'; //@ - tracking number

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
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->context->controller->addJS($this->_path . 'views/js/front_17.js');
            $this->context->controller->addCSS($this->_path . 'views/css/front_17.css');
        } else {
            $this->context->controller->addJS($this->_path . 'views/js/front_16.js');
            $this->context->controller->addCSS($this->_path . 'views/css/front_16.css');
        }
    }

    public function hookDisplayCarrierExtraContent($params)
    {
        return $this->hookDisplayCarrierList($params);
    }

    public function hookDisplayCarrierList($params)
    {
        $display = "none";
        $pickup_points = array();
        $version = '16';

        $client = $this->core->api->load_client();

        $id_carrier = $params['cart']->id_carrier;
        $template = 'front/carrier_list_16.tpl';

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $version = '17';
            $id_carrier = $params['carrier']['id'];
            $template = 'front/carrier_list_17.tpl';
        }

        $address = new Address($params['cart']->id_address_delivery);
        $country_iso = Country::getIsoById($address->id_country);
        $carrier = new Carrier($id_carrier);

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
            $pickups_number = Configuration::get('PAKETTIKAUPPA_MAX_PICKUPS');
            if (empty($pickups_number)) $pickups_number = 5;
            $pickup_points = $client->searchPickupPoints($address->postcode, null, $country_iso, $selected_method['code'], $pickups_number);
            if (empty($pickup_points)) $pickup_points = array();
        }

        $current_values = $this->core->sql->get_single_row(array(
            'table' => 'main',
            'get_values' => array(
                'point' => 'pickup_point_id',
                'method' => 'method_code',
            ),
            'where' => array(
                'id_cart' => $params['cart']->id,
            ),
        ));

        if (!$current_values) {
            $this->core->sql->insert_row(array(
                'table' => 'main',
                'values' => array(
                    'id_cart' => $params['cart']->id,
                    'id_carrier' => $id_carrier,
                    'method_code' => $selected_method['code'],
                ),
                'on_duplicate' => array(
                    'id_carrier' => $id_carrier,
                    'method_code' => $selected_method['code'],
                ),
            ));
        }
       
        $pickup_point_id = 0;
        if (count($pickup_points) > 0) {
            if (!empty($current_values['point']) && $current_values['method'] == $selected_method['code']) {
                $pickup_point_id = $current_values['point'];
            } else {
                $pickup_point_id = $pickup_points[0]->pickup_point_id;
                if ($version == '16') {
                    $this->core->sql->update_row(array(
                        'table' => 'main',
                        'update' => array(
                            'pickup_point_id' => $pickup_point_id,
                        ),
                        'where' => array(
                            'id_cart' => $params['cart']->id,
                        ),
                    ));
                }
            }
        }

        if ($current_values['method'] != $selected_method['code'] && $version == '16') {
            $this->core->sql->update_row(array(
                'table' => 'main',
                'update' => array(
                    'id_carrier' => $id_carrier,
                    'method_code' => $selected_method['code'],
                ),
                'where' => array(
                    'id_cart' => $params['cart']->id,
                ),
            ));
        }

        $this->context->smarty->assign(array(
            'class_has_pp' => ($selected_method['has_pp']) ? 'has_pp' : '',
            'pick_up_points' => $pickup_points,
            'selected_method' => $selected_method['code'],
            'selected_point' => $pickup_point_id,
            'ajax_url' => $this->_path . 'ajax.php',
            'id_cart' => $params['cart']->id,
            'id_carrier' => $id_carrier,
            'display' => $display,
            'current_postcode' => $address->postcode,
        ));
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/' . $template);
        
        return $output;
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        $check_state = Configuration::get('PAKETTIKAUPPA_SHIPPING_STATE');

        if ($check_state == $params['newOrderStatus']->id) {
            $shipment = $this->core->label->generate_shipment($params['id_order']);
            //if ($shipment['status'] === 'success') {}
            //$this->core->label->generate_label_pdf($params['id_order']); //Or generate and open PDF
        }
    }
}
