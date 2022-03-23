<div class="form-group">
    <label class="control-label col-lg-3 {if isset($field['required']) && $field['required'] === true}required{/if}">
        {$field['label']}
    </label>
    <div class="col-lg-6">
        <textarea name="{$field['name']}"
            {if isset($field['id'])}id="{$field['id']}"{/if}
            {if isset($field['class'])}class="{$field['class']}"{/if}
        >{$field['value']}</textarea>

        {if isset($field['description'])}
            <p class="help-block">{$field['description']}</p>
        {/if}
    </div>
</div>