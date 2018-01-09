{*
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
*  @author    Pakettikauppa <asiakaspalvelu@pakettikauppa.fi>
*  @copyright 2017- Pakettikauppa Oy
*  @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  
*}

{* Generate HTML code for printing Invoice Icon with link *}
<span class="btn-group-action">
	<span class="btn-group">
	{* Generate HTML code for printing Delivery Icon with link *}
	
		<a class="btn btn-default _blank" href="{$link->getAdminLink('AdminPakettikauppa')|escape:'html':'UTF-8'}&amp;submitAction=generateShippingSlipPDF&amp;id_cart={$order}">
			<i class="icon-file-text"></i>
		</a>
	
	</span>
</span>
