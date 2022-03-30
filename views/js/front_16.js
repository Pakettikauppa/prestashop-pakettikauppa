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
      $(".pakettikauppa-extracarrier").addClass("loading");
      var carrier_id = $(this).closest(".pakettikauppa-extracarrier").data("carrier");
      $("#check_" + carrier_id).click();
      return false;
    }
  });

  $(document).on("click", ".pickups_search_holder input[type=button]", function() {
    $(".pakettikauppa-extracarrier").addClass("loading");
  });

  $(document).on("change", "input.delivery_option_radio", function() {
    $(".pakettikauppa-extracarrier").show();
    $(".pakettikauppa-extracarrier").addClass("loading");
  });

  $(document).on("click", ".pakettikauppa-extracarrier .pickups_table_holder .row-bordered.clickable", function() {
    this.querySelector('input.pickup_point').click();
  });

  //Restore custom added radio functionality
  $(document).on("change", '.pakettikauppa-extracarrier .added_from_template input.pickup_point', function() {
    $('.pickups_table_holder .added_from_template input.pickup_point').parent().removeClass("checked");

    if (this.checked) {
      this.parentElement.classList.add("checked");
    }
  });
});
