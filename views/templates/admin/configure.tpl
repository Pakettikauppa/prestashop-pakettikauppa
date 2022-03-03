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

{include file="{$template_parts_path}/section-header.tpl" title="{l s='API settings' mod='pakettikauppa'}" icon="icon-key"}
    {foreach from=$fields['api'] item=field}
        {include file="{$template_parts_path}/field-{$field['tpl']}.tpl"}
    {/foreach}
{include file="{$template_parts_path}/section-footer.tpl" name="submitPakettikauppaAPI" button="{l s='Save' mod='pakettikauppa'}"}

{include file="{$template_parts_path}/section-header.tpl" title="{l s='Sender address' mod='pakettikauppa'}" icon="icon-home"}
    {foreach from=$fields['store'] item=field}
        {include file="{$template_parts_path}/field-{$field['tpl']}.tpl"}
    {/foreach}
{include file="{$template_parts_path}/section-footer.tpl" name="submitPakettikauppaSender" button="{l s='Save' mod='pakettikauppa'}"}

{include file="{$template_parts_path}/section-header.tpl" title="{l s='Checkout settings' mod='pakettikauppa'}" icon="icon-shopping-cart"}
    {foreach from=$fields['front'] item=field}
        {include file="{$template_parts_path}/field-{$field['tpl']}.tpl"}
    {/foreach}
{include file="{$template_parts_path}/section-footer.tpl" name="submitPakettikauppaFront" button="{l s='Save' mod='pakettikauppa'}"}

{if $warehouses}
    {include file="{$template_parts_path}/section-header.tpl" title="{l s='Configure Warehouse & Carrier' mod='pakettikauppa'}" icon="icon-archive"}
        <input name="submitPakettikauppaModule" value="1" type="hidden">
        {foreach from=$fields['warehouses'] item=field}
            {include file="{$template_parts_path}/field-{$field['tpl']}.tpl"}
        {/foreach}
    {include file="{$template_parts_path}/section-footer.tpl" name="submitPakettikauppaModule" button="{l s='Save' mod='pakettikauppa'}"}
{/if}

{include file="{$template_parts_path}/section-header.tpl" title="{l s='Labels generation' mod='pakettikauppa'}" icon="icon-file-alt"}
    {foreach from=$fields['labels'] item=field}
        {include file="{$template_parts_path}/field-{$field['tpl']}.tpl"}
    {/foreach}
{include file="{$template_parts_path}/section-footer.tpl" name="submitPakettikauppaShippingLabels" button="{l s='Save' mod='pakettikauppa'}"}

<script>
    {ldelim} var module_dir = '{$module_url}'; {rdelim}
</script>
