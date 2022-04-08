$(document).ready(function() {
  pk_toggle_service_params();

  $(document).on("change", ".service_cb input[type='checkbox']", function() {
    pk_toggle_service_params(this);
  });
});

function pk_toggle_service_params(elem = "") {
  var all_services = $(".service_cb input[type='checkbox']");
  if (elem !== "") {
    all_services = [elem];
  }

  for (var i=0;i<all_services.length;i++) {
    var service_param = $(all_services[i]).closest(".service_cb").siblings(".service_param").find("input");
    if (!all_services[i].checked) {
      $(service_param).prop('disabled', true);
    } else {
      $(service_param).prop('disabled', false);
    }
  }
}

function pk_update_order() {
  var form_data = $("#pakettikauppa-order_content").serialize()+'&'+$.param({
    ajax: "1",
    action: "updateOrder",
    id_cart: pk_cart_id,
    token: pk_token,
  });
  $("#pk_ajax_msg").hide();
  $("#pk_ajax_msg").attr('class', 'alert');

  $.ajax({
    type:"POST",
    url: pk_ajax_url,
    async: false,
    //dataType: "json",
    data: form_data,
    success : function(result) {
      if (result in pk_texts) {
        if (result == "save_success") {
          $("#pk_ajax_msg").addClass("alert-success");
          $("#current_pickup_point").hide();
          $("#updated_pickup_point").show();
        } else {
          $("#pk_ajax_msg").addClass("alert-danger");
        }
        $("#pk_ajax_msg").html(pk_texts[result]);
      } else {
        $("#pk_ajax_msg").addClass("alert-danger");
        $("#pk_ajax_msg").html(pk_texts.unknown_error);
      }
      $("#pk_ajax_msg").slideDown("slow");
    }
  });
}
