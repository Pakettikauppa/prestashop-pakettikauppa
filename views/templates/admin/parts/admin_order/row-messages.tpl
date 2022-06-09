{if !empty($critical_errors)}
  {foreach from=$critical_errors item=error_txt}
    <div class="alert alert-danger">
      <p>{$error_txt}</p>
    </div>
  {/foreach}
{/if}
{if !empty($warning_errors)}
  {foreach from=$warning_errors item=error_txt}
    <div class="alert alert-warning">
      <p>{$error_txt}</p>
    </div>
  {/foreach}
{/if}
<div id="pk_ajax_msg" class="alert" style="display:none"></div>