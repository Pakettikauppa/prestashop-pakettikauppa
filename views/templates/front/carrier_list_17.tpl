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

<div class="row box pakettikauppa-extracarrier ps17 carrier-{$id_carrier} {$class_has_pp}" style="display:{$display}" data-carrier="{$id_carrier}">
  
  <div class="col-md-12">
    <p class="carrier_title">{l s='Search Pickup Points' mod='pakettikauppa'}</p>
  </div>

  <div class="col-md-12 form-group pickups_search_holder">
    <input id="pickup_code_{$id_carrier}" type="text"  name="pickup_code" class="text form-control inline ac_input" value="{$current_postcode}" placeholder="{l s='Postcode' mod='pakettikauppa'}" />
    <input id="check_{$id_carrier}" class="button btn btn-outline " type="button" value="{l s='Search by postcode' mod='pakettikauppa'}" onclick="pk_search_pickup({$id_carrier})"/>
    <input type="hidden" id="id_carts_{$id_carrier}" value="{$id_cart}"/>
  </div>
  
  <div class="col-md-12 loader_holder">
    <div class="loader"></div>
  </div>

  <input type="hidden" id="method_code_{$id_carrier}" name="pk_method_code" value="{$selected_method}"/>
  
  <div id="pickuppoints_{$id_carrier}" class="col-md-12 pickups_table_holder">
    {if $pick_up_points|@count == 0}
      {l s='There is no any pickup points near your address' mod='pakettikauppa'}
    {/if}

    <table class="table-pickups">
      {foreach $pick_up_points as $pick_up_point}
        {assign var="selected" value=false}
        {if $pick_up_point->pickup_point_id == $selected_point }
          {assign var="selected" value=true}
        {/if}
        <tr class="row-bordered clickable">
          <td class="column-radio"><input type="radio" name="id_pick_up_point" value="{$pick_up_point->pickup_point_id}" onclick="pk_select_pickpup_point(this.value)" {if $selected === true}checked{/if}/></td>
          <td class="column-img"><img src="{$pick_up_point->provider_logo}" height="100%" width="100%"/></td>
          <td class="column-desc">
            <table class="table-desc">
              <tr>
                <td>
                  <div class="desc-row">
                    <span class="desc-title">{l s='Name' mod='pakettikauppa'}:</span> {$pick_up_point->name}
                  </div>
                  <div class="desc-row">
                    <span class="desc-title">{l s='Description' mod='pakettikauppa'}:</span> {$pick_up_point->description}
                  </div>
                  <div class="desc-row">
                    <span class="desc-title">{l s='Address' mod='pakettikauppa'}:</span> {$pick_up_point->street_address}, {$pick_up_point->city}
                  </div>
                  <div class="desc-row">
                    <span class="desc-title">{l s='Distance' mod='pakettikauppa'}:</span> {$pick_up_point->distance} m.
                  </div>
                </td>
              </tr>
            </table>
          </td>
        </tr>
      {/foreach}
    </table>
  </div>

</div>

<script>
  var pickup_template_{$id_carrier} = `<tr class="added_from_template row-bordered clickable">
      <td class="column-radio">
        <div class="radio"><span class="[checked]"><input type="radio" name="id_pick_up_point" value="[point_id]" onclick="pk_select_pickpup_point(this.value)" [checked]/></span></div>
      </td>
      
      <td class="column-img">
        <img src="[logo]" height="100%" width="100%"/>
      </td>

      <td class="column-desc">
        <table class="table-desc">
          <tr>
            <td>
              <div class="desc-row">
                <span class="desc-title">{l s='Name' mod='pakettikauppa'}:</span> [name]
              </div>
              <div class="desc-row">
                <span class="desc-title">{l s='Description' mod='pakettikauppa'}:</span> [description]
              </div>
              <div class="desc-row">
                <span class="desc-title">{l s='Address' mod='pakettikauppa'}:</span> [street], [city]
              </div>
              <div class="desc-row">
                <span class="desc-title">{l s='Distance' mod='pakettikauppa'}:</span> [distance] m.
              </div>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  `;
</script>

<script>
  var pakettikauppa_ajax = '{$ajax_url}';
</script>
