<fieldset id="evidence_hub_options">

		<?php
			//Conferencer::add_meta($post);
			$user_option_count = 0;
			$maphtml = "";
		?>
		<?php foreach($sub_options as $name => $option) { ?>
			<?php
				//if ($option['position'] == 'side') continue;
				
				//$value = isset($$name) ? $$name : $post->$name;
		
				$user_option_count++;
				$name = "evidence_hub_$name";
				$style = "eh_$name";
				$value = "";
				$post_id = isset($post->ID) ? $post->ID : NULL;
				if (isset($post)){
					if ($option['save_as'] == 'term'){
						$value = wp_get_object_terms($post->ID, $name); 
					} else {
						$value = get_post_meta( $post->ID, $name, true );
					}
				}
			?>
			
			<div class="eh_input <?php echo $style; ?>">
					<label for="<?php echo $name; ?>"  class="eh_label"><?php echo $option['label']; ?>: </label> 	
				<span class="eh_input_field">
					<?php if ($option['type'] == 'text') { ?>
						<input
							class="text"
							type="text"
							name="<?php echo $name; ?>"
							id="<?php echo $name; ?>"
							value="<?php echo htmlentities($value); ?>"
						/>
					<?php } else if ($option['type'] == 'int') { ?>
						<input
							class="int"
							type="text"
							name="<?php echo $name; ?>"
							id="<?php echo $name; ?>"
							value="<?php echo htmlentities($value); ?>"
						/>
					<?php } else if ($option['type'] == 'date') { ?>
						<input
							class="date"
							type="text"
							name="<?php echo $name; ?>"
							id="<?php echo $name.'_date'; ?>"
							value="<?php echo $value; ?>"
							placeholder="dd/mm/yyyy"
							
						/>
                     <?php } else if ($option['type'] == 'project') { ?>
						<input
							class="newtag form-input-tip"
							type="text"
                            autocomplete="off"
							name="<?php echo $name; ?>_field"
							id="<?php echo $name; ?>_field"
							value="<?php echo get_the_title($value); ?>"
                            placeholder="Start typing a <?php echo $option['type'];?>"
						/><?php if ($option['descr']){?>
                        <span class="description"><?php echo $option['descr'] ?></span>
                        <?php } ?>
                        <input
							type="hidden"
							name="<?php echo $name; ?>"
							id="<?php echo $name; ?>"
							value="<?php echo $value; ?>"
						/>
                        <div id="menu-container" style="position:absolute; width: 256px;"></div>
							<?php 
								$latLong = array(0,0);
								$zoom = 13;
								if ($value){
									$latLong[0] = get_post_meta($value, '_pronamic_google_maps_latitude', true );
									$latLong[1] = get_post_meta($value, '_pronamic_google_maps_longitude', true );
									$zoom = get_post_meta($value, '_pronamic_google_maps_zoom', true );
									/*$maphtml = '<div id="MapHolder" style="height:260px"></div><script>var map = L.map("MapHolder").setView([0, 0], 13);
									L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {attribution: "&copy; <a href=\"http://osm.org/copyright\">OpenStreetMap</a> contributors"}).addTo(map);
									var marker = L.marker([0, 0]).addTo(map);</script>';*/
								} 
								$maphtml = sprintf('<div id="MapHolder" style="height:260px;%s"></div><script>var map = L.map("MapHolder").setView([%s], %s);
								L.tileLayer("http://{s}.tile.osm.org/{z}/{x}/{y}.png", {attribution: "&copy; <a href=\"http://osm.org/copyright\">OpenStreetMap</a> contributors"}).addTo(map);
								var marker = L.marker([%s]).addTo(map);</script>', ($value) ? '' : 'display:none', implode(",",$latLong), $zoom, implode(",",$latLong));  
								  
                            ?>
					<?php } else if ($option['type'] == 'select') { ?>
								
						<select
							name="<?php echo $name; ?>"
							id="<?php echo $name; ?>"
						>
							<option value=""></option>

                           <?php if ($option['save_as'] != 'term'){ ?>
						   		<?php foreach ($option['options'] as $optionValue => $text) { ?>
								<option
									value="<?php echo $optionValue; ?>"
									<?php if ($optionValue == $value) echo 'selected'; ?>><?php echo $text; ?></option>
                                    <?php }
                                 } else { 
									 $loc_country = wp_get_object_terms(get_post_meta( $post_id, 'evidence_hub_location_id', true ), 'evidence_hub_country');
									 foreach ($option['options'] as $select) { 
										 if (!is_wp_error($value) && !empty($value) && !strcmp($select->slug, $value[0]->slug)) 
											echo "<option value='" . $select->slug . "' selected>" . $select->name . "</option>\n";
										 else if (!is_wp_error($loc_country) && !empty($loc_country) && empty($value) && !is_wp_error($value) && $select->slug == $loc_country[0]->slug)
										 	echo "<option value='" . $select->slug . "' selected>" . $select->name . "</option>\n";
										 else
											echo "<option value='" . $select->slug . "'>" . $select->name . "</option>\n"; 
									 }
						   		}
							?>
                        
						</select>
					<?php } else if ($option['type'] == 'multi-select') { ?>
                        <?php 
							$multivalues = $value;
							if (!$multivalues || !is_array($multivalues)) $multivalues = array(null);
						?>
						<ul>
							<?php foreach ($multivalues as $multivalue) {
								if ($multivalue){
									$itemSelected = ($option['save_as'] != 'term') ? $multivalue : $multivalue->slug; 
								} else {
									$itemSelected = NULL;
								}
								?>
								<li>
									<select name="<?php echo $name; ?>[]">
										<option value=""></option>
										<?php foreach ($option['options'] as $optionValue => $text) { ?>
                                        <?php 
										$itemValue = ($option['save_as'] != 'term') ? $optionValue : $text->slug; 
										$itemName = ($option['save_as'] != 'term') ? $text : $text->name; 
										?>
											<option
												value="<?php echo($itemValue) ?>"
												<?php if ($itemValue == $itemSelected) echo 'selected'; ?>
											>
												<?php echo $itemName; ?>
											</option>
										<?php } ?>
									</select>
								</li>
							<?php } ?>
						</ul>
                        <a class="add-another" href="#">add another</a>
                        <script>
							jQuery('#evidence_hub_options .add-another').click(function() {
								var list = jQuery(this).prev();
								var item = jQuery(jQuery('li:first', list).clone()).appendTo(list);
								jQuery('select', item).val('');
								return false;
							});
						</script>
					<?php } else if ($option['type'] == 'boolean') { ?>
						<input
							type="checkbox"
							name="<?php echo $name; ?>"
							id="<?php echo $name; ?>"
							<?php if ($value) echo 'checked'; ?>
						/>
					<?php } else echo 'unknown option type '.$option['type']; ?>
				</span>
                </div>
		<?php } ?>
    <?php if ($maphtml) echo $maphtml; ?>
	<?php  if (!$user_option_count) { ?>
		<p>There aren't any Evidence Hub options for <?php echo self::PLURAL; ?>.</p>
	<?php } ?>
    
</fieldset>