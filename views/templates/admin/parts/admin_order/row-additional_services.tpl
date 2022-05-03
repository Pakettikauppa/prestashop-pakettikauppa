<div class="row pk-row-services">
  <div class="col-lg-12">
    {include file="{$template_parts_path}/field-title.tpl" title="{l s='Additional services' mod='pakettikauppa'}" width="2"}
    <div class="col-lg-8">
      {if empty($all_additional_services)}
        <span class="provider">{l s='This shipping method not have additional services' mod='pakettikauppa'}.</span>
      {/if}
      {foreach from=$all_additional_services key=code item=service}
        {assign var=disabled value=false}
        {assign var=have_param value=false}
        {assign var=info_param value=''}
        {if $code == 3101}
          {if $payment_is_cod}
            {*assign var=disabled value=true*} {* Disabled, because not needed this *}
          {/if}
          {assign var=have_param value=true}
          {assign var=info_param value="(`$order_amount` `$currency`)"}
        {/if}
        {if $code == 3102}
          {assign var=have_param value=true}
        {/if}
        {if $code == 3143}
          {assign var=have_param value=true}
          {assign var=info_param value="(`$dangerous_goods['weight']` `$weight_unit`)"}
        {/if}
        <div class="row">
          <div class="service_cb">
            <input id="service_{$code}" type="checkbox" name="additional_services[]" value="{$code}" {if isset($selected_additional_services[$code])}checked{/if} {if $disabled}disabled{/if}>
            {if $disabled}
              <input type="hidden" name="additional_services[]" value="{$code}">
            {/if}
            <label for="service_{$code}" class="noselect">
              {$service->name}
            </label>
            <span class="service_info" title="{l s='Value from order' mod='pakettikauppa'}">{$info_param}</span>
          </div>
          {if $have_param}
            {assign var=param_title value=''}
            {assign var=param_value value=''}
            {assign var=param_type value=''}
            {assign var=param_min value=''}
            {assign var=param_max value=''}
            {assign var=param_step value=''}
            {assign var=param_unit value=''}

            {if $code == 3101}
              {assign var=param_title value={l s='C.O.D. amount' mod='pakettikauppa'}}
              {assign var=param_value value=$order_amount}
              {assign var=param_type value='number'}
              {assign var=param_min value='0'}
              {assign var=param_step value='0.01'}
              {assign var=param_unit value=$currency}
            {elseif $code == 3102}
              {assign var=param_title value={l s='Packages number' mod='pakettikauppa'}}
              {assign var=param_value value=1}
              {assign var=param_type value='number'}
              {assign var=param_min value='1'}
              {assign var=param_step value='1'}
            {elseif $code == 3143}
              {assign var=param_title value={l s='DG weight' mod='pakettikauppa'}}
              {assign var=param_value value=$dangerous_goods['weight']}
              {assign var=param_type value='number'}
              {assign var=param_min value='0'}
              {assign var=param_step value='0.001'}
              {assign var=param_unit value=$weight_unit}
            {/if}
            
            {if !empty($selected_additional_services[$code])}
              {assign var=param_value value=$selected_additional_services[$code]}
            {/if}

            <div class="service_param">
              <label for="service_{$code}_param">
                {$param_title}:
              </label>
              {if $param_type === 'number'}
                <input id="service_{$code}_param" type="number" class="fixed-width-sm" name="services_params[{$code}]" value="{$param_value}" min="{$param_min}" max="{$param_max}" step="{$param_step}"/> {$param_unit}
              {/if}
            </div>
          {/if}
        </div>
      {/foreach}
    </div>
  </div>
</div>