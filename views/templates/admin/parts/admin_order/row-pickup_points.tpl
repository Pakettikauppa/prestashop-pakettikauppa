<div class="row pk-row-pickup">
  <div class="col-12 col-lg-6 pk-col-current_pickup">
    {include file="{$template_parts_path}/field-title.tpl" title="{l s='Selected pickup point' mod='pakettikauppa'}"}

    <div id="current_pickup_point" class="col-lg-8">
      <span class="provider">{$selected_pickup_point->provider}</span>
      <span class="name">{$selected_pickup_point->name}</span>
      <span class="address">{$selected_pickup_point->street_address}, {$selected_pickup_point->city}, {$selected_pickup_point->postcode}, {$selected_pickup_point->country}</span>
    </div>
    <div id="updated_pickup_point" class="col-lg-8" style="display:none;">
      <span class="provider">{l s='Need to reload the page to see the information for the selected pickup point' mod='pakettikauppa'}</span>
    </div>
  </div>
  <div class="col-12 col-lg-6 pk-col-new_pickup">
    {include file="{$template_parts_path}/field-title.tpl" title="{l s='Change pickup point' mod='pakettikauppa'}"}

    <div class="col-lg-8">
      <select class="custom-select pickup_point_list" name="new_pickup_point">
        <option value="{$selected_pickup_point->pickup_point_id}" selected>
          {$selected_pickup_point->name} ({$selected_pickup_point->street_address}, {$selected_pickup_point->city})
        </option>
        {foreach from=$pickup_points item=point}
          {if $point->pickup_point_id == $selected_pickup_point->pickup_point_id}
            {continue}
          {/if}
          <option value="{$point->pickup_point_id}">
            {$point->name} ({$point->street_address}, {$point->city})
          </option>
        {/foreach}
      </select>
    </div>
  </div>
</div>