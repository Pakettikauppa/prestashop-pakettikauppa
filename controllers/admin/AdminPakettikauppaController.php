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
        parent::__construct();

        $this->core = new PS_Pakettikauppa(array(
            'translates' => array(
                'error_order_object' => $this->l('Cant load Order object'),
                'error_ship_not_found' => $this->l('Shipment information not found'),
                'error_required_postcode' => $this->l('Sender postcode is required'),
                'error_failed_get_tracking' => $this->l('Failed get tracking code'),
                'error_tracking_empty' => $this->l('Empty tracking code value'),
                'error_label_pdf_empty' => $this->l('Not received label PDF'),
                'error_from_api' => $this->l('Got error from Pakettikauppa server'),
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

        $this->bootstrap = true;
        $this->table = $this->core->sql->get_table_name('orders', '');
        $this->allow_export = true;
        $this->_defaultOrderBy = 'id';
        $this->_defaultOrderWay = 'DESC';
        $this->list_no_link = true;
        $this->addRowAction('Pdf');
        $this->identifier = 'id_cart';
        $this->simple_header = false;

        $this->context = Context::getContext();

        $this->_select = "o.id_order,
            concat(c.firstname,' ',c.lastname) as customer_name,
            ROUND(o.total_paid,2) as total,
            a.track_number as id_track,
            a.id_cart as pickup_point,
            a.id_carrier as id_carrier,
            a.id_cart as services";
        $this->_join = 'inner join ' . _DB_PREFIX_ . 'orders o on a.id_cart= o.id_cart inner join ' . _DB_PREFIX_ . 'customer c on o.id_customer=c.id_customer';
        $this->_orderBy = 'id_order';
        $this->_orderWay = 'DESC';

        $this->fields_list = array(
            'id_order' => array(
                'title' => $this->l('Order ID'),
                'class' => 'fixed-width-xs',
                'type' => 'text',
                'callback' => 'printOrderLink',
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
                'havingFilter' => true,
            ),
            'services' => array(
                'title' => $this->l('Use services'),
                'width' => 'auto',
                'type' => 'text',
                'callback' => 'additionalServices',
                'search' => false,
                'align' => 'center',
                'havingFilter' => false,
                'orderby' => false,
                'hint' => $this->l('You can set additional services in order edit page.'),
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

        if (Tools::getValue('submitAction') == 'printShippingSlipPDF' || Tools::getValue('submitAction') == 'regenerateShippingSlipPDF') {
            $id_order = $this->core->sql->get_single_row(array(
                'table' => _DB_PREFIX_ . 'orders',
                'get_values' => array('id_order'),
                'where' => array(
                    'id_cart' => Tools::getValue('id_cart'),
                ),
            ));
            if (!isset($id_order['id_order'])) {
                die($this->l('Failed to get order ID'));
            }
            $id_order = (int)$id_order['id_order'];

            if (Tools::getValue('submitAction') == 'printShippingSlipPDF') {
                $this->core->label->generate_label_pdf($id_order);
            } else {
                $this->core->label->generate_label_pdf($id_order, true);
            }

            die($this->l('Failed to generate label PDF'));
        }
    }

    public function setMedia($isNewTheme = false)
    {
        parent::setMedia();

        Media::addJsDef(array(
            'pakettikauppa_ajax' => _MODULE_DIR_ . $this->module->name . '/ajax.php',                       
        ));
        $this->context->controller->addCss(_MODULE_DIR_ . $this->module->name . '/views/css/back.css', 'all');
        $this->context->controller->addJS(_MODULE_DIR_ . $this->module->name . '/views/js/back-orders_list.js');
    }

    public function printOrderLink($id_order, $row)
    {
        $link = $this->context->link->getAdminLink('AdminOrders').'&id_order='.(int)$id_order.'&vieworder';
        if (version_compare(_PS_VERSION_, '1.7.7', '>=')) {
            $link = $this->context->link->getAdminLink('AdminOrders', true, ['route' => 'admin_orders_view', 'action' => 'vieworder', 'orderId' => (int)$id_order]);
        }

        return '<a href="' . $link . '" target="_blank">' . $id_order . '</a>';
    }

    public function getCarrierName($id_carrier, $tr)
    {
        $carrier = new Carrier($id_carrier);

        return (!empty($carrier->name)) ? $carrier->name : $this->empty_value;
    }

    public function getPickupName($id_cart, $tr)
    {
        $method = $this->core->sql->get_single_row(array(
            'table' => 'orders',
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
        $tracking_number = $this->core->label->get_tracking_number_from_db($cart_id);
        
        $this->context->smarty->assign(array(
            'order' => $cart_id,
            'have_label' => ($tracking_number) ? true : false,
        ));

        return $this->context->smarty->fetch($this->core->configs->module_dir . '/views/templates/admin/table-print_pdf.tpl');
    }

    public function additionalServices($cart_id, $row_data)
    {
        $order = (isset($row_data['id_order'])) ? new \Order((int)$row_data['id_order']) : '';

        if (empty($order)) {
          return $this->l('Error');
        }

        $additional_services = $this->core->api->get_additional_services($row_data['method_code']);
        $selected_services_text = '';
        $selected_services = $this->core->services->get_order_services($cart_id);

        $is_cod = $this->core->services->payment_is_cod($order->module);

        foreach ($selected_services as $service_code => $service_value) {
            if (!isset($additional_services[$service_code])) {
                continue;
            }
            if (!empty($selected_services_text)) {
                $selected_services_text .= ', ';
            }
            $selected_services_text .= '<span title="' . $additional_services[$service_code]->name . '">' . $service_code . '</span>';
        }
        if (empty($selected_services_text)) {
            $selected_services_text = '—';
        }

        $this->context->smarty->assign(array(
            'order_id' => (isset($row_data['id_order'])) ? $row_data['id_order'] : $cart_id,
            'cart_id' => $cart_id,
            'row_data' => $row_data,
            'additional_services' => $additional_services,
            'selected_services' => $selected_services,
            'selected_services_text' => $selected_services_text,
            'is_cod' => $is_cod,
        ));

        return $this->context->smarty->fetch($this->core->configs->module_dir . '/views/templates/admin/table-additional_services.tpl');
    }
}
