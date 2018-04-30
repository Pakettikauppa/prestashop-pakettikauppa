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
class PDFS
{
    public $filename;
    public $pdf_renderer;
    public $objects;
    public $template;
    public $send_bulk_flag = false;

    const TEMPLATE_INVOICE = 'Invoice';
    const TEMPLATE_ORDER_RETURN = 'OrderReturn';
    const TEMPLATE_ORDER_SLIP = 'OrderSlip';
    const TEMPLATE_DELIVERY_SLIP = 'DeliverySlip';
    const TEMPLATE_SUPPLY_ORDER_FORM = 'SupplyOrderForm';

    /**
     * @param $objects
     * @param $template
     * @param $smarty
     * @param string $orientation
     */
    public function __construct($objects, $template, $smarty, $orientation = 'P')
    {
        $this->pdf_renderer = new PDFGenerators((bool)Configuration::get('PS_PDF_USE_CACHE'), $orientation);
        $this->template = $template;
        $this->smarty = $smarty;

        $this->objects = $objects;
        if (!($objects instanceof Iterator) && !is_array($objects)) {
            $this->objects = array($objects);
        }

        if (count($this->objects) > 1) { // when bulk mode only
            $this->send_bulk_flag = true;
        }
    }

    /**
     * Render PDF
     *
     * @param bool $display
     * @return mixed
     * @throws PrestaShopException
     */
    public function render($display = true)
    {
        $render = false;
        $this->pdf_renderer->setFontForLang(Context::getContext()->language->iso_code);
        foreach ($this->objects as $object) {
            $this->pdf_renderer->startPageGroup();
            $template = $this->getTemplateObject($object);
            if (!$template) {
                continue;
            }

            if (empty($this->filename)) {
                $this->filename = $template->getFilename();
                if (count($this->objects) > 1) {
                    $this->filename = $template->getBulkFilename();
                }
            }

            $template->assignHookData($object);

            $this->pdf_renderer->createHeader($template->getHeader());
            $this->pdf_renderer->createFooter($template->getFooter());
            $this->pdf_renderer->createPagination($template->getPagination());
            $this->pdf_renderer->createContent($template->getContent());
            $this->pdf_renderer->writePage();
            $render = true;

            unset($template);
        }

        if ($render) {
            // clean the output buffer
            if (ob_get_level() && ob_get_length() > 0) {
                ob_clean();
            }
            return $this->pdf_renderer->render($this->filename, $display);
        }
    }

    /**
     * Get correct PDF template classes
     *
     * @param mixed $object
     * @return HTMLTemplate|false
     * @throws PrestaShopException
     */
    public function getTemplateObject($object)
    {
        $class = false;
        $class_name = 'HTMLTemplate' . $this->template;

        if (class_exists($class_name)) {
            // Some HTMLTemplateXYZ implementations won't use the third param but this is not a problem (no warning in PHP),
            // the third param is then ignored if not added to the method signature.
            $class = new $class_name($object, $this->smarty, $this->send_bulk_flag);

            if (!($class instanceof HTMLTemplate)) {
                throw new PrestaShopException('Invalid class. It should be an instance of HTMLTemplate');
            }
        }

        return $class;
    }
}
