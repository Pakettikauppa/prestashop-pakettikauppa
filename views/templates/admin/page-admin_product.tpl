{foreach from=$all_sections item=section}
  <hr>
  <div class="separation product-tab">
    {include file="{$template_parts_path}/section-title.tpl" size="h2" title="{$section['title']}" help="{$section['help']}"}

    <div class="row">
      {foreach from=$section['fields'] item=field}
        <div class="col-md-{$field['width']}">

          {if $field['type'] == 'number'}
            {include file="{$template_parts_path}/field-number.tpl" label="{$field['label']}" key="{$field['key']}" value="{$field['value']}" min="{$field['min']}" max="{$field['max']}" step="{$field['step']}" prepend="{$field['prepend']}" append="{$field['append']}"}
          {/if}

        </div>
      {/foreach}
    </div>

  </div>
{/foreach}