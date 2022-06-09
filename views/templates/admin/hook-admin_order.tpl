<div class="{$table_style} pakettikauppa block_in_order">
  {if $table_style == 'card'}
    {include file="{$template_parts_path}/card-header.tpl" title="{l s='Pakettikauppa shipping' mod='pakettikauppa'}" icon="icon-truck"}
  {else}
    {include file="{$template_parts_path}/panel-header.tpl" title="{l s='Pakettikauppa shipping' mod='pakettikauppa'}" icon="icon-truck"}
  {/if}

  <div class="{if $table_style == 'card'}card-body{/if} row pk-panel-content">
    <div class="col-lg-12">
      {include file="{$template_parts_path}/row-messages.tpl"}
      
      {if empty($critical_error)}
        <form id="pakettikauppa-order_content" class="pk-form" action="" method="POST">
          {if !empty($selected_pickup_point) || !empty($pickup_points)}
            {include file="{$template_parts_path}/row-pickup_points.tpl"}
          {else}
            {include file="{$template_parts_path}/row-courier.tpl"}
          {/if}

          {include file="{$template_parts_path}/row-additional_services.tpl"}
          {include file="{$template_parts_path}/row-labels.tpl"}

          {include file="{$template_parts_path}/row-submit.tpl"}
          {include file="{$template_parts_path}/row-scripts.tpl"}
        </form>
      {/if}

    </div>
  </div>

</div>