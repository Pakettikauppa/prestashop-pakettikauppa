<div class="form-group">
    <label class="control-label col-lg-3">{$field['label']}</label>
    <div class="col-lg-9">
        <textarea name="{$field['name']}"
            {if isset($field['id'])}id="{$field['id']}"{/if}
            {if isset($field['class'])}class="{$field['class']}"{/if}
        >{$field['value']}</textarea>

        {if isset($field['description'])}
            <p class="help-block">{$field['description']}</p>
        {/if}
    </div>
</div>