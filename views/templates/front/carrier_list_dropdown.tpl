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

<div class="row box pakettikauppa-extracarrier ps{$version} style-dropdown carrier-{$id_carrier} {$class_has_pp}" style="display:{$display}" data-carrier="{$id_carrier}">

  <div class="col-md-12 pickups_search_holder">
    <input id="pickup_code_{$id_carrier}" type="text"  name="pickup_code" class="text form-control inline ac_input" value="{$current_postcode}" placeholder="{l s='Postcode' mod='pakettikauppa'}" />
    <input id="check_{$id_carrier}" class="button btn btn-outline" type="button" value="{l s='Search by postcode' mod='pakettikauppa'}" onclick="pk_search_pickup({$id_carrier})"/>
    <input type="hidden" id="id_carts_{$id_carrier}" value="{$id_cart}"/>
  </div>
  
  <div class="col-md-12 loader_holder">
    <div class="loader"></div>
  </div>

  <input type="hidden" id="method_code_{$id_carrier}" name="pk_method_code" value="{$selected_method}"/>
  
  <div id="pickuppoints_{$id_carrier}" class="col-md-12 pickups_table_holder pickups_style-dropdown">
    {if $pick_up_points|@count == 0}
      {l s='There is no any pickup points near your address' mod='pakettikauppa'}
    {else}
      <select id="list-{$id_carrier}" class="pk_dropdown" onchange="pk_select_pickup_point(this.value)" data-params="name,address,distance">
        <option value="" {if !$selected_point}selected{/if}>-- {l s='Please select pickup point' mod='pakettikauppa'} --</option>
        {foreach $pick_up_points as $pick_up_point}
          {assign var="selected" value=false}
          {if $pick_up_point->pickup_point_id == $selected_point}
            {assign var="selected" value=true}
          {/if}
          <option value="{$pick_up_point->pickup_point_id}" {if $selected === true}selected{/if} data-name="{$pick_up_point->name}" data-address="{$pick_up_point->street_address}, {$pick_up_point->city}" data-distance="{$pick_up_point->distance} m.">{$pick_up_point->name}</option>
        {/foreach}
      </select>
    {/if}
  </div>

</div>

<script>
  var pk_template_style = "dropdown";

  {if $version === '16'}
    $(document).ready(function() {
      pk_change_dropdowns();
    });
  {/if}
</script>
