/**
* 2017-2018 Pakettikauppa
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    Pakettikauppa <asiakaspalvelu@pakettikauppa.fi>
*  @copyright 2017- Pakettikauppa Oy
*  @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  
*/

$(document).ready(function() {

  $('#id_warehouse').on('change',function(){
    $.ajax({
      type: 'POST',
      url: module_dir+'/ajax.php',
      data: {
        ajax: 1,
        action: "FetchData",
        id_warehouse:$('#id_warehouse').val()
      },
      success: function(jsonData) {
        var data=JSON.parse(jsonData);
        $('#selectedSwap option').remove();
        for(var i=0;i<data['selected_carriers'].length;i++)
        {
          $('#selectedSwap').append($('<option>', {
            value: data['selected_carriers'][i]["id_carrier"],
            text: data['selected_carriers'][i]["name"]
          }));
        }
        $('#availableSwap option').remove();
        for(var i=0;i<data['carriers'].length;i++)
        {
          $('#availableSwap').append($('<option>', {
            value: data['carriers'][i]["id_reference"],
            text: data['carriers'][i]["name"]
          }));
        }
      }
    });
  });

  $(document).on('click', '.help-block .variable_row.clickable code', function() {
    var field_id = '#' + $(this).closest('.variable_row').data('for');
    var field = $(field_id);
    field.val(field.val() + $(this).html());
  });
  
});
