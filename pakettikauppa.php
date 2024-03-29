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
    protected $hooks = array(
        'actionAdminControllerSetMedia',
        'actionEmailAddAfterContent',
        'actionOrderStatusPostUpdate',
        'actionProductUpdate',
        'actionValidateOrder',
        'displayAdminOrder',
        'displayAdminProductsExtra',
        'displayBackOfficeHeader',
        'displayCarrierExtraContent',
        'displayCarrierList',
        'displayHeader',
        'header',
        'sendMailAlterTemplateVars',
        'updateCarrier',
    );
    protected $tracking_url = 'https://www.pakettikauppa.fi/seuranta/?@'; //@ - tracking number

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
            'module_dir' => _PS_MODULE_DIR_ . $this->name,
            'module_name' => $this->name,
            'translates' => array(
                'error_order_object' => $this->l('Cant load Order object'),
                'error_ship_not_found' => $this->l('Shipment information not found'),
            ),
            'services_translates' => array(
                '3101' => $this->l('Cash on delivery'),
                '3102' => $this->l('Multi-package'),
                '3104' => $this->l('Fragile'),
                '3106' => $this->l('Saturday delivery'),
                '3143' => $this->l('Small amount of hazardous substance'),
                '3146' => $this->l('Pickup reminder by letter'),
                '3163' => $this->l('To be handed over in person'),
                '3164' => $this->l('Delivery without acknowledging the recipient'),
                '3165' => $this->l('Extension of shelf life'),
                '3166' => $this->l('Call before distribution'),
                '3174' => $this->l('Oversized'),
                '3376' => $this->l('Blocking of the control to the outdoor vending machine'),
                '9902' => $this->l('Transaction code'),
                //'9904' => $this->l(''),
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

        $this->add_custom_order_state(array(
            'name' => 'PAKETTIKAUPPA_CUSTOM_STATE_READY',
            'titles' => array(
                'en' => 'Pakettikauppa shipment ready',
            ),
            'color' => '#233385',
            'img' => 'state-ready',
        ));
        $this->add_custom_order_state(array(
            'name' => 'PAKETTIKAUPPA_CUSTOM_STATE_ERROR',
            'titles' => array(
                'en' => 'Pakettikauppa shipment error',
            ),
            'color' => '#FF2E33',
            'img' => 'state-error',
        ));

        if (parent::install()) {
            foreach ($this->hooks as $hook) {
                if (!$this->registerHook($hook)) {
                    return false;
                }
            }

            if (!$this->installModuleTab()) {
                return false;
            }

            return true;
        }

        return false;
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

    private function add_custom_order_state($params)
    {
        $existing_state_id = (int) Configuration::get($params['name']);
        $existing_state = new OrderState((int) $existing_state_id, (int) $this->context->language->id);
        
        if (!$existing_state_id || !$existing_state->id) {
            $new_state = new OrderState();
            $new_state->name = array();
            foreach (Language::getLanguages() as $language) {
                $iso_code = strtolower($language['iso_code']);
                if (isset($params['titles'][$iso_code])) {
                    $new_state->name[$language['id_lang']] = $params['titles'][$iso_code];
                } else {
                    $new_state->name[$language['id_lang']] = $params['titles']['en'];
                }
            }
            $new_state->send_email = false;
            $new_state->color = $params['color'];
            $new_state->hidden = false;
            $new_state->delivery = false;
            $new_state->logable = true;
            $new_state->invoice = false;
            $new_state->unremovable = false;
            if ($new_state->add()) {
                if (!empty($params['img'])) {
                    $img_source = $this->local_path . 'views/img/' . $params['img'] . '.gif';
                    $img_destination = _PS_ROOT_DIR_ . '/img/os/' . $new_state->id . '.gif';
                    copy($img_source, $img_destination);
                }
                Configuration::updateValue($params['name'], $new_state->id);
                return true;
            }
        }

        return false;
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
            'template_parts_path' => $this->local_path . 'views/templates/admin/parts/module_configure',
            'fields' => $this->get_configuration_fields(),
            'warehouses' => Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT'),
        ));
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/page-module_configure.tpl');

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
                    'name' => 'pickup_list_style',
                    'tpl' => 'select-simple',
                    'label' => $this->l('Pickup point selection style'),
                    'value' => array(
                        'radio' => $this->l('Radio buttons'),
                        'dropdown' => $this->l('Dropdown menu'),
                    ),
                    'selected' => Configuration::get('PAKETTIKAUPPA_LIST_STYLE'),
                    'default' => 'radio',
                    'class' => 'fixed-width-xl',
                    'description' => $this->l('How to display a list of pick-up points.'),
                ),
                array(
                    'name' => 'pickup_auto_select',
                    'tpl' => 'select-switcher',
                    'label' => $this->l('Automatically select a pickup point'),
                    'selected' => Configuration::get('PAKETTIKAUPPA_AUTO_SELECT'),
                    'description' => $this->l('Automatically select the nearest pickup point.') . ' ' . $this->l('Not working, when selection style is radio buttons.'),
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
                array(
                    'name' => 'use_custom_states',
                    'tpl' => 'select-switcher',
                    'label' => $this->l('Use custom order states'),
                    'selected' => Configuration::get('PAKETTIKAUPPA_USE_CUSTOM_STATES'),
                    'description' => $this->l('Allow change order state to module custom generated states, when label is generated.'),
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
            Configuration::updateValue('PAKETTIKAUPPA_USE_CUSTOM_STATES', Tools::getValue('use_custom_states'));
            
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
            Configuration::updateValue('PAKETTIKAUPPA_LIST_STYLE', Tools::getValue('pickup_list_style'));
            Configuration::updateValue('PAKETTIKAUPPA_AUTO_SELECT', Tools::getValue('pickup_auto_select'));
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
        $carrier->url = $this->tracking_url;

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
    public function hookDisplayBackOfficeHeader()
    {
        $module_name = (version_compare(_PS_VERSION_, '1.7', '>=')) ? Tools::getValue('configure') : Tools::getValue('module_name');

        $this->context->controller->addCSS($this->_path . 'views/css/back.css');

        if ($module_name == $this->name) {
            $this->context->controller->addJquery();
            $this->context->controller->addJS($this->_path . 'views/js/back-settings.js');
        }
    }

    public function hookActionAdminControllerSetMedia($params) {
        if (!empty($params['cart'])) {
            $carrier = new Carrier($params['cart']->id_carrier);
            $pakketikauppa_carrier = $this->core->sql->get_single_row(array(
                'table' => 'methods',
                'get_values' => array(),
                'where' => array(
                    'id_carrier_reference' => $carrier->id_reference,
                ),
            ));
            if (!empty($pakketikauppa_carrier)) {
                $this->context->controller->addJS($this->_path.'views/js/back-order_edit.js');
            }
        }
        if (version_compare(_PS_VERSION_, '1.7.7', '>=') && 'AdminOrders' === Tools::getValue('controller')) {
            $this->context->controller->addJS($this->_path.'views/js/back-order_edit.js');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     * Prestashop 1.6-1.7.6
     */
    public function hookHeader()
    {
        if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
            return;
        }
        /*** Load style and script files ***/
        $this->context->controller->addJS($this->_path . 'views/js/dropdown.js');
        $this->context->controller->addCSS($this->_path . 'views/css/dropdown.css');

        $this->context->controller->addJS($this->_path . 'views/js/front_global.js');
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $this->context->controller->addJS($this->_path . 'views/js/front_17.js');
            $this->context->controller->addCSS($this->_path . 'views/css/front_17.css');
        } else {
            $this->context->controller->addJS($this->_path . 'views/js/front_16.js');
            $this->context->controller->addCSS($this->_path . 'views/css/front_16.css');
        }

        /*** Load global script variables ***/
        if (in_array(Context::getContext()->controller->php_self, array('order-opc', 'order'))) {
            $this->context->smarty->assign(array(
                'ajax_url' => $this->_path . 'ajax.php',
                'configs' => array(
                    'autoselect' => Configuration::get('PAKETTIKAUPPA_AUTO_SELECT'),
                ),
            ));

            return $this->context->smarty->fetch($this->local_path . 'views/templates/front/checkout_header.tpl');
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     * Prestashop 1.7.7+
     */
    public function hookDisplayHeader()
    {
        /*** Load style and script files ***/
        $this->context->controller->addJS($this->_path . 'views/js/dropdown.js');
        $this->context->controller->addCSS($this->_path . 'views/css/dropdown.css');

        $this->context->controller->addJS($this->_path . 'views/js/front_global.js');
        $this->context->controller->addJS($this->_path . 'views/js/front_17.js');
        $this->context->controller->addCSS($this->_path . 'views/css/front_17.css');

        /*** Load global script variables ***/
        if (in_array(Context::getContext()->controller->php_self, array('order-opc', 'order'))) {
            $this->context->smarty->assign(array(
                'ajax_url' => $this->_path . 'ajax.php',
                'configs' => array(
                    'autoselect' => Configuration::get('PAKETTIKAUPPA_AUTO_SELECT'),
                ),
            ));

            return $this->context->smarty->fetch($this->local_path . 'views/templates/front/checkout_header.tpl');
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
        $template = 'front/carrier_list.tpl';

        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            $version = '17';
            $id_carrier = $params['carrier']['id'];
        }

        $pickup_list_style = Configuration::get('PAKETTIKAUPPA_LIST_STYLE');
        if (empty($pickup_list_style)) $pickup_list_style = 'radio';
        $template = str_replace('.tpl', '_' . $pickup_list_style . '.tpl', $template);

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
            'table' => 'orders',
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
                'table' => 'orders',
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
                if ($version == '16') {
                    $this->core->sql->update_row(array(
                        'table' => 'orders',
                        'update' => array(
                            'pickup_point_id' => $pickup_point_id,
                        ),
                        'where' => array(
                            'id_cart' => $params['cart']->id,
                        ),
                    ));
                }
            }
            if (!empty(Configuration::get('PAKETTIKAUPPA_AUTO_SELECT'))) {
                if (empty($pickup_point_id)) {
                    $pickup_point_id = $pickup_points[0]->pickup_point_id;
                    if ($current_values['method'] === $selected_method['code']) {
                        $this->core->sql->update_row(array(
                            'table' => 'orders',
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
        }

        if ($current_values['method'] != $selected_method['code'] && $version == '16') {
            $this->core->sql->update_row(array(
                'table' => 'orders',
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
            'search_img' => $this->_path . 'views/img/icon-search.png',
            'version' => $version,
        ));
        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/' . $template);
        
        return $output;
    }

    public function hookActionOrderStatusPostUpdate($params)
    {
        $check_state = Configuration::get('PAKETTIKAUPPA_SHIPPING_STATE');

        if ($check_state == $params['newOrderStatus']->id) {
            $shipment = $this->core->label->generate_shipment($params['id_order']);
            //$this->core->label->generate_label_pdf($params['id_order']); //Or generate and open PDF
        }
    }

    public function hookSendMailAlterTemplateVars($params) //Add template variables for PS 1.7
    {
        if (empty($params['cart']->id) || empty($params['cart']->id_carrier)) {
            return;
        }

        $pakketikauppa_order = $this->core->sql->get_single_row(array(
            'table' => 'orders',
            'get_values' => array(),
            'where' => array(
                'id_cart' => $params['cart']->id,
            ),
        ));
        if (empty($pakketikauppa_order) || empty($pakketikauppa_order['track_number'])) {
            return;
        }

        $carrier = new Carrier($params['cart']->id_carrier);

        $tracking_number = $pakketikauppa_order['track_number'];
        $tracking_url = (!empty($carrier->url)) ? $carrier->url : $this->tracking_url;
        $tracking_url = str_replace('@', $tracking_number, $tracking_url);

        $template_vars = array(
            '{shipping_number}' => $tracking_number,
            '{followup}' => $tracking_url,
        );

        foreach ($template_vars as $variable_key => $variable_value) {
            if (empty($params['template_vars'][$variable_key])) {
                $params['template_vars'][$variable_key] = $variable_value;
            }
        }

        return $params;
    }

    public function hookActionEmailAddAfterContent($params) //Add template variables for PS 1.6
    {
        if (version_compare(_PS_VERSION_, '1.7', '>=')) {
            return;
        }

        if (empty($params['cart']->id) || empty($params['cart']->id_carrier)) {
            return;
        }

        $pakketikauppa_order = $this->core->sql->get_single_row(array(
            'table' => 'orders',
            'get_values' => array(),
            'where' => array(
                'id_cart' => $params['cart']->id,
            ),
        ));
        if (empty($pakketikauppa_order) || empty($pakketikauppa_order['track_number'])) {
            return;
        }

        $carrier = new Carrier($params['cart']->id_carrier);

        $tracking_number = $pakketikauppa_order['track_number'];
        $tracking_url = (!empty($carrier->url)) ? $carrier->url : $this->tracking_url;
        $tracking_url = str_replace('@', $tracking_number, $tracking_url);

        $template_vars = array(
            '{shipping_number}' => $tracking_number,
            '{followup}' => $tracking_url,
        );

        foreach ($template_vars as $variable_key => $variable_value) {
            $params['template_html'] = str_replace($variable_key, $variable_value, $params['template_html']);
            $params['template_txt'] = str_replace($variable_key, $variable_value, $params['template_txt']);
        }

        return $params;
    }

    public function hookActionValidateOrder($params)
    {
        if (empty($params['order']->module) || empty($params['cart']->id)) {
            return;
        }

        $is_cod = $this->core->services->payment_is_cod($params['order']->module);
        
        if ($is_cod) {
            $this->core->services->add_service_to_order($params['cart']->id, $this->core->services->get_service_code('cod'));
        }

        $dangerous_goods = $this->core->services->get_order_dangerous_goods($params['order']);
        if (!empty($dangerous_goods['weight'])) {
            $service_code = $this->core->services->get_service_code('dangerous');
            $this->core->services->add_service_to_order($params['cart']->id, $service_code);
        }
    }

    public function hookDisplayAdminOrder($id_order)
    {
        $template = 'hook-admin_order.tpl';
        $critical_errors = array();
        $warning_errors = array();
        $pickup_points = array();
        $selected_point = '';
        $shipping_labels = array();
        $additional_services = array();
        $selected_services = array();
        $dangerous_goods = array('weight' => 0, 'count' => 0);

        $order = new Order((int)$id_order['id_order']);
        $carrier = new Carrier($order->id_carrier);
        
        $is_cod = $this->core->services->payment_is_cod($order->module);

        $pakketikauppa_carrier = $this->core->sql->get_single_row(array(
            'table' => 'methods',
            'get_values' => array(),
            'where' => array(
                'id_carrier_reference' => $carrier->id_reference,
            ),
        ));

        if (empty($pakketikauppa_carrier)) { //Not Pakettikauppa Shipping
            return;
        }

        $pakketikauppa_order = $this->core->sql->get_single_row(array(
            'table' => 'orders',
            'get_values' => array(),
            'where' => array(
                'id_cart' => $order->id_cart,
            ),
        ));

        if (!empty($pakketikauppa_order)) {
            $additional_services = $this->core->api->get_additional_services($pakketikauppa_carrier['method_code']);
            $selected_services = $this->core->services->get_order_services($order->id_cart);

            if (!empty($pakketikauppa_order['track_number'])) {
                if (strpos($pakketikauppa_order['track_number'], ',') !== false) {
                    $all_labels = explode(',', $pakketikauppa_order['track_number']);
                    foreach ($all_labels as $label) {
                        $shipping_labels[] = $label;
                    }
                } else {
                    $shipping_labels[] = $pakketikauppa_order['track_number'];
                }
            }

            if ($pakketikauppa_carrier['has_pp']) {
                $client = $this->core->api->load_client();
                $address = new Address($order->id_address_delivery);
                $country_iso = Country::getIsoById($address->id_country);
                $shipping_methods = $client->listShippingMethods();

                try {
                    $selected_point = json_decode($client->getPickupPointInfo($pakketikauppa_order['pickup_point_id'], $pakketikauppa_carrier['method_code']));
                    $pickup_points = $client->searchPickupPoints($address->postcode, null, $country_iso, $pakketikauppa_carrier['method_code'], 100);
                } catch (Exception $ex) {
                    $critical_errors[] = $this->l('Error from Pakettikauppa server') . ': ' . $ex->getMessage();
                    $selected_point = '';
                    $pickup_points = array();
                }
            }

            if ($is_cod && !isset($additional_services['3101'])) {
                $warning_errors[] = $this->l('In the order is selected the Cash on Delivery (COD) payment method, but the selected shipping method does not support this service.') . '<br/><b>' . $this->l('The COD service will not be added to the generated label!') . '</b>';
            }

            $dangerous_goods = $this->core->services->get_order_dangerous_goods($order);
        } else {
            $critical_errors[] = $this->l('Pakettikauppa order information was not found');
        }

        $this->context->smarty->assign(array(
            'table_style' => (version_compare(_PS_VERSION_, '1.7.7', '>=')) ? 'card' : 'panel',
            'template_parts_path' => $this->local_path . 'views/templates/admin/parts/admin_order',
            'critical_errors' => $critical_errors,
            'warning_errors' => $warning_errors,
            'method_name' => $carrier->name,
            'pickup_points' => $pickup_points,
            'selected_pickup_point' => $selected_point,
            'shipping_labels' => $shipping_labels,
            'tracking_url' => $carrier->url,
            'controller_url' => $this->context->link->getAdminLink('AdminPakettikauppa'),
            'ajax_url' => $this->_path . 'ajax.php',
            'cart_id' => $order->id_cart,
            'all_additional_services' => $additional_services,
            'selected_additional_services' => $selected_services,
            'payment_is_cod' => $is_cod,
            'order_amount' => Tools::ps_round($order->getOrdersTotalPaid(), 2),
            'currency' => (version_compare(_PS_VERSION_, '1.7', '>=')) ? $this->context->currency->symbol : $this->context->currency->sign,
            'dangerous_goods' => $dangerous_goods,
            'weight_unit' => $this->l('kg'),
        ));

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/' . $template);

        return $output;
    }

    public function hookDisplayAdminProductsShippingStepBottom($params) { //TODO: Maybe not need. Still not added to the hooks list
      //Product id: $params['id_product']
      /*$template = 'hook-admin_product-shipping.tpl';
      
      $fields = array(
        'dangerous' => array(
          'title' => $this->l('Dangerous goods'),
          'help' => $this->l('Content of hazardous substances in the product'),
          'fields' => array(
            array(
              'type' => 'number',
              'key' => 'pk_dangerous_weight',
              'label' => $this->l('Weight'),
              'value' => 0,
              'prepend' => '',
              'append' => $this->l('kg'),
              'width' => 2,
            ),
          ),
        ),
      );

      $this->context->smarty->assign(array(
        'template_parts_path' => $this->local_path . 'views/templates/admin/parts/admin_product',
        'tab_step' => 4,
        'fields' => $fields,
      ));

      $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/' . $template);
      //return '<pre>'.print_r(get_object_vars($params['request']->attributes),true).'</pre>';
      //return '<pre>'.print_r(array_keys((array)$params['request']->attributes),true).'</pre>';
      return '<pre>'.print_r(array_keys($params),true).'</pre>';
      return $output;*/
    }

    public function hookDisplayAdminProductsExtra($params) {
        if (isset($params['id_product'])) {
            $id_product = (int) $params['id_product'];
        } else {
            $id_product = (int) Tools::getValue('id_product');
        }

        $template = 'page-admin_product.tpl';
        if (version_compare(_PS_VERSION_, '1.7', '<')) {
            $template = 'page-admin_product_16.tpl';
        }

        $product_params = array();
        $sql_product = $this->core->sql->get_single_row(array(
            'table' => 'products',
            'get_values' => array('params'),
            'where' => array(
                'id_product' => $id_product,
            ),
        ));
        if (!empty($sql_product['params'])) {
            $product_params = unserialize($sql_product['params']);
        }

        $fields = array(
            'dangerous' => array(
                'title' => $this->l('Dangerous goods'),
                'help' => $this->l('Content of hazardous substances in the product'),
                'fields' => array(
                    array(
                        'type' => 'number',
                        'key' => 'lqweight',
                        'label' => $this->l('Weight'),
                        'value' => (isset($product_params['lqweight'])) ? $product_params['lqweight'] : 0,
                        'prepend' => '',
                        'append' => $this->l('kg'),
                        'width' => 2,
                        'step' => 0.001,
                        'min' => 0,
                        'max' => '',
                    ),
                ),
            ),
        );

        $this->context->smarty->assign(array(
            'template_parts_path' => $this->local_path . 'views/templates/admin/parts/admin_product',
            'module_name' => $this->name,
            'all_sections' => $fields,
        ));

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/' . $template);
        
        return $output;
    }

    public function hookActionProductUpdate($params) {
        if (empty($params['id_product'])) {
            return;
        }

        $module_fields = Tools::getValue($this->name);

        $sql_product = $this->core->sql->get_single_row(array(
            'table' => 'products',
            'get_values' => array('params'),
            'where' => array(
                'id_product' => $params['id_product'],
            ),
        ));

        if (empty($sql_product)) {
            $this->core->sql->insert_row(array(
                'table' => 'products',
                'values' => array(
                    'id_product' => $params['id_product'],
                    'params' => serialize($module_fields),
                ),
            ));

            return;
        }

        $product_params = unserialize($sql_product['params']);
        if (!is_array($product_params)) {
            $product_params = array();
        }

        foreach ($module_fields as $param_key => $param_value) {
            $product_params[$param_key] = $param_value;
        }

        $this->core->sql->update_row(array(
            'table' => 'products',
            'update' => array(
                'params' => (!empty($product_params)) ? serialize($product_params) : '',
            ),
            'where' => array(
                'id_product' => $params['id_product'],
            ),
        ));
    }
}
