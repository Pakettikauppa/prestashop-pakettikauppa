{foreach from=$all_sections key=section_key item=section}
  <div id="module-{$module_name}-{$section_key}" class="panel product-tab">
    {include file="{$template_parts_path}/section-title.tpl" size="h3" title="{$section['title']}" help="{$section['help']}"}

    <div class="row pakettikauppa-param">
      {foreach from=$section['fields'] item=field}
        <div class="col-md-{$field['width']}">

          {if $field['type'] == 'number'}
            {include file="{$template_parts_path}/field-number.tpl" label="{$field['label']}" key="{$field['key']}" value="{$field['value']}" min="{$field['min']}" max="{$field['max']}" step="{$field['step']}" prepend="{$field['prepend']}" append="{$field['append']}"}
          {/if}

        </div>
      {/foreach}
    </div>

    <div class="panel-footer">
      <button type="submit" name="submitAddproduct" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save' mod='pakettikauppa'}</button>
      <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"><i class="process-icon-save"></i> {l s='Save and stay' mod='pakettikauppa'}</button>
    </div>

  </div>
{/foreach}