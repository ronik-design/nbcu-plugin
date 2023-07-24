<?php


class RonikHelper{
    public function __construct() {
        add_action( 'init', [$this, 'ronikdesigns_svgconverter'] );
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
			$t_line = 'File Path Location: ' .  $t[0]['line'];
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
}




// // Creates an encoded svg for src, lazy loading.
// function ronikdesigns_svgplaceholder($imgacf = null)
// {
// 	$iacf = $imgacf;
// 	if ($iacf) {
// 		if ($iacf['width']) {
// 			$width = $iacf['width'];
// 		}
// 		if ($iacf['height']) {
// 			$height = $iacf['height'];
// 		}
// 		$viewbox = "width='{$width}' height='{$height}' viewBox='0 0 {$width} {$height}'";
// 	} else {
// 		$viewbox = "viewBox='0 0 100 100'";
// 	}
// 	return "data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' {$viewbox}%3E%3C/svg%3E";
// }

// // Creates an inline background image.
// function ronikBgImage($image)
// {
// 	return ' background-image: url(\'' . $image['url'] . '\'); ';
// }

// // Write error logs cleanly.
// function ronikdesigns_write_log($log)
// {
// 	$f_error_email = get_field('error_email', 'option');
// 	if ($f_error_email) {
// 		// Remove whitespace.
// 		$f_error_email = str_replace(' ', '', $f_error_email);
// 		// Lets run a backtrace to get more useful information.
// 		$t = debug_backtrace();
// 		$t_file = 'File Path Location: ' . $t[0]['file'];
// 		$t_line = 'File Path Location: ' .  $t[0]['line'];
// 		$to = $f_error_email;
// 		$subject = 'Error Found';
// 		$body = 'Error Message: ' . $log . '<br><br>' . $t_file . '<br><br>' . $t_line;
// 		$headers = array('Content-Type: text/html; charset=UTF-8');
// 		wp_mail($to, $subject, $body, $headers);
// 	}
// 	if (is_array($log) || is_object($log)) {
// 		error_log(print_r('<----- ' . $log . ' ----->', true));
// 	} else {
// 		error_log(print_r('<----- ' . $log . ' ----->', true));
// 	}
// }


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