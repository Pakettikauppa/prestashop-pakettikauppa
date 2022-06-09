{$selected_services_text}
{if $is_cod && !isset($additional_services['3101'])}
  <span class="service_error">{l s='COD payment not supported!' mod='pakettikauppa'}</span>
{/if}