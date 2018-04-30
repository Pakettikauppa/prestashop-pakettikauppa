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
include(_PS_MODULE_DIR_ . 'pakettikauppa/classes/Pakettikauppashipping.php');
include(_PS_MODULE_DIR_ . 'pakettikauppa/classes/pdf/HTMLTemplatePakiShipping.php');
include(_PS_MODULE_DIR_ . 'pakettikauppa/classes/pdf/HTMLTemplateDeliverySlip.php');

include(_PS_MODULE_DIR_ . 'pakettikauppa/classes/pdf/PDFS.php');
include(_PS_MODULE_DIR_ . 'pakettikauppa/classes/pdf/PDFGenerators.php');

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
            $pdf = new PDFS($order_invoice_collection, 'DeliverySlip', Context::getContext()->smarty);
            $pdf->render();

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


