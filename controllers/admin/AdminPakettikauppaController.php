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

        $this->core = new PS_Pakettikauppa(array(
          'translates' => array(
            'error_order_object' => $this->l('Cant load Order object'),
            'error_required_postcode' => $this->l('Sender postcode is required'),
            'error_failed_get_tracking' => $this->l('Failed get tracking code'),
            'error_tracking_empty' => $this->l('Empty tracking code value'),
            'error_label_pdf_empty' => $this->l('Not received label PDF'),
          ),
        ));

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

            $this->core->label->generate_PDF($id_order);

            die($this->l('Failed to generate label PDF'));
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
