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

/**
 * @since 1.5
 */
class HTMLTemplateDeliverySlip extends HTMLTemplate
{
    public $order;
    public $track_id;

    /**
     * @param OrderInvoice $order_invoice
     * @param $smarty
     * @throws PrestaShopException
     */
    public function __construct(OrderInvoice $order_invoice, $smarty, $bulk_mode = false)
    {

        $this->order_invoice = $order_invoice;
        $this->order = new Order($this->order_invoice->id_order);
        $this->smarty = $smarty;

        // If shop_address is null, then update it with current one.
        // But no DB save required here to avoid massive updates for bulk PDF generation case.
        // (DB: bug fixed in 1.6.1.1 with upgrade SQL script to avoid null shop_address in old orderInvoices)
        if (!isset($this->order_invoice->shop_address) || !$this->order_invoice->shop_address) {
            $this->order_invoice->shop_address = OrderInvoice::getCurrentFormattedShopAddress((int)$this->order->id_shop);
            if (!$bulk_mode) {
                OrderInvoice::fixAllShopAddresses();
            }
        }

        // header informations
        $this->date = Tools::displayDate($order_invoice->date_add);
        $prefix = Configuration::get('PS_DELIVERY_PREFIX', Context::getContext()->language->id);
        $this->title = sprintf(HTMLTemplateDeliverySlip::l('%1$s%2$06d'), $prefix, $this->order_invoice->delivery_number);

        // footer informations
        $this->shop = new Shop((int)$this->order->id_shop);
    }

    /**
     * Returns the template's HTML header
     *
     * @return string HTML header
     */
    public function getHeader()
    {
        $this->assignCommonHeaderData();
        $this->smarty->assign(array('header' => HTMLTemplateDeliverySlip::l('Delivery')));

        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/pdf/header.tpl');
    }

    /**
     * Returns the template's HTML content
     *
     * @return string HTML content
     */
    public function getContent()
    {
        $delivery_address = new Address((int)$this->order->id_address_delivery);
        $formatted_delivery_address = AddressFormat::generateAddress($delivery_address, array(), '<br />', ' ');
        $formatted_invoice_address = '';

        if ($this->order->id_address_delivery != $this->order->id_address_invoice) {
            $invoice_address = new Address((int)$this->order->id_address_invoice);
            $formatted_invoice_address = AddressFormat::generateAddress($invoice_address, array(), '<br />', ' ');
        }

        $carrier = new Carrier($this->order->id_carrier);
        $carrier->name = ($carrier->name == '0' ? Configuration::get('PS_SHOP_NAME') : $carrier->name);

        $order_details = $this->order_invoice->getProducts();
        if (Configuration::get('PS_PDF_IMG_DELIVERY')) {
            foreach ($order_details as &$order_detail) {
                if ($order_detail['image'] != null) {
                    $name = 'product_mini_' . (int)$order_detail['product_id'] . (isset($order_detail['product_attribute_id']) ? '_' . (int)$order_detail['product_attribute_id'] : '') . '.jpg';
                    $path = _PS_PROD_IMG_DIR_ . $order_detail['image']->getExistingImgPath() . '.jpg';

                    $order_detail['image_tag'] = preg_replace(
                        '/\.*' . preg_quote(__PS_BASE_URI__, '/') . '/',
                        _PS_ROOT_DIR_ . DIRECTORY_SEPARATOR,
                        ImageManager::thumbnail($path, $name, 45, 'jpg', false),
                        1
                    );

                    if (file_exists(_PS_TMP_IMG_DIR_ . $name)) {
                        $order_detail['image_size'] = getimagesize(_PS_TMP_IMG_DIR_ . $name);
                    } else {
                        $order_detail['image_size'] = false;
                    }
                }
            }
        }
        $trackID = DB::getInstance()->ExecuteS('SELECT p.id_track FROM `' . _DB_PREFIX_ . 'orders` o left join ' . _DB_PREFIX_ . 'pakettikauppa p on o.id_cart=p.id_cart WHERE o.id_order=31');
        $this->track_id = $trackID[0]['id_track'];
        $this->smarty->assign(array(
            'order' => $this->order,
            'order_details' => $order_details,
            'delivery_address' => $formatted_delivery_address,
            'invoice_address' => $formatted_invoice_address,
            'order_invoice' => $this->order_invoice,
            'carrier' => $carrier,
            'track_id' => $this->track_id,
            'available_in_your_account' => true,
            'display_product_images' => Configuration::get('PS_PDF_IMG_DELIVERY')
        ));

        $tpls = array(
            'style_tab' => $this->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/pdf/delivery-slip.style-tab.tpl'),
            'addresses_tab' => $this->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/pdf/delivery-slip.addresses-tab.tpl'),
            'summary_tab' => $this->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/pdf/delivery-slip.summary-tab.tpl'),
            'product_tab' => $this->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/pdf/delivery-slip.product-tab.tpl'),
            'payment_tab' => $this->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/pdf/delivery-slip.payment-tab.tpl'),
        );
        $this->smarty->assign($tpls);

        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/pdf/delivery-slip.tpl');
    }

    public function getFooter()
    {
        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/pdf/footer.tpl');
    }

    /**
     * Returns the template filename when using bulk rendering
     *
     * @return string filename
     */
    public function getBulkFilename()
    {
        return 'deliveries.pdf';
    }

    /**
     * Returns the template filename
     *
     * @return string filename
     */
    public function getFilename()
    {
        return Configuration::get('PS_DELIVERY_PREFIX', Context::getContext()->language->id, null, $this->order->id_shop) . sprintf('%06d', $this->order->delivery_number) . '.pdf';
    }
}
