<fieldset id="evidence_hub_options">
	<table>
		<?php
			//Conferencer::add_meta($post);
			$user_option_count = 0;
		?>
		<?php foreach($sub_options as $name => $option) { ?>
			<?php
				//if ($option['position'] == 'side') continue;
				
				//$value = isset($$name) ? $$name : $post->$name;
		
				$user_option_count++;
				$name = "evidence_hub_$name";
				$value = get_post_meta( $post->ID, $name, true );
			?>
			
			<tr>
				<td class="label">
					<label for="<?php echo $name; ?>">
						<?php echo $option['label']; ?>
					</label>
				</td>
				
				<td class="input">
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
							name="<?php echo $name; ?>[date]"
							id="<?php echo $name.'_date'; ?>"
							value="<?php if ($value) echo date('j/n/Y', $value); ?>"
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
                        <tr>
                        	<td colspan="2"><div id="pronamicMapHolder">
								<?php if ($value)
                                    echo WP_Evidence_Hub::get_pronamic_google_map($value);
                                ?>	 
                        	</div></td>
                        </tr>
					<?php } else if ($option['type'] == 'select') { ?>
						<select
							name="<?php echo $name; ?>"
							id="<?php echo $name; ?>"
						>
							<option value=""></option>
							<?php foreach ($option['options'] as $optionValue => $text) { ?>
								<option
									value="<?php echo $optionValue; ?>"
									<?php if ($optionValue == $value) echo 'selected'; ?>>
									<?php echo $text; ?>
								</option>
							<?php } ?>
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
				</td>
			</tr>
		<?php } ?>
	</table>
	<?php  if (!$user_option_count) { ?>
		<p>There aren't any Evidence Hub options for <?php echo self::PLURAL; ?>.</p>
	<?php } ?>
    
</fieldset>