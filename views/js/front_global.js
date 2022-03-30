function pk_select_pickup_point(pickup_id) {
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
        var data = JSON.parse(jsonData);
        if (typeof data === 'object') {
          var pickup_points = [];
          var selected = '';
          if ($.isArray(data.pickup_points) && data.pickup_points.length>0) {
            pickup_points = data.pickup_points;
            selected = data.selected;
          }
          if (pk_template_style === 'dropdown') {
            pk_update_dropdown(id_carrier, pickup_points, selected);
          } else {
            if (pickup_points.length>0) {
              $(container).html(pk_get_list_table(data, id_carrier));
            } else { 
              $(container).html(pakettikauppa_text.empty_list);
            }
          }
        } else {
          $(container).html(jsonData);
        }
      }
      
      $(".pakettikauppa-extracarrier").removeClass("loading");
      //console.log(jsonData);
    }
  });
}

function pk_get_list_table(data, id_carrier) {
  var html = '<table class="table-pickups">';
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
  return html + "</table>";
}

function pk_use_template(template, replaces) {
  for (var param in replaces) {
    template = template.split('[' + param + ']').join(replaces[param]);
  }
  
  return template;
}

function pk_update_dropdown(id_carrier, pickup_points, selected) {
  var dropdown = document.getElementById("list-" + id_carrier);
  pk_remove_options(dropdown);

  if (!pickup_points.length) {
    var option = document.createElement('option');
    option.value = "";
    option.innerHTML = pakettikauppa_text.empty_list;
    dropdown.appendChild(option);
  } else {
    var option = document.createElement('option');
    option.value = "";
    option.innerHTML = "-- " + pakettikauppa_text.first_option + " --";
    if (!selected) {
      option.selected = true;
    }
    dropdown.appendChild(option);
    for (var i=0; i<pickup_points.length; i++) {
      var option = document.createElement('option');
      option.value = pickup_points[i].pickup_point_id;
      option.innerHTML = pickup_points[i].name;
      option.dataset.name = pickup_points[i].name;
      option.dataset.address = pickup_points[i].street_address + ", " + pickup_points[i].city;
      option.dataset.distance = pickup_points[i].distance + " m.";
      if (pickup_points[i].pickup_point_id === selected) {
        option.selected = true;
      }
      dropdown.appendChild(option);
    }
  }
  
  pk_build_dropdown("list-" + id_carrier); //Required dropdown.js file
}

function pk_remove_options(select_element) {
   var i, L = select_element.options.length - 1;
   for(i = L; i >= 0; i--) {
      select_element.remove(i);
   }
}
