<div class="form-group">
  <label class="control-label col-lg-3">
    {$field['label']}
  </label>
  <div class="col-lg-9">
    <span class="switch prestashop-switch fixed-width-lg">
      <input type="radio" name="{$field['name']}" id="{$field['name']}_on" value="1" {if $field['selected']}checked{/if}>
      <label for="{$field['name']}_on">{l s='Yes' mod='pakettikauppa'}</label>
      <input type="radio" name="{$field['name']}" id="{$field['name']}_off" value="0" {if !$field['selected']}checked{/if}>
      <label for="{$field['name']}_off">{l s='No' mod='pakettikauppa'}</label>
      <a class="slide-button btn"></a>
    </span>
    {if isset($field['description'])}
      <p class="help-block">{$field['description']}</p>
    {/if}
  </div>
</div>