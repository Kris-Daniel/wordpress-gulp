<?php

namespace WBCR\Factory_414\Premium;

use Exception;
use Wbcr_Factory414_Plugin;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @author Webcraftic <wordpress.webraftic@gmail.com>, Alex Kovalev <alex.kovalevv@gmail.com>
 * @link https://webcraftic.com
 * @copyright (c) 2018 Webraftic Ltd
 * @version 1.0
 */
class Manager {
	
	/**
	 * @var Wbcr_Factory414_Plugin
	 */
	protected $plugin;
	
	/**
	 * @var \WBCR\Factory_414\Premium\Provider
	 */
	protected $provider;
	
	/**
	 * @var array
	 */
	protected $settings;
	
	/**
	 * Manager constructor.
	 *
	 * @param Wbcr_Factory414_Plugin $plugin
	 * @param array $settings
	 *
	 * @throws Exception
	 */
	public function __construct( Wbcr_Factory414_Plugin $plugin, array $settings ) {
		$this->plugin   = $plugin;
		$this->settings = $settings;
		//Plugin_Updates_Manager( $this->plugin, $this->updates['premium'], true );
	}
	
	/**
	 * @param Wbcr_Factory414_Plugin $plugin
	 * @param array $settings
	 *
	 * @return \WBCR\Factory_Freemius_103\Premium\Provider
	 * @throws Exception
	 */
	public static function instance( Wbcr_Factory414_Plugin $plugin, array $settings ) {
		$premium_manager = new Manager( $plugin, $settings );
		
		return $premium_manager->instance_provider();
	}
	
	/**
	 * @param $provider_name
	 *
	 * @return \WBCR\Factory_Freemius_103\Premium\Provider
	 * @throws Exception
	 */
	public function instance_provider() {
		$provider_name = $this->get_setting( 'provider' );
		
		if ( 'freemius' == $provider_name ) {
			return new \WBCR\Factory_Freemius_103\Premium\Provider( $this->plugin, $this->settings );
		} else if ( 'codecanyon' == $provider_name ) {
			//return new \WBCR\Factory_Codecanyon_000\Licensing\Provider( $this->plugin, $this->settings );
			throw new Exception( 'Codecanyon provider is not supported!' );
		} else if ( 'templatemonster' == $provider_name ) {
			//return new \WBCR\Factory_Themplatemonster_000\Licensing\Provider( $this->plugin, $this->settings );
			throw new Exception( 'Templatemonster provider is not supported!' );
		}
		
		throw new Exception( "Provider {$provider_name} is not supported!" );
	}
	
	protected function get_setting( $name ) {
		return isset( $this->settings[ $name ] ) ? $this->settings[ $name ] : null;
	}
}