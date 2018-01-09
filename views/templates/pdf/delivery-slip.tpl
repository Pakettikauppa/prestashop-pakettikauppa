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

{$style_tab}


<table width="100%" id="body" border="0" cellpadding="0" cellspacing="0" style="margin:0;">
	<!-- Addresses -->
	<tr>
		<td colspan="12" style="font-size:5pt">

		{$addresses_tab}

		</td>
	</tr>

	<tr>
		<td colspan="12" height="1">&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan="12" style="font-size:9pt">

		{$summary_tab}

		</td>
	</tr>

	<tr>
		<td colspan="12" height="1">&nbsp;</td>
	</tr>

	<!-- Products -->
	<tr>
		<td colspan="12" style="font-size:9pt">

		{$product_tab}

		</td>
	</tr>

	<tr>
		<td colspan="12" height="1">&nbsp;</td>
	</tr>
	
	<tr>
		<td colspan="7" class="left" style="font-size:9pt">

			{$payment_tab}

		</td>
		<td colspan="5">&nbsp;</td>
	</tr>

	<!-- Hook -->
	{if isset($HOOK_DISPLAY_PDF)}
	<tr>
		<td colspan="12" height="1">&nbsp;</td>
	</tr>

	<tr>
		<td colspan="2">&nbsp;</td>
		<td colspan="10">
			{$HOOK_DISPLAY_PDF}
		</td>
	</tr>
	{/if}

</table>
