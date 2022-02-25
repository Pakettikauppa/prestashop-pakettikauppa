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
  $(document).on("keyup", "#pickup_code", function(e) {
    if (event.keyCode === 13) {
      $("#check").click();
    }
  });

	$(document).on("change", ".delivery_option_radio", function() {
		$(".pakettikauppa-extracarrier").hide();
	});

  $(document).on("click", "#pickuppoints .row-bordered.clickable", function() {
    this.querySelector('input[name="id_pick_up_point"]').click();
  });

  //Restore custom added radio functionality
  $(document).on("change", '#pickuppoints .added_from_template input[name="id_pick_up_point"]', function() {
    $('#pickuppoints .added_from_template input[name="id_pick_up_point"]').parent().removeClass("checked");

    if (this.checked) {
      this.parentElement.classList.add("checked");
    }
  });
});

function pk_select_pickpup_point(code) {
  $.ajax({
    type: 'POST',
    url: pakettikauppa_ajax,
    data: {
      ajax: 1,
      action: "selectPickUpPoints",
      code:code,
      id_cart:$('#id_carts').val(),
      shipping_method_code:$(".delivery_option_radio input[type='radio']:checked").val()
    },
    success: function(jsonData) {
      //console.log(jsonData);
    }
  });
}

function pk_search_pickup() {
  $.ajax({
    type: 'POST',
    url: pakettikauppa_ajax,
    data: {
      ajax: 1,
      action: "searchPickUpPoints",
      postcode:$('#pickup_code').val()
    },
    success: function(jsonData) {
      if (jsonData.indexOf("Bad request")!= -1) {
        var error=jsonData.split(':');
        $('#pickuppoints').html('<font color="red">Error:'+error[2]+'</font><br>'+$('#pickuppoints').html());
      } else {
        var html='<table class="table-pickups">';
        var data=JSON.parse(jsonData);
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
              html = html + pk_use_template(pickup_template, replaces);
            }
            $('#pickuppoints').html(html + "</table>");
          } else { 
            $('#pickuppoints').html('No results found');
          }
        } else {
          $('#pickuppoints').html(jsonData);
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
