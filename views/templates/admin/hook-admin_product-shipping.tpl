<div class="col-md-12 pk-tab-shipping">
  {foreach from=$fields item=group}
    <div class="form-group">
      {include file="{$template_parts_path}/group-title.tpl" title="{$group['title']}" help="{$group['help']}"}
      
      <div class="row">
        {foreach from=$group['fields'] item=field}
          <div class="col-md-{$field['width']}">

            {if $field['type'] == 'number'}
              {include file="{$template_parts_path}/field-number.tpl" label="{$field['label']}" key="{$field['key']}" value="{$field['value']}" prepend="{$field['prepend']}" append="{$field['append']}"}
            {/if}

          </div>
        {/foreach}
      </div>
    </div>
  {/foreach}
</div>