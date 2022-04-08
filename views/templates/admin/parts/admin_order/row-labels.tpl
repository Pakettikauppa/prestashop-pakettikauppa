<div class="row pk-row-labels">
  <div class="col-12 col-lg-6">
    {include file="{$template_parts_path}/field-title.tpl" title="{l s='Shipment label' mod='pakettikauppa'}"}
    
    <div class="col-lg-8">
      {if empty($shipping_labels)}
        {l s='The label is still not created' mod='pakettikauppa'}.
      {else}
        <div class="pk-labels_list">
          {foreach from=$shipping_labels item=label}
            <div class="row">
              <div class="col-lg-12">
                <a class="btn btn-default" href="{$controller_url}&amp;submitAction=printShippingSlipPDF&amp;id_cart={$cart_id}" target="_blank" title="{l s='Print label' mod='pakettikauppa'}"><i class="icon-download"></i> {$label}</a>
              </div>
            </div>
          {/foreach}
        </div>
      {/if}
    </div>

  </div>
</div>