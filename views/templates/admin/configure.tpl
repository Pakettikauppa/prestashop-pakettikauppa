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

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Select Warehouse' mod='pakettikauppa'}
                </label>
                <div class="col-lg-9">
                    <select name="id_warehouse" class=" fixed-width-xl" id="id_warehouse">
                        {foreach $warehouses as $warehouse}
                            <option value="{$warehouse.id_warehouse}">{$warehouse.name}</option>
                        {/foreach}
                    </select>
                    <p class="help-block">
                        {l s='Select Warehouse to assign Pakettikauppa Carriers' mod='pakettikauppa'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="Associated carriers">
                        {l s='Carriers' mod='pakettikauppa'}
                    </span>
                </label>
                <div class="col-lg-9">

                    <div class="form-group">
                        <div class="col-lg-9">
                            <div class="form-control-static row">

                                <div class="col-xs-6">
                                    <select class="" id="availableSwap" name="ids_carriers_available[]" multiple="multiple">
                                        {foreach $carriers as $carrier}
                                            <option value="{$carrier.id_reference}">{$carrier.name}</option>
                                        {/foreach}
                                    </select>
                                    <a href="#" id="addSwap" class="btn btn-default btn-block">{l s='Add' mod='pakettikauppa'} <i class="icon-arrow-right"></i></a>
                                </div>

                                <div class="col-xs-6">
                                    <select class="" id="selectedSwap" name="ids_carriers_selected[]" multiple="multiple">
                                        {foreach $selected_carriers as $selected_carrier}
                                            <option value="{$selected_carrier.id_carrier}">{$selected_carrier.name}</option>
                                        {/foreach}
                                    </select>
                                    <a href="#" id="removeSwap" class="btn btn-default btn-block"><i class="icon-arrow-left"></i> {l s='Remove' mod='pakettikauppa'}</a>
                                </div>

                            </div>
                        </div>
                    </div>

                    <p class="help-block">
                        {l s='If no carrier is selected, no carrier will be show on order shipping method. Use CTRL+Click to select more than one carrier.' mod='pakettikauppa'}
                    </p>

                </div>
            </div>
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
