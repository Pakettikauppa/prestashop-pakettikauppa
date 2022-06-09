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
    var points_radio = $("#pickuppoints_" + carrier_id + " input.pickup_point");
    var dropdown = $("#pickuppoints_" + carrier_id + " select.pk_dropdown");

    if (points_radio.length > 0) {
      if (pakettikauppa_params.autoselect == 1) {
        points_radio[0].checked = true;
        $(points_radio[0]).trigger("click");
      }
    } else if (dropdown.length) {
      if (pakettikauppa_params.autoselect == 1) {
        for (var i=0; i<dropdown[0].options.length; i++) {
          if (dropdown[0].options[i].value) {
            dropdown[0].value = dropdown[0].options[i].value;
            break;
          }
        }
      } else if (dropdown[0].value === "") {
        pk_select_pickup_point(0);
      }
      $(dropdown).trigger("change");
    } else {
      pk_select_pickup_point(0);
    }
  });

  $(document).on("click", ".pakettikauppa-extracarrier .pickups_table_holder .row-bordered.clickable", function() {
    this.querySelector('input.pickup_point').click();
  });

  $(document).on("submit", "form#js-delivery", function() {
    var carrier_id = $("input[name*='delivery_option[']:checked").val().replace(/\D/g, "");
    
    if ($("#pickuppoints_" + carrier_id + " .pk_dropdown").length > 0) {
      if (!$("#pickuppoints_" + carrier_id + " .pk_dropdown").val() || $("#pickuppoints_" + carrier_id + " .pk_dropdown").val() === "") {
        alert(pakettikauppa_text.submit_error);
        return false;
      }
    }

    if ($("#pickuppoints_" + carrier_id + " input.pickup_point").length > 0) {
      if (!$("#pickuppoints_" + carrier_id + " input.pickup_point").is(':checked')) {
        alert(pakettikauppa_text.submit_error);
        return false;
      }
    }

    return true;
  });
});
