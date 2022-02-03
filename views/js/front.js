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
	$(document).on("change", ".delivery_option_radio", function() {
		$(".pakettikauppa-extracarrier").hide();
	});
});

/*
$(document).ready(function(){

$(document).on('click',$('#check'),function(){



$.ajax({
		type: 'POST',
		url: module_dir+'/ajax.php',
		data: {
		     ajax: 1,
                     action: "searchPickUpPoints",
                     postcode:$('#pickup_code').val()
		},
		success: function(jsonData)
		{

var html="";
var data=JSON.parse(jsonData);
for(var i=0;i<data.length;i++)
{
html =html + '<div class="col-md-12 resume table table-bordered" ><div class="col-md-1" style="margin-top:24px" ><input type="radio" name="id_pick_up_point" value="'+data[i]['pickup_point_id']+'" /></div><div class="col-md-2" ><img src="'+data[i]['provider_logo']+'" height="100%" width="100%"/></div><div class="col-md-9"><div class="row"><div class="col-md-12"><font color="black"><b>Name: </b></font>'+data[i]['name']+'</div><div class="col-md-12"><font color="black"><b> Address: </b></font>'+data[i]['street_address']+','+data[i]['city']+','+data[i]['postcode']+','+data[i]['country']+'</div><div class="col-md-12"><font color="black"><b> Description: </b></font>'+data[i]['description']+'</div><div class="col-md-12"><font color="black"><b>Distance: </b></font>'+data[i]['distance']+'</div></div></div></div>';
}
				$('#pickuppoints').html(html);
			//console.log(jsonData);
		}
	});







});



$(document).on('click', $("input[name=id_pick_up_point]"), function(el) {
    //console.log($(el.target).val());
    $.ajax({
		type: 'POST',
		url: module_dir+'/ajax.php',
		data: {
		     ajax: 1,
                     action: "selectPickUpPoints",
                     code:$(el.target).val(),
                     id_cart:$('#id_carts').val(),
                     shipping_method_code:$(".delivery_option_radio input[type='radio']:checked").val()
		},
		success: function(jsonData)
		{


		}
	});
});







});


function select_pickup(id_pickup)
{
$.ajax({
		type: 'POST',
		url: module_dir+'/ajax.php',
		data: {
		     ajax: 1,
                     action: "selectPickUpPoints",
                     code:$(this).val(),
                     id_cart:$('#id_carts')

		},
		success: function(jsonData)
		{


		}
	});
}
*/