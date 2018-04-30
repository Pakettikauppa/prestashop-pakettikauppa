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
class HTMLTemplatePakiShipping extends HTMLTemplate
{
    public $custom_model;

    public function __construct($custom_object, $smarty)
    {

        $this->custom_model = $custom_object;

        //$context = Context::getContext();
        $this->smarty = $smarty;
        // header informations
        $id_lang = Context::getContext()->language->id;
        $this->title = HTMLTemplatePakiShipping::l('Pakettikauppa Shipping');
        // footer informations
        $this->shop = new Shop(Context::getContext()->shop->id);
    }

    /**
     * Returns the template's HTML content
     * @return string HTML content
     */

    public function getContent()
    {


        $this->smarty->assign(array(
            'demo' => "demo",
        ));

        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/pdf/content.tpl');
    }

    public function getHeader()
    {
        $shop_name = Configuration::get('PS_SHOP_NAME');
        $path_logo = $this->getLogo();
        $width = $height = 0;


        if (!empty($path_logo))
            list($width, $height) = getimagesize($path_logo);

        $this->smarty->assign(array(
            'logo_path' => $path_logo,
            'img_ps_dir' => 'http://' . Tools::getMediaServer(_PS_IMG_) . _PS_IMG_,
            'img_update_time' => Configuration::get('PS_IMG_UPDATE_TIME'),
            'title' => $this->title,
            'date' => $this->date,
            'shop_name' => $shop_name,
            'width_logo' => $width,
            'height_logo' => $height,
            'data' => $this->custom_model

        ));
        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/pdf/header.tpl');
    }

    /**
     * Returns the template filename
     * @return string filename
     */
    protected function getLogo()
    {
        $logo = '';

        $physical_uri = Context::getContext()->shop->physical_uri . 'img/';


        $logo = _PS_IMG_DIR_ . Configuration::get('PS_LOGO', null, null, (int)Shop::getContextShopID());
        return $logo;
    }


    public function getFooter()
    {
        return $this->smarty->fetch(_PS_MODULE_DIR_ . 'pakettikauppa/views/templates/pdf/footer.tpl');
    }

    /**
     * Returns the template filename
     * @return string filename
     */
    public function getFilename()
    {
        return 'Pakettikauppa_shipping.pdf';
    }

    /**
     * Returns the template filename when using bulk rendering
     * @return string filename
     */
    public function getBulkFilename()
    {
        return 'Pakettikauppa_shipping.pdf';
    }
}


