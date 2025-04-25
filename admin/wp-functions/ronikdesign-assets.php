<?php

/**
 * Ronikdesign Assets Class
 *
 * @package    Ronikdesign
 * @subpackage Ronikdesign/public
 */
class Ronikdesign_Assets {
	protected $plugin_name;
	protected $version;
	protected $base_dir;
	protected $base_url;

	public function __construct($plugin_name, $version, $base_folder = 'public') {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	
		$plugin_root_dir = plugin_dir_path(dirname(__DIR__));
		$plugin_root_url = plugin_dir_url(dirname(__DIR__));
	
		$this->base_dir = $plugin_root_dir . rtrim($base_folder, '/') . '/';
		$this->base_url = $plugin_root_url . rtrim($base_folder, '/') . '/';
	}
	

	private function get_file_version($path) {
		return file_exists($path) ? filemtime($path) : $this->version;
	}

	public function enqueue_style($handle_suffix, $relative_path, $deps = [], $media = 'all') {
		$full_path = $this->base_dir . ltrim($relative_path, '/');
		$full_url  = $this->base_url . ltrim($relative_path, '/');
		$version   = $this->get_file_version($full_path);

		wp_enqueue_style($this->plugin_name . $handle_suffix, $full_url.'?cb='.CACHE_VERSION_BUMP , $deps, $version, $media);
	}

	public function enqueue_script($handle_suffix, $relative_path, $deps = [], $in_footer = true) {
		$full_path = $this->base_dir . ltrim($relative_path, '/');
		$full_url  = $this->base_url . ltrim($relative_path, '/');
		$version   = $this->get_file_version($full_path);

		wp_enqueue_script($this->plugin_name . $handle_suffix, $full_url.'?cb='.CACHE_VERSION_BUMP , $deps, $version, $in_footer);
	}
}
