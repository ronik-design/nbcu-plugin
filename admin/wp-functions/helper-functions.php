<?php


class RonikHelper{
    // public function __construct() {
    //     add_action( 'init', [$this, 'ronikdesigns_svgconverter'] );
    // }

	// Create a like compare function.
	public function ronik_compare_like_compare($a_value , $b_value){
		if(stripos($a_value, $b_value) !== FALSE){
			return true;
		} else {
			return false;
		}
	}
	
	// Pretty much a function that removes redundant database uppdates by using a checker function
	public function ronik_database_update($db_table, $meta_name, $meta_value, $target_id_name = false, $f_user_id, $f_old_data, $f_new_data, $f_pool_large = false, $swap_level = "soft"){
		global $wpdb;
		$helper = new RonikHelper;

		if( $target_id_name ){
			// We check the wp_usermeta where the user id is the target id. 
			$wp_meta_datas = $wpdb->get_results("select * from $db_table where $target_id_name = '$f_user_id'");
		} else{
			if($f_pool_large){
				$wp_meta_datas = $wpdb->get_results("select * from $db_table where $meta_value LIKE '%$f_old_data%'");
			} else {
				$wp_meta_datas = $wpdb->get_results("select * from $db_table");
			}
		}

		if($wp_meta_datas){
			$return_validation = '';
			// Loop through all the rows.
			foreach($wp_meta_datas  as $key =>  $wp_meta_data){
				$f_type_meta_key = $meta_name;
				$f_type_meta_value = $meta_value;
				$f_meta_key = $wp_meta_data->$meta_name;
				$f_meta_value = $wp_meta_data->$meta_value;

				// Using the helper LIKE compare we check the current meta value vs the old user email.
				if($helper->ronik_compare_like_compare($f_meta_value , $f_old_data)){
			
					if (str_contains($f_meta_value, ';s:')) {
						$f_meta_value_mod = str_replace( 's:'.strlen($f_old_data).':"'.$f_old_data.'"', 's:'.strlen($f_new_data).':"'.$f_new_data.'"', $f_meta_value);
					} else{
						$f_meta_value_mod = str_replace( $f_old_data, $f_new_data, $f_meta_value);
					}

					if($swap_level == 'hard' || $swap_level == 'medium'){
						if($target_id_name){
							$wpdb->query( 
								$wpdb->prepare( 
									"UPDATE $db_table SET $f_type_meta_value = %s WHERE $target_id_name = %d AND $f_type_meta_key = %s", $f_meta_value_mod, $f_user_id, $f_meta_key  
								)
							);
						} else {
							if($f_pool_large){								
								// We must pass the LIKE%% to the argument otherwise it will filter the % out.
								$f_like_old = '%'.$wpdb->esc_like($f_old_data).'%';
								$wpdb->query( 
									$wpdb->prepare( 
										"UPDATE $db_table SET $f_type_meta_value = %s WHERE $meta_value LIKE %s AND $f_type_meta_key = %s", $f_meta_value_mod, $f_like_old, $f_meta_key  
									)
								);	
							} else {
								$wpdb->query( 
									$wpdb->prepare( 
										"UPDATE $db_table SET $f_type_meta_value = %s WHERE $f_type_meta_key = %s", $f_meta_value_mod, $f_meta_key  
									)
								);	
							}
						}
					} 	
					$return_validation .= $f_meta_key . ', ';
				}
			}
			if(empty($return_validation)){
				$return_validation = ' Data Not Found.';
			}
			return ' Database Table '.$db_table.':' . $return_validation . ' ';		
		}
	}

	// All we do is store encrypted data and then we output the data.
	public function ronik_encrypt_data_meta($input_data){
		$helper = new RonikHelper;
		// $user_encrypt_data = get_user_meta( $input_data, 'ronikdesign_initialization_encrypt_data', true );
		// if(!$user_encrypt_data){
		// 	$user_encrypt_data = update_user_meta( $input_data, 'ronikdesign_initialization_encrypt_data', $helper->ronik_encrypt_data($input_data) );
		// }
		// return $user_encrypt_data;

		// Above code was a thought but it seems to not work correctly. 
		return $helper->ronik_encrypt_data($input_data);
	}

	// Might be overkill but we encrypt user login and secrete password.
	public function ronik_encrypt_data($input_data){
		if (!function_exists('ronik_encrypt')) {
			function ronik_encrypt($data, $key){
				$helper = new RonikHelper;

				// Remove the base64 encoding from our key
				$encryption_key = base64_decode($key);
				// Generate an initialization vector
				// $ivlen = 16;
				// $iv = random_bytes($ivlen);
				// $initialization_vector = $iv ;
				// Length aes-128-CBC treats the 128 bits of a block as 16 bytes
				$ivlen = openssl_cipher_iv_length('aes-128-CBC');
				// Generate a pseudo-random string of bytes
				$initialization_vector = openssl_random_pseudo_bytes($ivlen);
				// In order to store the iv we have to format it to bin2hex
				// Ideally we would store the true file but due to santization issue and base_64 modification of the true IV this is the only decent option. Not ideal but good enough.
				// $reformated_iv = bin2hex($initialization_vector);
				// // We have to check the length in bits so bin2hex converts to 32 so we remove the last 16 digits
				// if(mb_strlen($reformated_iv, '8bit') > 16){
				// 	$reformated_iv = substr($reformated_iv, 0, -16);
				// }

				$directory = dirname(__FILE__, 2).'/ronik_iv/';
				//If the directory doesn't already exists.
				if(!is_dir($directory)){
					//Create our directory.
					mkdir($directory, 0777, true);
					sleep(1);
				}
				// We store the IV path in the user meta. Then we create the file as well.
				// Pretty much everytime the page reloads we start from scratch with a random IV. 
				$reformated_iv = $initialization_vector;
				$fp = fopen($directory . "/".get_current_user_id().".txt","wb");
				fwrite($fp, $reformated_iv);
				fclose($fp);
				update_user_meta( get_current_user_id(), 'ronikdesign_initialization_vector', $directory . get_current_user_id().".txt" );

				$helper->ronikdesigns_write_log_devmode('encrypt', 'low', 'auth');
				$helper->ronikdesigns_write_log_devmode($reformated_iv, 'low', 'auth');

				// $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
				// Encrypt the data using AES 256 encryption in CBC mode using our encryption key and initialization vector.
				$encrypted = openssl_encrypt($data, 'aes-128-CBC', $encryption_key, 0, $reformated_iv);
				// $encrypted = openssl_encrypt($data, 'aes-256-cbc', $encryption_key, 0, $iv);
				// The $iv is just as important as the key for decrypting, so save it with our encrypted data using a unique separator (::)
				return base64_encode($encrypted . ':::' . $reformated_iv);
			}
		}
		//$key is our base64 encoded 256bit key. Store and define this key in a config file.
		$key = '1nA5Nk9iXTh6IY4cMJyuTRzC+NmHGzhatAnynng4UIw=';
		//our data to be encoded
		$password_plain = 'data='.$input_data.';time=' . time();
		//since it's base64 encoded, it can go straight into a varchar or text database field without corruption worry
		$password_encrypted = ronik_encrypt($password_plain, $key);
		// We have to rawurlencode the password_encrypted otherwise there will be an error 1 out of 3 tries..
		return rawurlencode($password_encrypted);
	}
	// 3600 == 1 hr
	// $time=3600*24
	// 604800 = 7 days * 4 weeks
	public function ronik_decrypt_data($input_data, $time=(604800*4)){
		// Decrypt the login data we also have a timer.
		if (!function_exists('ronik_decrypt')) {
			function ronik_decrypt($data, $key){
				$helper = new RonikHelper;

				// Remove the base64 encoding from our key
				$encryption_key = base64_decode($key);
				// To decrypt, split the encrypted data from our IV - our unique separator used was "::"
				list($encrypted_data, $iv) = explode(':::', base64_decode($data), 2);

				$iv_path = get_user_meta( get_current_user_id(), 'ronikdesign_initialization_vector', true );
				// If no path is in the metadata we fall back to the previous passed over IV.
				if($iv_path){
					if(file_exists($iv_path)){
						$iv = file_get_contents($iv_path, true);
					}
				}

				$helper->ronikdesigns_write_log_devmode('decrypt', 'low', 'auth');
				$helper->ronikdesigns_write_log_devmode($iv, 'low', 'auth');

				// return openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
				return openssl_decrypt($encrypted_data, 'aes-128-CBC', $encryption_key, 0, $iv);
			}
		}
		//$key is our base64 encoded 256bit key. Store and define this key in a config file.
		$key = '1nA5Nk9iXTh6IY4cMJyuTRzC+NmHGzhatAnynng4UIw=';
		// $password_encrypted = rawurldecode( $input_data );
		$password_encrypted = $input_data;

		//now we turn our encrypted data back to plain text
		$password_decrypted = ronik_decrypt($password_encrypted, $key);
		$piecesArray = explode(";", $password_decrypted);

		$helper = new RonikHelper;
		$helper->ronikdesigns_write_log_devmode('password_encrypted', 'low', 'auth');
		$helper->ronikdesigns_write_log_devmode($password_encrypted, 'low', 'auth');
		$helper->ronikdesigns_write_log_devmode($piecesArray, 'low', 'auth');


		if ($piecesArray) {
			$info_data = str_replace("data=", "", $piecesArray[0]);
			$timestamp = str_replace("time=", "", $piecesArray[1]);
			$dif = (time() - intval($timestamp));
			// Cancel log in request. If more than desired seconds has passed.
			if ($dif > $time) {
				$helper->ronikdesigns_write_log_devmode('Cancel log in request. More than desired seconds has passed', 'low', 'auth');
				return false;
			} else {
				return $info_data;
			}
		}
	}









	// Creates an encoded svg for src, lazy loading.
    public function ronikdesigns_svgplaceholder($imgacf = null, $advanced_mode = null, $custom_css = null) {
		if( !is_array($imgacf) && !empty($imgacf) ){
			$img = wp_get_attachment_image_src( attachment_url_to_postid($imgacf) , 'full' );
			$viewbox = "width='{$img[1]}' height='{$img[2]}' viewBox='0 0 {$img[1]} {$img[2]}'";
			$width  = $img[1];
			$height = $img[2];
			$url = $imgacf;
			$alt = '';
		} else {
			$iacf = $imgacf;
			if ($iacf) {
				if ($iacf['alt']) {
					$alt = $iacf['alt'];
				}
				if ($iacf['url']) {
					$url = $iacf['url'];
				}
				if ($iacf['width']) {
					$width = $iacf['width'];
				}
				if ($iacf['height']) {
					$height = $iacf['height'];
				}
				$viewbox = "width='{$width}' height='{$height}' viewBox='0 0 {$width} {$height}'";
			} else {
				$url = '';
				$alt = '';
				$viewbox = "viewBox='0 0 100 100'";
			}
		}
		if($advanced_mode) {
			$svg_url = "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' {$viewbox}%3E%3C/svg%3E";
		?>
			<img data-width="<?= $width; ?>" data-height="<?= $height; ?>" class="<?= $custom_css; ?> lzy_img reveal-disabled" src="<?= $svg_url; ?>" data-src="<?= $url; ?>" alt="<?= $alt; ?>">
		<?php } else{
			return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' {$viewbox}%3E%3C/svg%3E";
		}
    }

	// Creates an inline background image.
    public function ronikBgImage($image) {
		return ' background-image: url(\'' . $image['url'] . '\'); ';
	}

	// Write error logs cleanly.
    public function ronikdesigns_write_log($log) {
		$f_error_email = get_field('error_email', 'option');
		if ($f_error_email) {
			// Remove whitespace.
			$f_error_email = str_replace(' ', '', $f_error_email);
			// Lets run a backtrace to get more useful information.
			$t = debug_backtrace();
			$t_file = 'File Path Location: ' . $t[0]['file'];
			$t_line = 'On Line: ' .  $t[0]['line'];
			$to = $f_error_email;
			$subject = 'Error Found';
			$body = 'Error Message: ' . $log . '<br><br>' . $t_file . '<br><br>' . $t_line;
			$headers = array('Content-Type: text/html; charset=UTF-8');
			wp_mail($to, $subject, $body, $headers);
		}
		if (is_array($log) || is_object($log)) {
			error_log(print_r('<----- ' . $log . ' ----->', true));
		} else {
			error_log(print_r('<----- ' . $log . ' ----->', true));
		}
	}
	// Write error logs cleanly.
	public function ronikdesigns_write_log_devmode($log, $severity_level='low', $error_type='general') {
		// https://together.nbcudev.local/wp-admin/?ronik_debug=valid
		// LOG all general errors Logs.
		if( isset($_GET['ronik_debug']) && $_GET['ronik_debug'] == 'valid' ){
			setcookie("RonikDebug", 'valid', time()+1500);  /* expire in 25 min */
		}
		// https://together.nbcudev.local/wp-admin/?ronik_debug=auth
		// AUTH represents the combination of MFA and 2FA Process..,
		if( isset($_GET['ronik_debug']) && $_GET['ronik_debug'] == 'auth' ){
			setcookie("RonikDebug", 'auth', time()+1500);  /* expire in 25 min */
		}
		// https://together.nbcudev.local/wp-admin/?ronik_debug=auth_mfa
		// AUTH MFA errors logs all MFA Processes
		if( isset($_GET['ronik_debug']) && $_GET['ronik_debug'] == 'auth_mfa' ){
			setcookie("RonikDebug", 'auth_mfa', time()+1500);  /* expire in 25 min */
		}
		// https://together.nbcudev.local/wp-admin/?ronik_debug=auth_2fa
		// AUTH 2FA errors logs all 2FA Processes
		if( isset($_GET['ronik_debug']) && $_GET['ronik_debug'] == 'auth_2fa' ){
			setcookie("RonikDebug", 'auth_2fa', time()+1500);  /* expire in 25 min */
		}
		// https://together.nbcudev.local/wp-admin/?ronik_debug=password
		// AUTH 2FA errors logs all 2FA Processes
		if( isset($_GET['ronik_debug']) && $_GET['ronik_debug'] == 'password' ){
			setcookie("RonikDebug", 'password', time()+1500);  /* expire in 25 min */
		}


		$debugger_error_type = 'general';
		if(isset($_COOKIE['RonikDebug']) && array_key_exists( 'RonikDebug', $_COOKIE) && $_COOKIE['RonikDebug'] == 'valid'){
			error_log(print_r( 'DEBUG ACTIVATED', true));
		} else {
			if(isset($_COOKIE['RonikDebug']) && array_key_exists( 'RonikDebug', $_COOKIE) && $_COOKIE['RonikDebug'] == 'auth' ){
				error_log(print_r( 'DEBUG ACTIVATED AUTH', true));
				$debugger_error_type = 'auth';
			} else if(isset($_COOKIE['RonikDebug']) && array_key_exists( 'RonikDebug', $_COOKIE) && $_COOKIE['RonikDebug'] == 'auth_mfa' ){
				error_log(print_r( 'DEBUG ACTIVATED AUTH MFA', true));
				$debugger_error_type = 'auth_mfa';
			} else if(isset($_COOKIE['RonikDebug']) && array_key_exists( 'RonikDebug', $_COOKIE) && $_COOKIE['RonikDebug'] == 'auth_2fa' ){
				error_log(print_r( 'DEBUG ACTIVATED AUTH 2FA', true));
				$debugger_error_type = 'auth_2fa';
			} else if(isset($_COOKIE['RonikDebug']) && array_key_exists( 'RonikDebug', $_COOKIE) && $_COOKIE['RonikDebug'] == 'password' ){
				error_log(print_r( 'DEBUG ACTIVATED AUTH Password', true));
				$debugger_error_type = 'password';
			} else if($severity_level == 'low') {
				return false;
			}
		}
		if($debugger_error_type !== $error_type){
			return false;
		}

		$f_error_email = get_field('error_email', 'option');
		// Lets run a backtrace to get more useful information.
		$t = debug_backtrace();
		$t_file = 'File Path Location: ' . $t[0]['file'];
		$t_line = 'On Line: ' .  $t[0]['line'];

		//  Low, Medium, High, and Critical
		if( $severity_level == 'critical'){
			if ($f_error_email) {
				// Remove whitespace.
				$f_error_email = str_replace(' ', '', $f_error_email);
				$to = $f_error_email;
				$subject = 'Error Found';
				$headers = array('Content-Type: text/html; charset=UTF-8');
				$body = 'Userid: '. get_current_user_id() .' Website URL: '. $_SERVER['HTTP_HOST'] .'<br><br>Error Message: ' . $log . '<br><br>' . $t_file . '<br><br>' . $t_line;
				wp_mail($to, $subject, $body, $headers);
			}
		}
		if (is_array($log) || is_object($log)) {
			error_log(print_r('<----- ' . $log . ' ----->', true));
			error_log(print_r( 'USER ID:' . get_current_user_id() , true));
			error_log(print_r( $t_file , true));
			error_log(print_r( $t_line , true));
			error_log(print_r('<----- END LOG '.$log.' ----->', true));
			error_log(print_r('   ', true));

		} else {
			error_log(print_r('<----- ' . $log . ' ----->', true));
			error_log(print_r( 'USER ID:' . get_current_user_id() , true));
			error_log(print_r( $t_file , true));
			error_log(print_r( $t_line , true));
			error_log(print_r('<----- END LOG '.$log.' ----->', true));
			error_log(print_r('   ', true));
		}
	}
}

add_action('password_reset', 'ronikdesigns_password_reset_action_store', 10, 2);
function ronikdesigns_password_reset_action_store($user, $new_pass) {
	// Helper Guide
	$helper = new RonikHelper;
    // USER ID
	$f_user_id = $user->data->ID;
    // Target Meta
    $rk_password_history = 'ronik_password_history';
    $rk_password_history_array = get_user_meta( $f_user_id, $rk_password_history, true  );
	$f_hashedPassword = wp_hash_password($new_pass);

        if($rk_password_history_array){
            if( count($rk_password_history_array) == 10 ){
                array_shift($rk_password_history_array);
                // We reindex the password history array
                $rk_password_history_array = array_values($rk_password_history_array);
                array_push($rk_password_history_array, $f_hashedPassword);
            } else {
                array_push($rk_password_history_array, $f_hashedPassword);
            }
        } else {
            $rk_password_history_array  = array($user->data->user_pass, $f_hashedPassword);
        }
    $updated = update_user_meta( $f_user_id, $rk_password_history, $rk_password_history_array );

	$helper->ronikdesigns_write_log_devmode('rk_password_history_array', 'low', 'password');
	$helper->ronikdesigns_write_log_devmode($rk_password_history_array, 'low', 'password');
	$helper->ronikdesigns_write_log_devmode($updated, 'low', 'password');
}


function ronikdesigns_getLineWithString_ronikdesigns($fileName, $id) {
	$f_attached_file = get_attached_file( $id );
	$pieces = explode('/', $f_attached_file ) ;
	$lines = file( urldecode($fileName) );
	foreach ($lines as $lineNumber => $line) {
		if (strpos($line, end($pieces)) !== false) {
			return $id;
		}
	}
}

function ronikdesigns_receiveAllFiles_ronikdesigns($id){
	$f_files = scandir( get_theme_file_path() );
	$array2 = array("functions.php", "package-lock.json", ".", "..", ".DS_Store");
	$results = array_diff($f_files, $array2);

	if($results){
		foreach($results as $file){
			if (is_file(get_theme_file_path().'/'.$file)){
				$f_url = urlencode(get_theme_file_path().'/'.$file);
				$image_ids = ronikdesigns_getLineWithString_ronikdesigns( $f_url , $id);
			}
		}
	}
	return $image_ids;
}

function ronikdesigns_get_page_by_title($title, $post_type = 'page'){
	$query = new WP_Query(
		array(
			'post_type'              => $post_type,
			'title'                  => $title,
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'ignore_sticky_posts'    => true,
			'update_post_term_cache' => false,
			'update_post_meta_cache' => false,
		)
	);
	if( !empty($query->post) ){
		return true;
	} else {
		return false;
	}
}

// POST CLEANING
function cleanInputPOST() {
	function cleanInput($input){
		$search = array(
		  '@<script[^>]*?>.*?</script>@si',
		  '@<[\/\!]*?[^<>]*?>@si',
		  '@<style[^>]*?>.*?</style>@siU',
		  '@<![\s\S]*?--[ \t\n\r]*>@'
		);
		$output = preg_replace($search, '', $input);
		$additional_output = sanitize_text_field( $output );
		return $additional_output;
	}
	// Next lets santize the post data.
	foreach ($_POST as $key => $value) {
		$_POST[$key] = cleanInput($value);
	}
}

// Simple Ajax Secruity
function ronik_ajax_security($nonce_name, $validate_with_nonce ){
	// Helper Guide
	$helper = new RonikHelper;
	$helper->ronikdesigns_write_log_devmode('Security.', 'low');

	// Check if user is logged in. AKA user is authorized.
	if (!is_user_logged_in()) {
		$helper->ronikdesigns_write_log_devmode('Failed user is not logged in', 'low');
		$results['error'] = 'user_logged_is_not_logged_in';
	}
	// If POST is empty we fail it.
	if( empty($_POST) ){
		$helper->ronikdesigns_write_log_devmode('Failed post is empty', 'low');
		$results['error'] = 'Security check failed. Post is empty';
		// wp_send_json_error('Security check failed. Post is empty', '400');
		// wp_die();
	}
	if($validate_with_nonce){
		// Check if the NONCE is correct. Otherwise we kill the application.
		if (!wp_verify_nonce($_POST['nonce'], $nonce_name)) {
			$helper->ronikdesigns_write_log_devmode('Failed wp_verify_nonce', 'low');
			$helper->ronikdesigns_write_log_devmode($nonce_name, 'low');
			$results['error'] = 'Security check failed. wp_verify_nonce';
			// wp_send_json_error('Security check failed. wp_verify_nonce', '400');
			// wp_die();
		}
		// Verifies intent, not authorization AKA protect against clickjacking style attacks
		if ( !check_admin_referer($nonce_name, 'nonce' ) ) {
			$helper->ronikdesigns_write_log_devmode('Failed check_admin_referer', 'low');
			$results['error'] = 'Security check failed. check_admin_referer';
			// wp_send_json_error('Security check failed. check_admin_referer', '400');
			// wp_die();
		}
	}

	if(isset($results['error']) && $results['error'] == 'user_logged_is_not_logged_in'){
		wp_send_json_success('noreload');
		return;
	} else if(isset($results['error']) && $results['error']) {
		wp_send_json_success('security_check_failed');
		return;
	}
}
