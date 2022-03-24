{if !empty($additional_services)}
  <input type="hidden" id="order_{$order_id}_cart_id" value="{$cart_id}"/>
  <table class="table additional_services">
    {foreach from=$additional_services key=service_code item=service_label}
      <tr>
        <td><input type="checkbox" id="service_{$order_id}_{$service_code}" name="additional_services_{$order_id}[]" value="{$service_code}" {if $service_code|in_array:$selected_services}checked{/if} onchange="pk_save_additional_services({$order_id});"/></td>
        <td><label for="service_{$order_id}_{$service_code}" class="noselect">{$service_label}</label></td>
      </tr>
    {/foreach}
  </table>
{/if}