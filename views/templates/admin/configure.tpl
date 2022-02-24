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

<form id="module_form" class="defaultForm form-horizontal"
      action="index.php?controller=AdminModules&amp;configure=pakettikauppa&amp;tab_module=shipping_logistics&amp;module_name=pakettikauppa&amp;token={$token}"
      method="post" enctype="multipart/form-data" novalidate="">
    <div class="panel" id="fieldset_0">

        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='API settings' mod='pakettikauppa'}
        </div>

        <div class="form-wrapper">

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='API key' mod='pakettikauppa'}
                </label>
                <div class="col-lg-6">
                    <input type="text" name="api_key" value="{Configuration::get('PAKETTIKAUPPA_API_KEY')}"/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='API secret' mod='pakettikauppa'}
                </label>
                <div class="col-lg-6">
                    <input type="text" name="secret" value="{Configuration::get('PAKETTIKAUPPA_SECRET')}"/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Mode' mod='pakettikauppa'}
                </label>
                <div class="col-lg-6">
                    <select name="modes" onchange="alert('CAUTION! Mode change will delete all existing Pakettikauppa carriers');">
                        <option value="1" {if Configuration::get('PAKETTIKAUPPA_MODE')==1}selected{/if}>{l s='Test mode' mod='pakettikauppa'}</option>
                        <option value="0" {if Configuration::get('PAKETTIKAUPPA_MODE')==0}selected{/if}>{l s='Production mode' mod='pakettikauppa'}</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                </label>
                <div class="col-lg-9">
                    <div class="form-group">
                        <div class="col-lg-9">
                            <div class="form-control-static row">
                                Saving the settings in this section creates the missing carriers
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.form-wrapper -->

        <div class="panel-footer">
            <button type="submit" value="1" id="module_form_submit_btn" name="submitPakettikauppaAPI"
                class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>

    </div>
</form>

<form id="module_form" class="defaultForm form-horizontal"
      action="index.php?controller=AdminModules&amp;configure=pakettikauppa&amp;tab_module=shipping_logistics&amp;module_name=pakettikauppa&amp;token={$token}"
      method="post" enctype="multipart/form-data" novalidate="">
    <div class="panel" id="fieldset_0">

        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Sender address' mod='pakettikauppa'}
        </div>

        <div class="form-wrapper">

            <div class="form-group">
                <label class="control-label col-lg-3">
                    Store Name
                </label>
                <div class="col-lg-6">
                    <input type="text" name="store_name" value="{Configuration::get('PAKETTIKAUPPA_STORE_NAME')}"/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    Address
                </label>
                <div class="col-lg-6">
                    <input type="text" name="address" value="{Configuration::get('PAKETTIKAUPPA_STORE_ADDRESS')}"/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    Post code
                </label>
                <div class="col-lg-6">
                    <input type="text" name="postcode" value="{Configuration::get('PAKETTIKAUPPA_POSTCODE')}"/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    City
                </label>
                <div class="col-lg-6">
                    <input type="text" name="city" value="{Configuration::get('PAKETTIKAUPPA_CITY')}"/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    Phone
                </label>
                <div class="col-lg-6">
                    <input type="text" name="phone" value="{Configuration::get('PAKETTIKAUPPA_PHONE')}"/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    Country
                </label>
                <div class="col-lg-6">
                    <select name="country">
                        {foreach $countries as $country}
                            {if Configuration::get('PAKETTIKAUPPA_COUNTRY')== $country.iso_code}
                                <option value="{$country.iso_code}" selected="true">{$country.country}</option>
                            {else}
                                <option value="{$country.iso_code}">{$country.country}</option>
                            {/if}
                        {/foreach}
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    Vat Code
                </label>
                <div class="col-lg-6">
                    <input type="text" name="vat_code" value="{Configuration::get('PAKETTIKAUPPA_VATCODE')}"/>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">

                </label>
                <div class="col-lg-9">
                    <div class="form-group">
                        <div class="col-lg-9">
                            <div class="form-control-static row">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.form-wrapper -->

        <div class="panel-footer">
            <button type="submit" value="1" id="module_form_submit_btn" name="submitPakettikauppaSender"
                    class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>

    </div>
</form>

<form id="module_form" class="defaultForm form-horizontal"
      action="index.php?controller=AdminModules&amp;configure=pakettikauppa&amp;tab_module=shipping_logistics&amp;module_name=pakettikauppa&amp;token={$token}"
      method="post" enctype="multipart/form-data" novalidate="">
    <div class="panel" id="fieldset_0">
        
        <div class="panel-heading">
            <i class="icon-cogs"></i> Checkout settings
        </div>

        <div class="form-wrapper">
            
            <div class="form-group">
                <label class="control-label col-lg-3"></label>
                <div class="col-lg-9">
                    <p>List of pickup-point providers: activate, shipping price, trigger price, triggered price</p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Number of pickup points' mod='pakettikauppa'}
                </label>

                <div class="col-lg-6">
                    {assign var="current_value" value="{Configuration::get('PAKETTIKAUPPA_MAX_PICKUPS')}"}
                    {if empty($current_value)}
                        {assign var="current_value" value="5"}
                    {/if}
                    <select class="form-control fixed-width-xs" name="pickup_points_count">
                        {for $i=1 to $max_pickup}
                            <option value="{$i}" {if $current_value == $i}selected{/if}>{$i}</option>
                        {/for}
                    </select>

                    <p class="help-block">
                        {l s='How many pickup points are shown.' mod='pakettikauppa'}
                    </p>
                </div>
            </div>

        </div>

        <div class="panel-footer">
            <button type="submit" value="1" id="module_form_submit_btn" name="submitPakettikauppaFront"
                    class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>

    </div>
</form>

{if $warehouses}
<form id="module_form" class="defaultForm form-horizontal" method="post" enctype="multipart/form-data"
      action="index.php?controller=AdminModules&amp;configure=pakettikauppa&amp;tab_module=shipping_logistics&amp;module_name=pakettikauppa&amp;token={$token}" novalidate="">

    <input name="submitPakettikauppaModule" value="1" type="hidden">
                
    <div class="panel" id="fieldset_0">

        <div class="panel-heading">
            <i class="icon-cogs"></i> Configure Warehouse & Carrier
        </div>
 
        <div class="form-wrapper">

            <div class="form-group">
                <label class="control-label col-lg-3">
                    Select Warehouse
                </label>
                <div class="col-lg-9">
                    <select name="id_warehouse" class=" fixed-width-xl" id="id_warehouse">
                        {foreach $warehouses as $warehouse}
                            <option value="{$warehouse.id_warehouse}">{$warehouse.name}</option>
                        {/foreach}
                    </select>
                    <p class="help-block">
                        Select Warehouse to assign Pakettikauppa Carriers
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="Associated carriers">
                        Carriers
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
                                    <a href="#" id="addSwap" class="btn btn-default btn-block">Add <i class="icon-arrow-right"></i></a>
                                </div>

                                <div class="col-xs-6">
                                    <select class="" id="selectedSwap" name="ids_carriers_selected[]" multiple="multiple">
                                        {foreach $selected_carriers as $selected_carrier}
                                            <option value="{$selected_carrier.id_carrier}">{$selected_carrier.name}</option>
                                        {/foreach}
                                    </select>
                                    <a href="#" id="removeSwap" class="btn btn-default btn-block"><i class="icon-arrow-left"></i> Remove</a>
                                </div>

                            </div>
                        </div>
                    </div>

                    <p class="help-block">
                        If no carrier is selected, no carrier will be show on order shipping method. Use CTRL+Click to select more than one carrier.
                    </p>

                </div>
            </div>

        </div><!-- /.form-wrapper -->

        <div class="panel-footer">
            <button type="submit" value="1" id="module_form_submit_btn" name="submitPakettikauppaModule" class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>

    </div>
</form>
{/if}

<form id="module_form" class="defaultForm form-horizontal"
      action="index.php?controller=AdminModules&amp;configure=pakettikauppa&amp;tab_module=shipping_logistics&amp;module_name=pakettikauppa&amp;token={$token}"
      method="post" enctype="multipart/form-data" novalidate="">
    <div class="panel" id="fieldset_0">

        <div class="panel-heading">
            <i class="icon-cogs"></i> {l s='Labels generation' mod='pakettikauppa'}
        </div>

        <div class="form-wrapper">

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Automatically generate when state' mod='pakettikauppa'}
                </label>
                <div class="col-lg-9">
                    <select name="shipping_state" class=" fixed-width-xl" id="order_state">
                        <option value="">--- {l s='Select order state' mod='pakettikauppa'} ---</option>
                        {foreach $order_statuses as $order_statuse}
                            {if $order_statuse.id_order_state==$shipping_state}
                                <option value="{$order_statuse.id_order_state}"
                                        selected="true">{$order_statuse.name}</option>
                            {else}
                                <option value="{$order_statuse.id_order_state}">{$order_statuse.name}</option>
                            {/if}
                        {/foreach}
                    </select>
                    
                    <p class="help-block">
                        {l s='Order state on which you want automatically generate shipment.' mod='pakettikauppa'}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {l s='Add comment on labels' mod='pakettikauppa'}
                </label>
                <div class="col-lg-9">
                    <textarea id="label_comment" name="label_comment">{Configuration::get('PAKETTIKAUPPA_LABEL_COMMENT')}</textarea>

                    <p class="help-block">
                        {l s='Available variables' mod='pakettikauppa'}:
                        {foreach from=$label_comment_variables key=variable item=title}
                            <span class="variable_row clickable noselect" data-for="label_comment">
                                <code>{$variable}</code> - {$title}
                            </span>
                        {/foreach}
                    </p>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label col-lg-3">
                    {* EMPTY *}
                </label>
                <div class="col-lg-9">
                    <div class="form-group">
                        <div class="col-lg-9">
                            <div class="form-control-static row">
                                {* EMPTY *}
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div><!-- /.form-wrapper -->

        <div class="panel-footer">
            <button type="submit" value="1" id="module_form_submit_btn" name="submitPakettikauppaShippingLabels"
                    class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>

    </div>
</form>

<script>
    {ldelim} var module_dir = '{$module_dir}'; {rdelim}
</script>
