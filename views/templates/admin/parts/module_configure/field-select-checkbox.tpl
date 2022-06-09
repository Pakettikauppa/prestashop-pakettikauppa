<div class="form-group">
    <label class="control-label col-lg-3 {if isset($field['required']) && $field['required'] === true}required{/if}">
        {$field['label']}
    </label>
    <div class="col-lg-9">
        {foreach from=$field['value'] key=opt_value item=opt_title}
            <div class="checkbox">
                <label for="pakettikauppa_cod_{$opt_value}">
                    <input id="pakettikauppa_cod_{$opt_value}" class="" type="checkbox" name="{$field['name']}[]" value="{$opt_value}" {if $field['selected']|is_array && $opt_value|in_array:$field['selected']}checked{/if}/>
                    {$opt_title}
                </label>
            </div>
        {/foreach}
        {if isset($field['description'])}
            <p class="help-block">{$field['description']}</p>
        {/if}
    </div>
</div>