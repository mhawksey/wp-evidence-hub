<?php



/**

 * Output meta box

 * @author Mike Gogulski

 */

class CitationManagerMetabox {



  // Set up meta box for use

  function CitationManagerMetabox() {

    add_action('wp_ajax_newcitationbox', array($this, 'newCitationBox'));

    add_action('admin_init', array($this, 'adminInit'));

    add_action('admin_head', array($this, 'addJavascript'));

  }



  // Hooks to run when admin interface is initialized

  function adminInit() {

    // Citation creation methods needed by WordPress

    add_action('save_post', array($this, 'saveForm'));

    add_action('delete_post', array($this, 'deleteForm'));



    // Add meta box to interface

    add_meta_box('citationmanager', 'Citation Manager', array($this, 'editForm'), 'post', 'normal');

  }



  // The Citation Manager edit form

  public static function editForm() {

    global $wpdb, $post;

    $citation_count = 0;

    // If this is a valid post, fetch citation list

    if ($post->ID)

      $citations = $wpdb->get_results("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$post->ID} AND meta_key = 'citation' ORDER BY meta_id", ARRAY_A);

?>



		<div id="citationmanager_citations">



		<?php



    // If the list of citations is not empty

    if (!empty($citations)) {

      // Loop through each citation

      foreach ($citations as $citation) {

        // If the citation count is greater than none, add to a list of valid citations

        if ($citation_count > 0)

          $cit_citation_ids .= ',';

        $citation_count++;

        // Append the current citation id to a list of citation ids

        $cit_citation_ids .= $citation['meta_id'];

        // Grab the contents of the citation

        $citation_value = unserialize(base64_decode($citation['meta_value']));



        // Begin citation HTML

        // TODO: Validate date format

        // TODO: Validate URL format

?>

			<table cellpadding="3" class="cit_citation" id="cit_citation_<?php echo $citation['meta_id']; ?>">

				<tr>

					<td class="cit-title">Author</td>

					<td class="cit-element" colspan="3"><input type="text" name="cit_author_<?php echo $citation['meta_id']; ?>" class="cit_author" value="<?php echo htmlentities(stripslashes($citation_value['author']), ENT_QUOTES); ?>" /></td>

				</tr>

				<tr>

					<td class="cit-title">Title</td>

					<td class="cit-element" colspan="3"><input type="text" name="cit_title_<?php echo $citation['meta_id']; ?>" class="cit_title" value="<?php echo htmlentities(stripslashes($citation_value['title']), ENT_QUOTES); ?>" /></td>

				</tr>

				<tr>

					<td class="cit-title">Publication</td>

					<td class="cit-element" colspan="3"><input type="text" name="cit_publication_<?php echo $citation['meta_id']; ?>" class="cit_publication" value="<?php echo htmlentities(stripslashes($citation_value['publication']), ENT_QUOTES); ?>" /></td>

				</tr>

				<tr>

					<td class="cit-title">Page/issue/etc.</td>

					<td class="cit-element"><input type="text" name="cit_where_<?php echo $citation['meta_id']; ?>" class="cit_where" value="<?php echo htmlentities(stripslashes($citation_value['where']), ENT_QUOTES); ?>" /></td>

					<td class="cit-title">Date</td>

					<td class="cit-element"><input type="text" name="cit_date_<?php echo $citation['meta_id']; ?>" class="cit_date" value="<?php echo htmlentities(stripslashes($citation_value['date']), ENT_QUOTES); ?>" /></td>

				</tr>

				<tr>

					<td class="cit-title">URL</td>

					<td class="cit-element" colspan="2"><input type="text" name="cit_url_<?php echo $citation['meta_id']; ?>" class="cit_url" value="<?php echo htmlentities(stripslashes($citation_value['url']), ENT_QUOTES); ?>" /></td>

					<td class="cit-update"><input name="delete_cit_<?php echo $citation['meta_id']; ?>" type="button" class="button" style="margin: 0 5px; width: 80%;" value="Delete Citation" onclick="delete_citation(<?php echo $citation['meta_id']; ?>);" /></td>

				</tr>

			</table>



		<?php } ?>

		<?php } ?>

		<input name="cit_citation_ids" type="hidden" value="<?php echo $cit_citation_ids; ?>" />

		<input name="cit_new_citation_ids" id="cit_new_citation_ids" type="hidden" value="" />

		<input name="cit_delete_citation_ids" id="cit_delete_citation_ids" type="hidden" value="" />

		<input name="cit_ignore_citation_ids" id="cit_ignore_citation_ids" type="hidden" value="" />

		</div>



		<table cellpadding="3" class="cit_new_citation">

			<tr>

				<td class="submit">

					<input name="add_citation" id="add_citation_button" type="button" class="" value="Add a new citation" onclick="do_add_citation();" />

				</td>

			</tr>

		</table>

	<?php



  }// citationmanager_edit_form()



  // Save citation information

  function saveForm($postID) {

    global $wpdb;



    // Security prevention

    if (!current_user_can('edit_post', $postID))

      return $postID;

    // Extra security prevention

    if (isset($_POST['comment_post_ID']))

      return $postID;

    if (isset($_POST['not_spam']))

      return $postID;// akismet fix

    if (isset($_POST['comment']))

      return $postID;// moderation.php fix

    // Ignore save_post action for revisions and autosave

    if (wp_is_post_revision($postID) || wp_is_post_autosave($postID))

      return $postID;



    // Add new citations

    if ($_POST['cit_new_citation_ids'] != '') {

      $cit_new_citation_ids = explode(',', substr($_POST['cit_new_citation_ids'], 0, -1));

      $cit_ignore_citation_ids = explode(',', substr($_POST['cit_ignore_citation_ids'], 0, -1));

      $added_citation_ids = array();

      foreach ($cit_new_citation_ids AS $cit_citation_id) {

      	error_log("processing cit id " . $cit_citation_id);

        $cit_citation_id = (int) $cit_citation_id;



        // Don't create citations when there is no data.

        if (empty($_POST['cit_new_title_' . $cit_citation_id]) && empty($_POST['cit_new_publication_' . $cit_citation_id]) && empty($_POST['cit_new_date_' . $cit_citation_id]) && empty($_POST['cit_new_where_' . $cit_citation_id]) && empty($_POST['cit_new_author_' . $cit_citation_id]) && empty($_POST['cit_new_url_' . $cit_citation_id])) {

          $keys = array_keys($cit_new_citation_ids, $cit_citation_id);

          error_log("not creating empty citation");

          foreach ($keys as $key)

            unset($cit_new_citation_ids[$key]);

          continue;

        }



        // Check if the citation is on the ignore list

        if (!in_array($cit_citation_id, $cit_ignore_citation_ids)) {

          $citation = base64_encode(serialize(array(

            'title' => "",

            'publication' => "",

            'date' => "",

            'where' => "",

            'author' => "",

            'url' => ""

          )));

          add_post_meta($postID, 'citation', $citation);



          $added_citation_ids[] = $cit_citation_id;

        }

      }

    }



    // Update citations

    if (isset($_POST['cit_citation_ids'])) {

      $cit_citation_ids = explode(',', $_POST['cit_citation_ids']);

      $cit_new_citation_ids = explode(',', substr($_POST['cit_new_citation_ids'], 0, -1));

      $cit_ignore_citation_ids = explode(',', substr($_POST['cit_ignore_citation_ids'], 0, -1));

      $cit_delete_citation_ids = explode(',', substr($_POST['cit_delete_citation_ids'], 0, -1));

      $citations = $wpdb->get_results("SELECT meta_id, meta_value FROM {$wpdb->postmeta} WHERE post_id = {$postID} AND meta_key = 'citation' ORDER BY meta_id", ARRAY_A);

      $i = 0;



      if ($_POST['cit_citation_ids'] != '') {

        foreach ($cit_citation_ids as $cit_citation_id) {

          // Ensure we're dealing with an ID

          $cit_citation_id = (int) $cit_citation_id;



          $citation = base64_encode(serialize(array(

            'title' => $_POST['cit_title_' . $cit_citation_id],

            'publication' => $_POST['cit_publication_' . $cit_citation_id],

            'date' => $_POST['cit_date_' . $cit_citation_id],

            'where' => $_POST['cit_where_' . $cit_citation_id],

            'author' => $_POST['cit_author_' . $cit_citation_id],

            'url' => $_POST['cit_url_' . $cit_citation_id]

          )));



          update_post_meta($postID, 'citation', $citation, $citations[$i]['meta_value']);



          $i++;



          // Delete citation

          if (in_array($cit_citation_id, $cit_delete_citation_ids)) {

            delete_meta($cit_citation_id);

          }

        }

      }

      if (count($added_citation_ids) > 0) {

        foreach ($added_citation_ids as $cit_citation_id) {

          // Ensure we're dealing with an ID

          $cit_citation_id = (int) $cit_citation_id;



          // Check if the citation is on the ignore list

          if (!in_array($cit_citation_id, $cit_ignore_citation_ids)) {

            $citation = base64_encode(serialize(array(

              'title' => $_POST['cit_new_title_' . $cit_citation_id],

              'publication' => $_POST['cit_new_publication_' . $cit_citation_id],

              'date' => $_POST['cit_new_date_' . $cit_citation_id],

              'where' => $_POST['cit_new_where_' . $cit_citation_id],

              'author' => $_POST['cit_new_author_' . $cit_citation_id],

              'url' => $_POST['cit_new_url_' . $cit_citation_id]

            )));



            $meta_id = $citations[$i]['meta_id'];

            // Update citation

            $wpdb->query("UPDATE {$wpdb->postmeta} SET meta_value = '$citation' WHERE meta_id = '$meta_id'");

            $i++;

          }

        }

      }

    }



    return $postID;

  }// citationmanager_save_form()



  // Create a box for editing citation

  function newCitationBox() {

    // Check AJAX referer

    check_ajax_referer('citationmanager');

    // Get the required variables

    $id = $_POST['cit_id'];

?>

		<table cellpadding="3" class="cit_citation" id="new_citation_<?php echo $id; ?>">

			<tr>

				<td class="cit-title">Author</td>

				<td class="cit-element" colspan="3"><input type="text" name="cit_new_author_<?php echo $id; ?>" class="cit_author" value="" /></td>

			</tr>

			<tr>

				<td class="cit-title">Title</td>

				<td class="cit-element" colspan="3"><input type="text" name="cit_new_title_<?php echo $id; ?>" class="cit_title" value="" /></td>

			</tr>

			<tr>

				<td class="cit-title">Publication</td>

				<td class="cit-element" colspan="3"><input type="text" name="cit_new_publication_<?php echo $id; ?>" class="cit_publication" value="" /></td>

			</tr>

			<tr>

				<td class="cit-title">Page/issue/etc.</td>

				<td class="cit-element"><input type="text" name="cit_new_where_<?php echo $id; ?>" class="cit_where" value="" /></td>

				<td class="cit-title">Date</td>

				<td class="cit-element"><input type="text" name="cit_new_date_<?php echo $id; ?>" class="cit_date" value="" /></td>

			</tr>

			<tr>

				<td class="cit-title">URL</td>

				<td class="cit-element" colspan="2"><input type="text" name="cit_new_url_<?php echo $id; ?>" class="cit_url" value="" /></td>

				<td class="cit-update"><input name="delete_cit_<?php echo $id; ?>" type="button" class="button" style="margin: 0 5px; width: 80%;" value="Delete Citation" onclick="delete_new_citation(<?php echo $id; ?>);" /></td>

			</tr>

		</table>



		<?php



  }



  // Add Javascript for citation management

  function addJavascript() {

?>

<script type='text/javascript'>

/* <![CDATA[ */



			jQuery(document).ready(function() {

				jQuery('#add_citation_button').click(function() {

					var button = this;

					button.disabled = true;

					setTimeout(function() { button.disabled = false; }, 3000);

				});

			});



			var newCitationId = 1000;



			// This function will add Javascript to make a new citation appear on the post without refreshing the page

			function do_add_citation() {

				// Grab the variables

				var existingCitationIds = jQuery("#cit_new_citation_ids").val();



				// Create the citation box

				jQuery.ajax({

					type: "post",

					url: "<?php echo admin_url('admin-ajax.php'); ?>",

					data: { action: 'newcitationbox', cit_id: newCitationId, _ajax_nonce: '<?php echo wp_create_nonce("citationmanager"); ?>' },

					success: function(newCitation) {

						// Add the citation to the page

						jQuery(newCitation).appendTo("#citationmanager_citations");

						jQuery("#cit_new_citation_ids").val(existingCitationIds + newCitationId + ',');



						// Reset the add form

						jQuery("table.cit_new_citation input.cit_new_file").val('');



						// Increase the citation counter

						newCitationId = newCitationId + 1;

					},

					error: function() {

						alert('Failed to add citation box.');

					}

				});

			}



			// This function will remove the HTML for an citation, marking the citation for deletion on the next save

			function delete_citation(id) {

				var existingRemovals = jQuery("#cit_delete_citation_ids").val();



				confirm_delete = confirm("Are you sure you want to delete this citation?");



				if (confirm_delete === true) {

					jQuery("#cit_citation_" + id).hide('slow');

					jQuery("#cit_delete_citation_ids").val(existingRemovals + id + ',');

				}

			}



			// This function will remove the citation for citations that have been added without saving

			function delete_new_citation(id) {

				var existingRemovals = jQuery("#cit_ignore_citation_ids").val();



				confirm_delete = confirm("Are you sure you want to delete this citation?");



				if (confirm_delete === true) {

					jQuery("#new_citation_" + id).hide('slow');

					jQuery("#cit_ignore_citation_ids").val(existingRemovals + id + ',');

				}

			}

/* ]]> */



		</script>

		<?php

  }

}



// Start the metabox

$citationmanager_metabox = new CitationManagerMetabox();

?>

