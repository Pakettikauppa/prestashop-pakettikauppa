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

require_once(dirname(__FILE__) . '../../../config/config.inc.php');
require_once(dirname(__FILE__) . '../../../init.php');

require_once('vendor/pakettikauppa/api-library/src/Pakettikauppa/Client.php');

switch (Tools::getValue('action')) {
    case 'FetchData' :
        $selected_carriers = DB::getInstance()->ExecuteS('SELECT wc.`id_carrier`,c.name FROM `' . _DB_PREFIX_ . 'warehouse_carrier` wc inner join ' . _DB_PREFIX_ . 'carrier c on wc.`id_carrier`=c.`id_carrier` WHERE wc.`id_warehouse`=' . Tools::getValue('id_warehouse'));
        $carriers = DB::getInstance()->ExecuteS("SELECT `id_reference`,`name` FROM `" . _DB_PREFIX_ . "carrier` WHERE is_module=1 and `external_module_name`='pakettikauppa' and deleted=0 and `id_reference` not in (SELECT wc.`id_carrier` FROM `" . _DB_PREFIX_ . "warehouse_carrier` wc inner join " . _DB_PREFIX_ . "carrier c on wc.`id_carrier`=c.`id_carrier` WHERE wc.`id_warehouse`=" . Tools::getValue('id_warehouse') . ")");

        echo json_encode(array('selected_carriers' => $selected_carriers, 'carriers' => $carriers));

        break;

    case 'searchPickUpPoints':

        if (Configuration::get('PAKETTIKAUPPA_COUNTRY') == 1) {
            $client = new \Pakettikauppa\Client(array('test_mode' => true));
        } else {
            $client = new \Pakettikauppa\Client(array('api_key' => Configuration::get('PAKETTIKAUPPA_API_KEY'), 'api_secret' => Configuration::get('PAKETTIKAUPPA_SECRET')));
        }

        $result = $client->searchPickupPoints(Tools::getValue('postcode'));

        echo $result;

        break;

    case 'selectPickUpPoints':

        $result = DB::getInstance()->Execute('INSERT INTO ' . _DB_PREFIX_ . 'pakettikauppa (id_cart,id_pickup_point,id_track,shipping_method_code) VALUES(' . Tools::getValue('id_cart') . ', ' . Tools::getValue('code') . ', 0,' . rtrim(Tools::getValue('shipping_method_code'), ',') . ') ON DUPLICATE KEY UPDATE id_pickup_point=' . Tools::getValue('code') . ', id_track="", shipping_method_code=' . rtrim(Tools::getValue('shipping_method_code'), ','));

        if ($result) {
            echo true;
        }

        break;
}
