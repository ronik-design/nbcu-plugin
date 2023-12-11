<?php


class RonikHelper{
    // public function __construct() {
    //     add_action( 'init', [$this, 'ronikdesigns_svgconverter'] );
    // }

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
	public function ronikdesigns_write_log_devmode($log, $severity_level='low') {
		if($severity_level == 'low'){
			return false;
		}
		$f_error_email = get_field('error_email', 'option');
		// Lets run a backtrace to get more useful information.
		$t = debug_backtrace();
		$t_file = 'File Path Location: ' . $t[0]['file'];
		$t_line = 'On Line: ' .  $t[0]['line'];

		//  Low, Medium, High, and Critical
		if( $severity_level == 'critical' ){
			if ($f_error_email) {
				// Remove whitespace.
				$f_error_email = str_replace(' ', '', $f_error_email);
				$to = $f_error_email;
				$subject = 'Error Found';
				$headers = array('Content-Type: text/html; charset=UTF-8');
				$body = 'Website URL: '. $_SERVER['HTTP_HOST'] .'<br><br>Error Message: ' . $log . '<br><br>' . $t_file . '<br><br>' . $t_line;
				wp_mail($to, $subject, $body, $headers);
			}
		}
		if (is_array($log) || is_object($log)) {
			error_log(print_r('<----- ' . $log . ' ----->', true));
			error_log(print_r( $t_file , true));
			error_log(print_r( $t_line , true));
			error_log(print_r('<----- END LOG '.$log.' ----->', true));
			error_log(print_r('   ', true));

		} else {
			error_log(print_r('<----- ' . $log . ' ----->', true));
			error_log(print_r( $t_file , true));
			error_log(print_r( $t_line , true));
			error_log(print_r('<----- END LOG '.$log.' ----->', true));
			error_log(print_r('   ', true));
		}
	}
}

add_action('password_reset', 'ronikdesigns_password_reset_action_store', 10, 2);
function ronikdesigns_password_reset_action_store($user, $new_pass) {
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

    error_log(print_r('$rk_password_history_array' , true));
    error_log(print_r($rk_password_history_array , true));
    error_log(print_r('$updated' , true));
    error_log(print_r($updated , true));
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
function ronik_ajax_security($nonce_name ,$skip_nonce ){
	// Check if user is logged in. AKA user is authorized.
	if (!is_user_logged_in()) {
		error_log(print_r( 'Failed user is not logged in', true));
		wp_send_json_success('noreload');
		return;
	}
	// If POST is empty we fail it.
	if( empty($_POST) ){
		error_log(print_r( 'Failed post is empty', true));
		wp_send_json_error('Security check failed', '400');
		wp_die();
	}
	if($skip_nonce){
		// Check if the NONCE is correct. Otherwise we kill the application.
		if (!wp_verify_nonce($_POST['nonce'], $nonce_name)) {
			error_log(print_r( 'Failed wp_verify_nonce', true));
			wp_send_json_error('Security check failed', '400');
			wp_die();
		}
		// Verifies intent, not authorization AKA protect against clickjacking style attacks
		if ( !check_admin_referer($nonce_name, 'nonce' ) ) {
			error_log(print_r( 'Failed check_admin_referer', true));
			wp_send_json_error('Security check failed', '400');
			wp_die();
		}
	}
}
