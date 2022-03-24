function pk_save_additional_services(order_id) {
  var cart_id = $("#order_" + order_id + "_cart_id").val();
  var selected_checkboxes = document.querySelectorAll('input[name="additional_services_' + order_id + '[]"]:checked');
  var selected_services = [];
  for (var i=0; i<selected_checkboxes.length; i++) {
    selected_services.push(selected_checkboxes[i].value);
  }

  $.ajax({
    type: 'POST',
    cache: false,
    url: pakettikauppa_ajax,
    data: {
      ajax: true,
      action: 'saveAddtionalService',
      dataType: 'json',
      id_cart: cart_id,
      selected_services: selected_services
    },
    success: function (jsonData) {
      if (!isJson(jsonData)) {
        alert(jsonData);
      }
    }
  });
}

function isJson(str) {
  try {
    JSON.parse(str);
  } catch (e) {
    return false;
  }
  return true;
}
