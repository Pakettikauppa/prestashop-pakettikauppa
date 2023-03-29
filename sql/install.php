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

$sql = array();

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'pakettikauppa` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT "ID in this table",
  `id_cart` int(10) unsigned NOT NULL COMMENT "Row ID in cart table",
  `id_pickup_point` varchar(20) NOT NULL COMMENT "Pakettikauppa pickup point ID",
  `id_provider` int(10) NOT NULL COMMENT "Pakettikauppa provider ID",
  `id_track` varchar(50) NOT NULL COMMENT "Shipment tracking number",
  `shipping_method_code` int(11) NOT NULL COMMENT "PS carrier ID",
  PRIMARY KEY (`id`),
  UNIQUE (`id_cart`)
) ENGINE=' . _MYSQL_ENGINE_ . '  DEFAULT CHARSET=utf8 AUTO_INCREMENT=21;';


foreach ($sql as $query) {
    if (Db::getInstance()->execute($query) == false) {
        return false;
    }
}
