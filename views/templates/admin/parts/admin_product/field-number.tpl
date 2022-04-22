<div class="form-group">
  <label class="form-control-label">{$label}</label>
  
  <div class="input-group">
    {if !empty($prepend)}
      <div class="input-group-prepend">
        <span class="input-group-text">{$prepend}</span>
      </div>
    {/if}
    
    <input type="number" id="{$module_name}_{$key}" name="{$module_name}[{$key}]" class="form-control" value="{$value}" min="{$min}" max="{$max}" step="{$step}">

    {if !empty($append)}
      <div class="input-group-append">
        <span class="input-group-text">{$append}</span>
      </div>
    {/if}
  </div>
</div>