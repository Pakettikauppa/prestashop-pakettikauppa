<div class="form-group">
    <label class="control-label col-lg-3 {if isset($field['required']) && $field['required'] === true}required{/if}">
        {$field['label']}
    </label>
    <div class="col-lg-6">
        <input type="text" name="{$field['name']}" value="{$field['value']}"/>
        {if isset($field['description'])}
            <p class="help-block">{$field['description']}</p>
        {/if}
    </div>
</div>