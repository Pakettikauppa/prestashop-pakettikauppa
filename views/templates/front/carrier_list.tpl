{*
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
*}
<div class="row pakettikauppa-extracarrier" style="display:{$display}">
   <div class="col-md-12">
   <p class="carrier_title">{l s='Search Pickup Points' mod='pakettikauppa'}</p>
   </div>

   <div class="col-md-3">
        <input type="text"  name="pickup_code" class="form-control ac_input" id="pickup_code" />
   </div>

   <div class="col-md-9">
        <input class="button btn btn-outline " type="button" value="{l s='Search' mod='pakettikauppa'}" id="check" onclick="search_pickup()"/>
        <input type="hidden" id="id_carts" value="{$id_cart}"/>

   </div>
   <div class="col-md-9" id="pickuppoints" style="padding-top:10px">
       {if $pick_up_points|@count == 0}
            {l s='There is no any pickup points near your address' mod='pakettikauppa'}
       {/if}

     <table>
        
        {foreach $pick_up_points as $pick_up_point}
        
            <tr>
                <td><input type="radio" name="id_pick_up_point" value="{$pick_up_point->pickup_point_id}" onclick="selecteds(this.value)"/></td>
                <td><img src="{$pick_up_point->provider_logo}" height="100%" width="100%"/></td>
                <td>
                    <table>
                        <tr>
                            <td>
                                <font color="black"><b>{l s='Name' mod='pakettikauppa'}: </b></font>{$pick_up_point->name}<br>
                                <font color="black"><b>{l s='Description' mod='pakettikauppa'}: </b></font>{$pick_up_point->description}<br>
                                <font color="black"><b>{l s='Address' mod='pakettikauppa'}: </b></font>{$pick_up_point->street_address}, {$pick_up_point->city}<br>
                                <font color="black"><b>{l s='Distance' mod='pakettikauppa'}: </b></font>{$pick_up_point->distance} m.<br>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        
        {/foreach}
</table>
   </div>
</div><br>










<script>
{ldelim} var module_dir='{$module_dir}'; 


function search_pickup()
{
	
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

if(jsonData.indexOf("Bad request")!= -1)
{
    var error=jsonData.split(':');
    $('#pickuppoints').html('<font color="red">Error:'+error[2]+'</font><br>'+$('#pickuppoints').html());
}else{
var html="<table>";
var data=JSON.parse(jsonData);
if($.isArray(data)){
if(data.length>0){
for(var i=0;i<data.length;i++)
{
    
html =html + '<tr><td><input type="radio" name="id_pick_up_point" value="'+data[i]['pickup_point_id']+'" onclick="selecteds(this.value)"/></td><td><img src="'+data[i]['provider_logo']+'" height="100%" width="100%"/></td><td><table><tr><td><font color="black"><b>Name: </b></font>'+data[i]['name']+'<br><font color="black"><b> Address: </b></font>'+data[i]['street_address']+','+data[i]['city']+','+data[i]['postcode']+','+data[i]['country']+'</br><font color="black"><b> Description: </b></font>'+data[i]['description']+'</br><font color="black"><b>Distance: </b></font>'+data[i]['distance']+'</td></tr></table></td></tr>';

}
   
	$('#pickuppoints').html(html+"</table>");
	}
	else{
	   
		$('#pickuppoints').html('No results found');
	}

}
else{
    
	$('#pickuppoints').html(jsonData);
}
}
				//$('#pickuppoints').html(html+"</table>");
			//console.log(jsonData);
		}
	});
	

	
	
}


function selecteds(code)
{
    $.ajax({
		type: 'POST',
		url: module_dir+'/ajax.php',
		data: {
		     ajax: 1,
                     action: "selectPickUpPoints",
                     code:code,
                     id_cart:$('#id_carts').val(),
                     shipping_method_code:$(".delivery_option_radio input[type='radio']:checked").val()
		},
		success: function(jsonData)
		{


		}
	});
}
	//selecteds(first_check);
$("input[name='id_pick_up_point']").on('click', function(el) {
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
{rdelim}




</script>

