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
				if ($option['save_as'] == 'term'){
					$value = wp_get_object_terms($post->ID, $name); 
				} else {
					$value = get_post_meta( $post->ID, $name, true );
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
							value="<?php if ($value) $value; ?>"
							placeholder="dd/mm/yyyy"
							
						/>
                    <?php } else if ($option['type'] == 'location') { ?>
						<input
							class="newtag form-input-tip"
							type="text"
                            autocomplete="off"
							name="<?php echo $name; ?>_field"
							id="<?php echo $name; ?>_field"
							value="<?php echo get_the_title($value); ?>"
                            placeholder="Start typing a location"
						/>
                        <input
							type="hidden"
							name="<?php echo $name; ?>"
							id="<?php echo $name; ?>"
							value="<?php echo $value; ?>"
						/>
                        <div id="menu-container" style="position:absolute; width: 256px;"></div>
								<?php if ($value)
									$maphtml = '<div id="pronamicMapHolder">'.Evidence_Hub::get_pronamic_google_map($value).'</div>';
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
								 	 $loc_country = wp_get_object_terms(get_post_meta( $post->ID, 'evidence_hub_location_id', true ), 'evidence_hub_country');
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
							<?php foreach ($multivalues as $multivalue) {?>
								<li>
									<select name="<?php echo $name; ?>[]">
										<option value=""></option>
										<?php foreach ($option['options'] as $optionValue => $text) { ?>
											<option
												value="<?php echo $optionValue; ?>"
												<?php if ($optionValue == $multivalue) echo 'selected'; ?>
											>
												<?php echo $text; ?>
											</option>
										<?php } ?>
									</select>
								</li>
							<?php } ?>
						</ul>
						<a class="add-another" href="#">add another</a>
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