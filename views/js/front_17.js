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
  $(document).on("keyup", ".pakettikauppa-extracarrier input[name='pickup_code']", function(e) {
    if (e.keyCode === 13 || e.which === 13) {
      e.preventDefault();
      var carrier_id = $(this).closest(".pakettikauppa-extracarrier").data("carrier");
      $("#check_" + carrier_id).click();
      return false;
    }
  });

  $(document).on("change", "input[name*='delivery_option[']", function() {
    var carrier_id = this.value.replace(/\D/g, "");
    var points = $("#pickuppoints_" + carrier_id + " input[name='id_pick_up_point']");
    if (points.length > 0) {
      points[0].checked = true;
      $(points[0]).trigger("click");
    } else {
      pk_select_pickpup_point(0);
    }
  });

  $(document).on("click", ".pakettikauppa-extracarrier .pickups_table_holder .row-bordered.clickable", function() {
    this.querySelector('input[name="id_pick_up_point"]').click();
  });
});

function pk_select_pickpup_point(pickup_id) {
  var carrier_id = $("input[name*='delivery_option[']:checked").val().replace(/\D/g, "");
  $.ajax({
    type: 'POST',
    url: pakettikauppa_ajax,
    data: {
      ajax: 1,
      action: "selectPickUpPoints",
      id_pickup: pickup_id,
      id_cart: $('#id_carts_' + carrier_id).val(),
      id_carrier: carrier_id,
      method_code: $('#method_code_' + carrier_id).val(),
    },
    success: function(jsonData) {
      //console.log(jsonData);
    }
  });
}

function pk_search_pickup(id_carrier) {
  $.ajax({
    type: 'POST',
    url: pakettikauppa_ajax,
    data: {
      ajax: 1,
      action: "searchPickUpPoints",
      postcode:$('#pickup_code_' + id_carrier).val()
    },
    success: function(jsonData) {
      var container = $('#pickuppoints_' + id_carrier);
      if (jsonData.indexOf("Bad request")!= -1) {
        var error = jsonData.split(':');
        $(container).html('<font color="red">Error:' + error[2] + '</font><br>' + $('#pickuppoints_' + id_carrier).html());
      } else {
        var html = '<table class="table-pickups">';
        var data = JSON.parse(jsonData);
        if (typeof data === 'object') {
          if ($.isArray(data.pickup_points) && data.pickup_points.length>0) {
            for (var i=0;i<data.pickup_points.length;i++) {
              var pickup_data = data.pickup_points[i];
              var checked = '';
              if (data.selected == pickup_data['pickup_point_id']) {
                checked = 'checked';
              }
              var replaces = {
                point_id: pickup_data['pickup_point_id'],
                logo: pickup_data['provider_logo'],
                name: pickup_data['name'],
                description: pickup_data['description'],
                street: pickup_data['street_address'],
                city: pickup_data['city'],
                postcode: pickup_data['postcode'],
                country: pickup_data['country'],
                distance: pickup_data['distance'],
                checked: checked,
              };
              html = html + pk_use_template(window['pickup_template_' + id_carrier], replaces);
            }
            $(container).html(html + "</table>");
          } else { 
            $(container).html('No results found');
          }
        } else {
          $(container).html(jsonData);
        }
      }
      
      //console.log(jsonData);
    }
  });
}

function pk_use_template(template, replaces) {
  for (var param in replaces) {
    template = template.replace('[' + param + ']', replaces[param]);
  }
  
  return template;
}
