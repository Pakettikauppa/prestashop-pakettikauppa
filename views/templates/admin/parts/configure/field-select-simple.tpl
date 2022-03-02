<div class="form-group">
    <label class="control-label col-lg-3">{$field['label']}</label>
    <div class="col-lg-6">
        <select name="{$field['name']}"
            {if isset($field['id'])}id="{$field['id']}"{/if}
            {if isset($field['class'])}class="{$field['class']}"{/if}
            {if isset($field['onchange'])}onchange="{$field['onchange']}"{/if}
        >
            {if !empty($field['empty_option'])}
                <option value="">{$field['empty_option']}</option>
            {/if}
            {foreach from=$field['value'] key=opt_value item=opt_title}
                <option value="{$opt_value}" {if $opt_value == $field['selected'] || ($field['selected'] == '' && isset($field['default']) && $opt_value == $field['default'])}selected{/if}>{$opt_title}</option>
            {/foreach}
        </select>
        {if isset($field['description'])}
            <p class="help-block">{$field['description']}</p>
        {/if}
    </div>
</div>