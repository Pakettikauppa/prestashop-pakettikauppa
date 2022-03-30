<script>
  const pakettikauppa_ajax = "{$ajax_url}";
  const pakettikauppa_params = {ldelim}
    autoselect: "{$configs['autoselect']}"
  {rdelim};
  const pakettikauppa_text = {ldelim}
    empty_list: "{l s='No results found' mod='pakettikauppa'}",
    first_option: "{l s='Please select pickup point' mod='pakettikauppa'}",
    submit_error: "{l s='Please select pickup point' mod='pakettikauppa'}"
  {rdelim};
</script>