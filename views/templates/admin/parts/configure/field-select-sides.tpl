<div class="form-group">
    <label class="control-label col-lg-3">
        {if isset($field['label_explain'])}
            <span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="{$field['label_explain']}">
                {$field['label']}
            </span>
        {else}
            {$field['label']}
        {/if}
    </label>
    <div class="col-lg-9">
        <div class="form-group">
            <div class="col-lg-8">
                <div class="form-control-static row">
                    <div class="col-xs-6">
                        <select name="{$field['side_available']['name']}[]" multiple="multiple"
                            id="{if isset($field['side_available']['id'])}{$field['side_available']['id']}{else}availableSwap{/if}"
                            {if isset($field['side_available']['class'])}class="{$field['side_available']['class']}"{/if}
                        >
                            {foreach from=$field['value'] key=opt_value item=opt_title}
                                <option value="{$opt_value}">{$opt_title}</option>
                            {/foreach}
                        </select>
                        <a href="#" class="btn btn-default btn-block"
                            id="{if isset($field['side_available']['btn_id'])}{$field['side_available']['btn_id']}{else}addSwap{/if}">
                            {if isset($field['side_available']['btn_txt'])}{$field['side_available']['btn_txt']}{else}{l s='Add' mod='pakettikauppa'}{/if} <i class="icon-arrow-right"></i>
                        </a>
                    </div>
                    <div class="col-xs-6">
                        <select name="{$field['side_selected']['name']}[]" multiple="multiple"
                            id="{if isset($field['side_selected']['id'])}{$field['side_selected']['id']}{else}selectedSwap{/if}"
                            {if isset($field['side_selected']['class'])}class="{$field['side_selected']['class']}"{/if}
                        >
                            {foreach from=$field['selected'] key=opt_value item=opt_title}
                                <option value="{$opt_value}">{$opt_title}</option>
                            {/foreach}
                        </select>
                        <a href="#" class="btn btn-default btn-block"
                            id="{if isset($field['side_selected']['btn_id'])}{$field['side_selected']['btn_id']}{else}removeSwap{/if}">
                            <i class="icon-arrow-left"></i> {if isset($field['side_selected']['btn_txt'])} {$field['side_selected']['btn_txt']}{else}{l s='Remove' mod='pakettikauppa'}{/if}
                        </a>
                    </div>
                </div>
            </div>
        </div>
        {if isset($field['description'])}
            <p class="help-block">{$field['description']}</p>
        {/if}
    </div>
</div>