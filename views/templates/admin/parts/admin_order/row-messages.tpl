{if !empty($critical_errors)}
  {foreach from=$critical_errors item=error_txt}
    <div class="alert alert-danger">
      {$error_txt}
    </div>
  {/foreach}
{/if}
{if !empty($warning_errors)}
  {foreach from=$warning_errors item=error_txt}
    <div class="alert alert-warning">
      {$error_txt}
    </div>
  {/foreach}
{/if}
<div id="pk_ajax_msg" class="alert" style="display:none"></div>