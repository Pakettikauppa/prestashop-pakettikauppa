<div class="row pk-row-submit">
  <div class="col-lg-12">
    <button id="pakettikauppa-submit-save" type="button" class="btn btn-default" name="save_changes" onclick="pk_update_order()">
      <i class="icon-save"></i> {l s='Save changes' mod='pakettikauppa'}
    </button>

    <a class="btn btn-primary _blank pull-right float-right float-end" target="_blank" href="{$controller_url}&amp;submitAction=regenerateShippingSlipPDF&amp;id_cart={$cart_id}">
      <i class="icon-file-text"></i> {l s='Generate label' mod='pakettikauppa'}
    </a>
  </div>
</div>