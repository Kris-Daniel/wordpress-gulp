<?php

namespace WBCR\Factory_414\Entities;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * @author Webcraftic <wordpress.webraftic@gmail.com>, Alex Kovalev <alex.kovalevv@gmail.com>
 * @link https://webcraftic.com
 * @copyright (c) 2018 Webraftic Ltd
 * @since 4.1.1
 */

class Paths {
	
	public $absolute;
	public $main_file;
	public $relative;
	public $url;
	
	protected $plugin_path;
	
	public function __construct( $plugin_file ) {
		$this->plugin_path = $plugin_file;
		
		$this->main_file  = $plugin_file;
		$this->absolute   = dirname( $plugin_file );
		$this->basename   = plugin_basename( $plugin_file );
		$this->url        = plugins_url( null, $plugin_file );
		$this->migrations = $this->absolute . '/migrations';
	}
}
