<?php

/**
 * Citation Manager Settings Interface
 * @author Mike Gogulski http://www.nostate.com/
 *
 * TODO: Add "Manage all citations" capability
 *
 **/

class CitationManagerSettings {

	// Initialize settings interface
	function CitationManagerSettings() {
		// Add settings
		add_action('admin_init', array($this, 'addCitationManagerSettings'));

		// Add settings page
		add_action('admin_menu', array($this, 'addCitationManagerSettingsPage'));
	}

	// Add settings page to admin menu
	function addCitationManagerSettingsPage() {
		# Add the options page
		add_options_page('Citation Manager Settings', 'Citation Manager', 8, basename(__FILE__), array(
			$this,
			'addSettings'
		));
	}

	// Add settings page
	function addCitationManagerSettings() {
		// Register settings
		if (function_exists('register_setting')) {
			register_setting('citationmanager', 'cit_introtext', '');
			register_setting('citationmanager', 'cit_outrotext', '');
			register_setting('citationmanager', 'cit_targetblank', '');
			register_setting('citationmanager', 'cit_sortorder', '');
		}
	}

	function HtmlPrintBoxHeader($id, $title) {
?>
		<div id="<?php echo $id; ?>" class="postbox">
			<h3 class="hndle"><span><?php echo $title ?></span></h3>
       	<div class="inside">
       	<?php

	}

	function HtmlPrintBoxFooter() {
?>
       	</div>
       	</div>
       	<?php

	}

	// Display settings
	function addSettings() {
		// Store options if postback
		if (isset($_POST['Submit'])) {
			// Prevent attacks
			//if (wp_verify_nonce($_POST['citationmanager-nonce-key'], 'citationmanager')) {
			if (true) {
				// Update options
				update_option('cit_introtext', $_POST['cit_introtext']);
				update_option('cit_outrotext', $_POST['cit_outrotext']);
				update_option('cit_targetblank', $_POST['cit_targetblank']);
				update_option('cit_sortorder', $_POST['cit_sortorder']);

				// Report update
				echo "<div class='updated fade'><p><strong>Citation Manager settings saved.</strong></p></div>";
			}
		}
?>

		<div class="wrap">
			<form method="post" action="options.php">
				<input type="hidden" name="action" value="update" />
				<?php wp_nonce_field('update-options'); ?>
				<input type="hidden" name="page_options" value="cit_introtext,cit_outrotext,cit_sortorder,cit_targetblank" />

				<h2>Citation Manager Settings</h2>

				<div id="poststuff" class="metabox-holder has-right-sidebar">
					<div class="inner-sidebar">
						<div id="side-sortables" class="meta-box-sortabless ui-sortable" style="position:relative;">
							<?php $this->HtmlPrintBoxHeader('citation_manager_support', 'Support this plugin!'); ?>
								<p>Your gifts in support of this plugin are <em>greatly</em> appreciated! Thank you!</p>
								<ul>
									<li><a class="cit_button cit_giftPayPal" href="https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&amp;business=mikegogulski%40gmail%2ecom&amp;item_name=Wordpress%20Plugin%20(Citation%20Manager)&amp;no_shipping=0&amp;no_note=1&amp;tax=0&amp;currency_code=USD&amp;charset=UTF%2d8&amp;lc=US" title="Send a gift via PayPal">Send a gift via PayPal</a></li>
									<li><a class="cit_button cit_giftRevmoney" href="https://www.revolutionmoneyexchange.com/paybyrmx.aspx?sellertype=PAY&amp;selleremail=mike%40gogulski.com&amp;amount=&amp;desc=Citation%20Manager%20gift%20to%20Mike%20Gogulski" title="Send a gift via Revolution MoneyExchange">Send a gift via Revolution MoneyExchange</a>
									<li><a class="cit_button cit_giftPecunix" href="https://www.pecunix.com/pay.me?mike@gogulski.com" title="Send a gift via Pecunix">Send a gift via Pecunix</a>
									<li><a class="cit_button cit_giftMoneybookers" href="https://www.moneybookers.com/app/?rid=3271107" title="Send a gift via Moneybookers">Sign up and send a gift to mike@gogulski.com via Moneybookers</a>
									<li><a class="cit_button cit_giftAmazon" href="http://www.amazon.co.uk/registry/wishlist/1VP7NMTZDHP8F" title="My Amazon wishlist">My Amazon wishlist</a></li>
								</ul>
							<?php $this->HtmlPrintBoxFooter(); ?>
						</div>
					</div>

					<div class="has-sidebar cit-padded" >
						<div id="post-body-content" class="has-sidebar-content">
							<div class="meta-box-sortabless">
							<?php $this->HtmlPrintBoxHeader('pingcrawl_options', 'Options'); ?>
								<p>Citation Manager by <a href="http://www.nostate.com/" title="nostate.com">Mike Gogulski</a>.</p>
								<p>Citation Manager is intended for management of external (print, web, broadcast, etc.) citations and mentions of WordPress content.
								Unlike the trackback and pingback capabilities, it allows for the manual addition of citation data.</p>
								<p>Citation Manager was developed for the <a href="http://c4ss.org" title="Center for a Stateless Society">Center for a Stateless Society</a>.</p>
								<table class="cit_settings">
									<tr class="cit_settings_row">
										<td><label for="cit_introtext">Intro text:</label></td>
										<td>
											<input type="text" name="cit_introtext" value="<?php echo htmlentities(stripslashes(get_option('cit_introtext')), ENT_QUOTES); ?>" />
											<br /><span class="setting-description">Introductory text to be displayed before the citation list.</span>
										</td>
									</tr>
									<tr class="cit_settings_row">
										<td><label for="cit_outrotext">Outro text:</label></td>
										<td>
											<input type="text" name="cit_outrotext" value="<?php echo htmlentities(stripslashes(get_option('cit_outrotext')), ENT_QUOTES); ?>" />
											<br /><span class="setting-description">Text to be displayed after the citation list.</span>
										</td>
									</tr>
									<tr class="cit_settings_row">
										<td><strong>Citation sort order</strong></td>
										<td><div style="width: auto;">
											<input style="width: auto;" type="radio" name="cit_sortorder" id="cit_sort_desc" value="DESC" <?php
												if (get_option("cit_sortorder") == "DESC")
													echo 'checked="checked"';
											?> /><label for="cit_sort_desc">Most recent first</label>
											<input style="width: auto;" type="radio" name="cit_sortorder" id="cit_sort_asc" value="ASC" <?php
												if (get_option("cit_sortorder") == "ASC")
													echo 'checked="checked"';
											?> /><label for="cit_sort_asc">Oldest first</label>
											<br /><br /><span class="setting-description">The order in which citations are sorted on output. Note that the sorts is done based on post ID and citation meta ID, not the post date or citation date.</span>
											</div>
										</td>
									</tr>
									<tr class="cit_settings_row">
										<td><label for="cit_targetblank">Open external links in a new window?</label></td>
										<td>
											<input style="width: auto;" type="checkbox" name="cit_targetblank" value="1" <?php
												if (get_option("cit_targetblank") == "1")
													echo 'checked="checked"';
											?> />
											<br /><br /><span class="setting-description">If enabled, clicking on links to external websites in the citation display will open a new browser window.</span>
										</td>
									</tr>
								</table>

								<p class="submit"><input type="submit" name="Submit" value="Save Changes" /></p>
							<?php $this->HtmlPrintBoxFooter(); ?>
							</div>
						</div>
					</div>
				</div>
			</form>
		</div>
		<?php

	}
}

// Start settings interface
$citationmanager_settings = new CitationManagerSettings();
?>
