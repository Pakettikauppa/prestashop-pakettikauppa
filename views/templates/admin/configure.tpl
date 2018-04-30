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
<form id="module_form" class="defaultForm form-horizontal"
      action="index.php?controller=AdminModules&amp;configure=pakettikauppa&amp;tab_module=shipping_logistics&amp;module_name=pakettikauppa&amp;token={$token}"
      method="post" enctype="multipart/form-data" novalidate="">


    <div class="panel" id="fieldset_0">

        <div class="panel-heading">
            <i class="icon-cogs"></i> Configure API
        </div>


        <div class="form-wrapper">

            <div class="form-group">

                <label class="control-label col-lg-3">
                    API Key
                </label>

                <div class="col-lg-6">
                    <input type="text" name="api_key" value="{Configuration::get('PAKETTIKAUPPA_API_KEY')}"/>
                </div>

            </div>

            <div class="form-group">

                <label class="control-label col-lg-3">
                    Secret
                </label>

                <div class="col-lg-6">
                    <input type="text" name="secret" value="{Configuration::get('PAKETTIKAUPPA_SECRET')}"/>
                </div>

            </div>

            <div class="form-group">

                <label class="control-label col-lg-3">
                    Mode
                </label>

                <div class="col-lg-6">
                    <select name="modes">
                        {if Configuration::get('PAKETTIKAUPPA_MODE')==1}
                            <option value="1" selected="true">Test Mode</option>
                        {else}
                            <option value="1">Test Mode</option>
                        {/if}

                        {if Configuration::get('PAKETTIKAUPPA_MODE')==0}
                            <option value="0" selected="true">Production Mode</option>
                        {else}
                            <option value="0">Production Mode</option>
                        {/if}

                    </select>

                </div>

            </div>
            <div class="form-group">

                <label class="control-label col-lg-3">

                </label>


                <div class="col-lg-9">

                    <div class="form-group">
                        <div class="col-lg-9">
                            <div class="form-control-static row">

                            </div>
                        </div>
                    </div>


                </div>

            </div>


        </div><!-- /.form-wrapper -->


        <div class="panel-footer">
            <button type="submit" value="1" id="module_form_submit_btn" name="submitPakettikauppaAPI"
                    class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>

    </div>


</form>

<form id="module_form" class="defaultForm form-horizontal"
      action="index.php?controller=AdminModules&amp;configure=pakettikauppa&amp;tab_module=shipping_logistics&amp;module_name=pakettikauppa&amp;token={$token}"
      method="post" enctype="multipart/form-data" novalidate="">


    <div class="panel" id="fieldset_0">

        <div class="panel-heading">
            <i class="icon-cogs"></i> Configure Sending Methods
        </div>

        <div class="form-wrapper">

<p>Some kind of a list to which shipping methods to use</p>
        </div>

        <div class="panel-footer">
            <button type="submit" value="1" id="module_form_submit_btn" name="submitPakettikauppaSender"
                    class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>

    </div>
</form>

<form id="module_form" class="defaultForm form-horizontal"
      action="index.php?controller=AdminModules&amp;configure=pakettikauppa&amp;tab_module=shipping_logistics&amp;module_name=pakettikauppa&amp;token={$token}"
      method="post" enctype="multipart/form-data" novalidate="">


    <div class="panel" id="fieldset_0">

        <div class="panel-heading">
            <i class="icon-cogs"></i> Pick-up point settings
        </div>

        <div class="form-wrapper">

        <p>List of pickup-point providers: activate, shipping price, trigger price, triggered price</p>

        </div>


        <label class="control-label col-lg-3">
            How many pick-up points are shown
        </label>

        <div class="col-lg-6">
            <select class="form-control" name="pickup_points_count">
                <option value="1">1</option>
                <option value="2">2</option>
                <option value="3">3</option>
                <option value="4">4</option>
                <option value="5">5</option>
                <option value="6">6</option>
                <option value="7">7</option>
                <option value="8">8</option>
                <option value="9">9</option>
                <option value="10">10</option>
            </select>
        </div>


        <div class="panel-footer">
            <button type="submit" value="1" id="module_form_submit_btn" name="submitPakettikauppaSender"
                    class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>

    </div>
</form>

<form id="module_form" class="defaultForm form-horizontal"
      action="index.php?controller=AdminModules&amp;configure=pakettikauppa&amp;tab_module=shipping_logistics&amp;module_name=pakettikauppa&amp;token={$token}"
      method="post" enctype="multipart/form-data" novalidate="">


    <div class="panel" id="fieldset_0">

        <div class="panel-heading">
            <i class="icon-cogs"></i> Configure Sender Address
        </div>


        <div class="form-wrapper">

            <div class="form-group">

                <label class="control-label col-lg-3">
                    Store Name
                </label>

                <div class="col-lg-6">
                    <input type="text" name="store_name" value="{Configuration::get('PAKETTIKAUPPA_STORE_NAME')}"/>
                </div>

            </div>

            <div class="form-group">

                <label class="control-label col-lg-3">
                    Address
                </label>

                <div class="col-lg-6">
                    <input type="text" name="address" value="{Configuration::get('PAKETTIKAUPPA_STORE_ADDRESS')}"/>
                </div>

            </div>

            <div class="form-group">

                <label class="control-label col-lg-3">
                    Post code
                </label>

                <div class="col-lg-6">
                    <input type="text" name="postcode" value="{Configuration::get('PAKETTIKAUPPA_POSTCODE')}"/>
                </div>

            </div>

            <div class="form-group">

                <label class="control-label col-lg-3">
                    City
                </label>

                <div class="col-lg-6">
                    <input type="text" name="city" value="{Configuration::get('PAKETTIKAUPPA_CITY')}"/>
                </div>

            </div>

            <div class="form-group">

                <label class="control-label col-lg-3">
                    Phone
                </label>

                <div class="col-lg-6">
                    <input type="text" name="phone" value="{Configuration::get('PAKETTIKAUPPA_PHONE')}"/>
                </div>

            </div>

            <div class="form-group">

                <label class="control-label col-lg-3">
                    Country
                </label>

                <div class="col-lg-6">
                    <select name="country">
                        {foreach $countries as $country}
                            {if Configuration::get('PAKETTIKAUPPA_COUNTRY')== $country.iso_code}
                                <option value="{$country.iso_code}" selected="true">{$country.country}</option>
                            {else}
                                <option value="{$country.iso_code}">{$country.country}</option>
                            {/if}
                        {/foreach}
                    </select>

                </div>

            </div>

            <div class="form-group">

                <label class="control-label col-lg-3">
                    Vat Code
                </label>

                <div class="col-lg-6">
                    <input type="text" name="vat_code" value="{Configuration::get('PAKETTIKAUPPA_VATCODE')}"/>
                </div>

            </div>


            <div class="form-group">

                <label class="control-label col-lg-3">

                </label>


                <div class="col-lg-9">

                    <div class="form-group">
                        <div class="col-lg-9">
                            <div class="form-control-static row">

                            </div>
                        </div>
                    </div>


                </div>

            </div>


        </div><!-- /.form-wrapper -->


        <div class="panel-footer">
            <button type="submit" value="1" id="module_form_submit_btn" name="submitPakettikauppaSender"
                    class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>

    </div>


</form>

{*
<form id="module_form" class="defaultForm form-horizontal" action="index.php?controller=AdminModules&amp;configure=pakettikauppa&amp;tab_module=shipping_logistics&amp;module_name=pakettikauppa&amp;token={$token}" method="post" enctype="multipart/form-data" novalidate="">
				<input name="submitPakettikauppaModule" value="1" type="hidden">
				
				<div class="panel" id="fieldset_0">
												
						<div class="panel-heading">
														<i class="icon-cogs"></i>							Configure Warehouse & Carrier
						</div>
					
								
												<div class="form-wrapper">
											
						<div class="form-group">
													
																	<label class="control-label col-lg-3">
																				Select Warehouse
																			</label>
															

							
								<div class="col-lg-9">
								
																											<select name="id_warehouse" class=" fixed-width-xl" id="id_warehouse">
																											{foreach $warehouses as $warehouse}
							<option value="{$warehouse.id_warehouse}">{$warehouse.name}</option>
								                    {/foreach}																                </select>
																									
								
																			<p class="help-block">
																							Select Warehouse to assign Pakettikauppa Carriers
																					</p>
																	
								</div>
							
												</div>
						
											
						<div class="form-group">
													
																	<label class="control-label col-lg-3">
																				<span class="label-tooltip" data-toggle="tooltip" data-html="true" title="" data-original-title="																																										Associated carriers.
																																																								You can choose which carriers can ship orders from particular warehouses.
																																																								If you do not select any carrier, all the carriers will be able to ship from this warehouse.
																																							">
																				Carriers
																				</span>
																			</label>
															

							
								<div class="col-lg-9">
								
																	<div class="form-group">
										<div class="col-lg-9">
											<div class="form-control-static row">
												<div class="col-xs-6">
													<select class="" id="availableSwap" name="ids_carriers_available[]" multiple="multiple">
														{foreach $carriers as $carrier}
									<option value="{$carrier.id_reference}">{$carrier.name}</option>	
								      {/foreach}																																																			
																											
																																																							</select>
													<a href="#" id="addSwap" class="btn btn-default btn-block">Add <i class="icon-arrow-right"></i></a>
												</div>
												<div class="col-xs-6">
								<select class="" id="selectedSwap" name="ids_carriers_selected[]" multiple="multiple">
								{foreach $selected_carriers as $selected_carrier}
									<option value="{$selected_carrier.id_carrier}">{$selected_carrier.name}</option>	
								      {/foreach}																								</select>
													<a href="#" id="removeSwap" class="btn btn-default btn-block"><i class="icon-arrow-left"></i> Remove</a>
												</div>
											</div>
										</div>
									</div>
																
								
																			<p class="help-block">
																							If no carrier is selected, no carrier will be show on order shipping method. Use CTRL+Click to select more than one carrier.
																					</p>
																	
								</div>
							
												</div>
						
										
																	
									</div><!-- /.form-wrapper -->
								
											
						
												<div class="panel-footer">
												<button type="submit" value="1" id="module_form_submit_btn" name="submitPakettikauppaModule" class="btn btn-default pull-right">
							<i class="process-icon-save"></i> Save
						</button>
																													</div>
							
		</div>
		
		
	</form>
	*}

<form id="module_form" class="defaultForm form-horizontal"
      action="index.php?controller=AdminModules&amp;configure=pakettikauppa&amp;tab_module=shipping_logistics&amp;module_name=pakettikauppa&amp;token={$token}"
      method="post" enctype="multipart/form-data" novalidate="">


    <div class="panel" id="fieldset_0">

        <div class="panel-heading">
            <i class="icon-cogs"></i> Shipping Status
        </div>


        <div class="form-wrapper">

            <div class="form-group">

                <label class="control-label col-lg-3">
                    Order status on which you will be generating shipping
                </label>


                <div class="col-lg-9">

                    <select name="shipping_state" class=" fixed-width-xl" id="id_warehouse">
                        <option value="">---Select Order State---</option>

                        {foreach $order_statuses as $order_statuse}
                            {if $order_statuse.id_order_state==$shipping_state}
                                <option value="{$order_statuse.id_order_state}"
                                        selected="true">{$order_statuse.name}</option>
                            {else}
                                <option value="{$order_statuse.id_order_state}">{$order_statuse.name}</option>
                            {/if}
                        {/foreach}
                    </select>

                </div>

            </div>


            <div class="form-group">

                <label class="control-label col-lg-3">

                </label>


                <div class="col-lg-9">

                    <div class="form-group">
                        <div class="col-lg-9">
                            <div class="form-control-static row">

                            </div>
                        </div>
                    </div>


                </div>

            </div>


        </div><!-- /.form-wrapper -->


        <div class="panel-footer">
            <button type="submit" value="1" id="module_form_submit_btn" name="submitPakettikauppaShippingState"
                    class="btn btn-default pull-right">
                <i class="process-icon-save"></i> Save
            </button>
        </div>

    </div>


</form>

<script>
    {ldelim} var module_dir = '{$module_dir}'; {rdelim}
</script>

