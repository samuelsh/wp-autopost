<?php

/**
 *Plugin Name: WP autopost
 *Plugin URI: http:
 *Description: A plugin that helps to import the data's from a CSV file.
 *Version: 1.0
 *Author: dogen
 *Author URI: http://doge-of-koga.livejournal.com
 *
 * Copyright (C) 2013 dogen
 *
 *
 * @link http://doge-of-koga.livejournal.com
 ***********************************************************************************************
 */
/****************************************************************************************************/
/* This script inserts post automatically to Word Press												*/
/****************************************************************************************************/

ob_start();

if(!function_exists('wp_get_current_user')) {
    include(ABSPATH . "wp-includes/pluggable.php"); 
}

class CSVImporterPlugin {

function process_option($name, $default, $params) {
        if (array_key_exists($name, $params)) {
            $value = stripslashes($params[$name]);
        } elseif (array_key_exists('_'.$name, $params)) {
            // unchecked checkbox value
            $value = stripslashes($params['_'.$name]);
        } else {
            $value = null;
        }
        $stored_value = get_option($name);
        if ($value == null) {
            if ($stored_value === false) {
                if (is_callable($default) &&
                    method_exists($default[0], $default[1])) {
                    $value = call_user_func($default);
                } else {
                    $value = $default;
                }
                add_option($name, $value);
            } else {
                $value = $stored_value;
            }
        } else {
            if ($stored_value === false) {
                add_option($name, $value);
            } elseif ($stored_value != $value) {
                update_option($name, $value);
            }
        }
        return $value;
    }

	
function post()  {
	$row = 1;
	//print_r($_FILES['csv_import']);
	if (($handle = fopen($_FILES['csv_import']['tmp_name'], "r")) !== FALSE) {
		while (($data = fgetcsv($handle,",")) !== FALSE) {
			$num = count($data);
			//echo "<p> $num fields in line $row: <br /></p>\n";
			// ($c=0; $c < $num; $c++) {
			//    echo $data[$c] . "<br />\n";
			//

			if ($row > 1) {
				$user_name=str_replace(" ","",trim($data[1]) );
				$email_address="test@test.com";
				
				if( null == username_exists( $user_name ) ) {
					  // Generate the password and create the user
					  $password = wp_generate_password( 12, false );
					  $user_id = wp_create_user( $user_name, $password, $email_address );

					  // Set the nickname
					  wp_update_user(
						array(
						  'ID'          =>    $user_id,
						  'nickname'    =>    $email_address
						)
					  );

					  // Set the role
					  $user = new WP_User( $user_id );
					  $user->set_role( 'author' );

					  // Email the user
					  //wp_mail( $email_address, 'Welcome!', 'Your Password: ' . $password );

				} // end if
				
				//echo "title: ".$data[4]."<br/>";
				//echo "content: ".$data[5]."<br/>";
				//echo "---------------------<br/>";
				
				$mycat = $data[3];
				$cid = wp_create_category($mycat);
				
				$the_post = array( 
				'post_date' => date("Y-m-d H:i:s"),
				'post_author' => username_exists($user_name),
				'post_title' => $data[4],
				'post_content' => $data[5],
				'post_excerpt' => $data[4], 
				'post_status' => 'publish',
				'post_type' => 'post',
				'post_category' => array($cid),
				'tags_input' => $data[6].",".$data[7].",".$data[8].",".$data[9].",".$data[10],
				'tax_input' => array("Rating" => $data[11])
				);
	
				// Insert the post into the database
				$post_id = wp_insert_post( $the_post, $wp_error = true );
				add_post_meta($post_id, 'Rating', $data[11]);
				
				text_to_image($data[2]);
				
				$wp_filetype = wp_check_filetype(ABSPATH . 'wp-content/plugins/wp-autopost/url.png', null );
				$wp_upload_dir = wp_upload_dir();
				
				$attachment = array(
				'guid' => $wp_upload_dir['path'].'/'.$data[2].'url.png', 
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => preg_replace('/\.[^.]+$/', '', $data[2]),
				'post_content' => '',
				'post_status' => 'inherit',
	
				);
				
				require_once(ABSPATH . 'wp-admin/includes/image.php');
				require_once(ABSPATH . "wp-admin" . '/includes/file.php');
				require_once(ABSPATH . "wp-admin" . '/includes/media.php');
				
				$attach_id = wp_insert_attachment( $attachment, $wp_upload_dir['path'].'/'.$data[2].'-url.png', $post_id );
				//$attach_id = media_handle_upload( $wp_upload_dir['path'].'/'.$data[2].'-url.png', $post_id );
				$attach_data = wp_generate_attachment_metadata( $attach_id, $wp_upload_dir['path'].'/'.$data[2].'-url.png' );
				wp_update_attachment_metadata( $attach_id, $attach_data );
				//update_post_meta($post_id,'_thumbnail_id',$attach_id);
				
				$post = get_post($post_id,'ARRAY_A');
				$image = wp_get_attachment_image_src( $attach_id, 'Large' );
				$image_tag = '<p><img src="'.$image[0].'" width="400px" height="30px" /></p>';

				//add image under the content
				$post['post_content'] = $image_tag . $post['post_content'];

				//add image above the content
				//$post['post_content'] = $post['post_content'] . $image_tag;

				$post_id =  wp_update_post( $post );
				
				//if($return)
				//	echo $return->get_error_message();
			}
			$row++;
		}    
	}
	fclose ($handle);
}

/**
     * Plugin's interface
     *
     * @return void
     */
function form() {
        $opt_draft = $this->process_option('csv_importer_import_as_draft',
            'publish', $_POST);
        $opt_cat = $this->process_option('csv_importer_cat', 0, $_POST);

        if ('POST' == $_SERVER['REQUEST_METHOD']) {
            $this->post(compact('opt_draft', 'opt_cat'));
        }

        // form HTML {{{
?>

<div class="wrap">
    <h2>Import CSV</h2>
    <form class="add:the-list: validate" method="post" enctype="multipart/form-data">
        <!-- Import as draft -->
        <p>
        <input name="_csv_importer_import_as_draft" type="hidden" value="publish" />
        <label><input name="csv_importer_import_as_draft" type="checkbox" <?php if ('draft' == $opt_draft) { echo 'checked="checked"'; } ?> value="draft" /> Import posts as drafts</label>
        </p>

        <!-- Parent category
        <p>Organize into category <!--?php wp_dropdown_categories(array('show_option_all' => 'Select one ...', 'hide_empty' => 0, 'hierarchical' => 1, 'show_count' => 0, 'name' => 'csv_importer_cat', 'orderby' => 'name', 'selected' => $opt_cat));?><br/>
            <small>This will create new categories inside the category parent you choose.</small></p-->

        <!-- File input -->
        <p><label for="csv_import">Upload file:</label><br/>
            <input name="csv_import" id="csv_import" type="file" value="" aria-required="true" /></p>
        <p class="submit"><input type="submit" class="button" name="submit" value="Import" /></p>
    </form>
</div><!-- end wrap -->

<?php
        // end form HTML }}}

    }
	
}

function text_to_image( $str ) {
	
		// Create the image
		$im = imagecreatetruecolor(400, 30);

		// Create some colors
		$white = imagecolorallocate($im, 255, 255, 255);
		$grey = imagecolorallocate($im, 128, 128, 128);
		$black = imagecolorallocate($im, 0, 0, 0);
		imagefilledrectangle($im, 0, 0, 399, 29, $white);


		// Replace path by your own font path
				
		$font = ABSPATH . 'wp-content/plugins/wp-autopost/arial.ttf';

		// Add the text
		imagettftext($im, 20, 0, 10, 20, $black, $font, utf8_encode($str));

		// Using imagepng() results in clearer text compared with imagejpeg()
		$wp_upload_dir = wp_upload_dir();
		imagepng($im,$wp_upload_dir['path'].'/'.$str.'-url.png');
		
		imagedestroy($im);

	}
	
	
	

function csv_admin_menu() {
    require_once ABSPATH . '/wp-admin/admin.php';
    $plugin = new CSVImporterPlugin;
    add_management_page('edit.php', 'CSV Importer', 'manage_options', __FILE__,
        array($plugin,'form'));
}

add_action('admin_menu', 'csv_admin_menu');


///////////////////////////////////////////////////// Custom Metabox

add_action( 'admin_menu', 'my_create_post_meta_box' );
add_action( 'save_post', 'my_save_post_meta_box', 10, 2 );

function my_create_post_meta_box() {
	add_meta_box( 'my-meta-box', 'Rating', 'my_post_meta_box', 'post', 'normal', 'high' );
}

function my_post_meta_box( $object, $box ) { ?>
    <p>
		<label for="rating">Rating</label>
		<textarea name="rating" id="rating" cols="60" rows="4" tabindex="30" style="width: 97%;"><?php echo wp_specialchars( get_post_meta( $object->ID, 'Rating', true ), 1 ); ?></textarea>
		<input type="hidden" name="my_meta_box_nonce" value="<?php echo wp_create_nonce( plugin_basename( __FILE__ ) ); ?>" />
	</p>
<?php }

function my_save_post_meta_box( $post_id, $post ) {

	if ( !wp_verify_nonce( $_POST['my_meta_box_nonce'], plugin_basename( __FILE__ ) ) )
		return $post_id;

	if ( !current_user_can( 'edit_post', $post_id ) )
		return $post_id;

	$meta_value = get_post_meta( $post_id, 'Rating', true );
	$new_meta_value = stripslashes( $_POST['rating'] );

	if ( $new_meta_value && '' == $meta_value )
		add_post_meta( $post_id, 'Rating', $new_meta_value, true );

	elseif ( $new_meta_value != $meta_value )
		update_post_meta( $post_id, 'Rating', $new_meta_value );

	elseif ( '' == $new_meta_value && $meta_value )
		delete_post_meta( $post_id, 'Rating', $meta_value );
}

/////// HTML to Image //////

convert_url_to_image( $url $filepath ) {

	// The URL to get your HTML
	$url = "http://www.google.com/";
	 
	// Name of your output image
	$name = "example.jpg";
	 
	// Command to execute
	$command = "/usr/bin/wkhtmltoimage-i386 --load-error-handling ignore";
	 
	// Directory for the image to be saved
	$image_dir = "/var/www/images/";
	 
	// Putting together the command for `shell_exec()`
	$ex = "$command $url " . $image_dir . $name;
	 
	// The full command is: "/usr/bin/wkhtmltoimage-i386 --load-error-handling ignore http://www.google.com/ /var/www/images/example.jpg"
	// If we were to run this command via SSH, it would take a picture of google.com, and save it to /vaw/www/images/example.jpg
	 
	// Generate the image
	// NOTE: Don't forget to `escapeshellarg()` any user input!
	$output = shell_exec($ex);

}

?>