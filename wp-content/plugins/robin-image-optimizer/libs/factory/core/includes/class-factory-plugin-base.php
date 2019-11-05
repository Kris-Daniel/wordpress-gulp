<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Базовый класс
 *
 * @author Webcraftic <wordpress.webraftic@gmail.com>, Alex Kovalev <alex.kovalevv@gmail.com>
 * @link https://webcraftic.com
 * @copyright (c) 2018 Webraftic Ltd
 * @since 4.0.8
 */
class  Wbcr_Factory414_Base {
	
	use WBCR\Factory_414\Options;
	
	/**
	 * Namespace Prefix among Wordpress Options
	 *
	 * @var string
	 */
	protected $prefix;
	
	/**
	 * Plugin title
	 *
	 * @var string
	 */
	protected $plugin_title;
	
	/**
	 * Plugin name. Valid characters [A-z0-9_-]
	 *
	 * @var string
	 */
	protected $plugin_name;
	
	/**
	 * Plugin version. Valid characters [0-9.]
	 * Example: 1.4.5
	 *
	 * @var string
	 */
	protected $plugin_version;
	
	/**
	 * @since 4.1.1
	 * @var string
	 */
	protected $plugin_text_domain;
	
	/**
	 * @var array
	 */
	protected $support_details;
	
	/**
	 * @var bool
	 */
	protected $has_updates = false;
	
	/**
	 * Optional. Settings for plugin updates from a remote repository.
	 *
	 * @var array {
	 *
	 *    Update settings for free plugin.
	 *
	 *    {type} string repository          Type where we download plugin updates
	 *                                      (wordpress | freemius | other)
	 *
	 *    {type} string slug                Plugin slug
	 *
	 *    {type} array rollback             Settings for rollback to the previous version of
	 *                                      the plugin, will gain only one option prev_stable_version,
	 *                                      you must specify previous version of the plugin         *
	 * }
	 */
	protected $updates_settings = array();
	
	/**
	 * Does plugin have a premium version?
	 *
	 * @var bool
	 */
	protected $has_premium = false;
	
	/**
	 * Optional. Settings for download, update and upgrage to premium of the plugin.
	 *
	 * @var array {
	 *      {type} string license_provider            Store where premium plugin was sold (freemius | codecanyon | template_monster)
	 *      {type} string plugin_id                   Plugin id, only for freemius
	 *      {type} string public_key                  Plugin public key, only for freemius
	 *      {type} string slug                        Plugin name, only for freemius
	 *
	 *      {type} array  premium_plugin_updates {
	 *              Update settings for free plugin.
	 *
	 *              {type} array rollback             Settings for rollback to the previous version of
	 *                                                the plugin, will gain only one option prev_stable_version,
	 *                                                you must specify previous version of the plugin         *
	 *      }
	 * }
	 */
	protected $license_settings = array();
	
	/**
	 * Required. Framework modules needed to develop a plugin.
	 *
	 * @var array {
	 * Array with information about the loadable module
	 *      {type} string $module [0]   Relative path to the module directory
	 *      {type} string $module [1]   Module name with prefix 000
	 *      {type} string $module [2]   Scope:
	 *                                  admin  - Module will be loaded only in the admin panel,
	 *                                  public - Module will be loaded only on the frontend
	 *                                  all    - Module will be loaded everywhere
	 * }
	 */
	protected $load_factory_modules = array(
		array( 'libs/factory/bootstrap', 'factory_bootstrap_414', 'admin' ),
		array( 'libs/factory/forms', 'factory_forms_414', 'admin' ),
		array( 'libs/factory/pages', 'factory_pages_414', 'admin' ),
	);
	
	
	/**
	 * @var \WBCR\Factory_414\Entities\Support
	 */
	protected $support;
	
	/**
	 * @var \WBCR\Factory_414\Entities\Paths
	 */
	protected $paths;
	
	/**
	 * @var string
	 */
	private $plugin_file;
	
	/**
	 * @var array
	 */
	private $plugin_data;
	
	/**
	 * @since 4.1.1 - добавил две сущности support, paths. Удалил свойства, plugin_build
	 *                plugin_assembly, main_file, plugin_root, relative_path, plugin_url
	 * @since 4.0.8 - добавлена дополнительная логика
	 *
	 * @param string $plugin_file
	 * @param array $data
	 *
	 * @throws Exception
	 */
	public function __construct( $plugin_file, $data ) {
		$this->plugin_file = $plugin_file;
		$this->plugin_data = $data;
		
		foreach ( (array) $data as $option_name => $option_value ) {
			if ( property_exists( $this, $option_name ) ) {
				$this->$option_name = $option_value;
			}
		}
		
		if ( empty( $this->prefix ) || empty( $this->plugin_name ) || empty( $this->plugin_title ) || empty( $this->plugin_version ) || empty( $this->plugin_text_domain ) ) {
			throw new Exception( 'One of the required attributes has not been passed (prefix, plugin_title, plugin_name, plugin_version, plugin_text_domain).' );
		}
		
		$this->support = new \WBCR\Factory_414\Entities\Support( $this->support_details );
		$this->paths   = new \WBCR\Factory_414\Entities\Paths( $plugin_file );
		
		// used only in the module 'updates'
		$this->plugin_slug = ! empty( $this->plugin_name ) ? $this->plugin_name : basename( $plugin_file );
	}
	
	/**
	 * @param $name
	 *
	 * @return string|null
	 */
	public function __get( $name ) {
		
		$deprecated_props = array(
			'plugin_build',
			'plugin_assembly',
			'main_file',
			'plugin_root',
			'relative_path',
			'plugin_url'
		);
		
		if ( in_array( $name, $deprecated_props ) ) {
			$deprecated_message = 'In version 4.1.1 of the Factory framework, the class properties ';
			$deprecated_message .= '(' . implode( ',', $deprecated_props ) . ')';
			$deprecated_message .= 'have been removed. To get plugin paths, use the new paths property.' . PHP_EOL;
			
			$backtrace = debug_backtrace();
			if ( ! empty( $backtrace ) && isset( $backtrace[1] ) ) {
				$deprecated_message .= 'BACKTRACE:(';
				$deprecated_message .= 'File: ' . $backtrace[1]['file'];
				$deprecated_message .= 'Function: ' . $backtrace[1]['function'];
				$deprecated_message .= 'Line: ' . $backtrace[1]['line'];
				$deprecated_message .= ')';
			}
			
			_deprecated_argument( __METHOD__, '4.1.1', $deprecated_message );
			
			switch ( $name ) {
				case 'plugin_build':
					return null;
					break;
				case 'plugin_assembly':
					return null;
					break;
				case 'main_file':
					return $this->get_paths()->main_file;
					break;
				case 'plugin_root':
					return $this->get_paths()->absolute;
					break;
				case 'relative_path':
					return $this->get_paths()->basename;
					break;
				case 'plugin_url':
					return $this->get_paths()->url;
					break;
			}
		}
		
		return null;
	}
	
	/**
	 * @param $name
	 * @param $arguments
	 *
	 * @return stdClass|null
	 * @throws Exception
	 */
	public function __call( $name, $arguments ) {
		
		$deprecated_methods = array(
			'getPluginBuild',
			'getPluginAssembly',
			'getPluginPathInfo'
		);
		
		if ( in_array( $name, $deprecated_methods ) ) {
			$deprecated_message = 'In version 4.1.1 of the Factory framework, methods (' . implode( ',', $deprecated_methods ) . ') have been removed.';
			
			$backtrace = debug_backtrace();
			if ( ! empty( $backtrace ) && isset( $backtrace[1] ) ) {
				$deprecated_message .= 'BACKTRACE:(';
				$deprecated_message .= 'File: ' . $backtrace[1]['file'];
				$deprecated_message .= 'Function: ' . $backtrace[1]['function'];
				$deprecated_message .= 'Line: ' . $backtrace[1]['line'];
				$deprecated_message .= ')';
			}
			
			_deprecated_argument( __METHOD__, '4.1.1', $deprecated_message );
			
			if ( 'getPluginPathInfo' == $name ) {
				$object = new stdClass;
				
				$object->main_file     = $this->get_paths()->main_file;
				$object->plugin_root   = $this->get_paths()->absolute;
				$object->relative_path = $this->get_paths()->basename;
				$object->plugin_url    = $this->get_paths()->url;
				
				return $object;
			}
		}
		
		throw new Exception( "Method {$name} does not exist" );
	}
	
	/**
	 * @return bool
	 */
	public function has_premium() {
		return $this->has_premium;
	}
	
	/**
	 * @return string
	 */
	public function getPluginTitle() {
		return $this->plugin_title;
	}
	
	/**
	 * @return string
	 */
	public function getPrefix() {
		return $this->prefix;
	}
	
	/**
	 * @return string
	 */
	public function getPluginName() {
		return $this->plugin_name;
	}
	
	/**
	 * @return string
	 */
	public function getPluginVersion() {
		return $this->plugin_version;
	}
	
	/**
	 * @param $attr_name
	 *
	 * @return null
	 */
	public function getPluginInfoAttr( $attr_name ) {
		if ( isset( $this->plugin_data[ $attr_name ] ) ) {
			return $this->plugin_data[ $attr_name ];
		}
		
		return null;
	}
	
	/**
	 * @return \WBCR\Factory_414\Entities\Support
	 */
	public function get_support() {
		return $this->support;
	}
	
	/**
	 * @return \WBCR\Factory_414\Entities\Paths
	 */
	public function get_paths() {
		return $this->paths;
	}
	
	/**
	 * @return object
	 */
	public function getPluginInfo() {
		return (object) $this->plugin_data;
	}
	
	/**
	 * Активирован ли сайт в режиме мультисайтов и мы находимся в области суперадминистратора
	 * TODO: Вынести метод в функции
	 * @return bool
	 */
	public function isNetworkAdmin() {
		return is_multisite() && is_network_admin();
	}
	
	/**
	 * Активирован ли плагин для сети
	 * TODO: Вынести метод в функции
	 * @since 4.0.8
	 * @return bool
	 */
	public function isNetworkActive() {
		// Makes sure the plugin is defined before trying to use it
		if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}

		$activate = is_plugin_active_for_network( $this->get_paths()->basename );

		if ( ! $activate && $this->isNetworkAdmin() && isset( $_GET['action'] ) && $_GET['action'] == 'activate' ) {
			return isset( $_GET['networkwide'] ) && 1 == (int)$_GET['networkwide'];

		}

		return $activate;
	}
	
	/**
	 * Получает список активных сайтов сети
	 * TODO: Вынести метод в функции
	 * @since 4.0.8
	 * @return array|int
	 */
	public function getActiveSites( $args = array( 'archived' => 0, 'mature' => 0, 'spam' => 0, 'deleted' => 0 ) ) {
		global $wp_version;
		
		if ( version_compare( $wp_version, '4.6', '>=' ) ) {
			return get_sites( $args );
		} else {
			$converted_array = array();
			
			$sites = wp_get_sites( $args );
			
			if ( empty( $sites ) ) {
				return $converted_array;
			}
			
			foreach ( (array) $sites as $key => $site ) {
				$obj = new stdClass();
				foreach ( $site as $attr => $value ) {
					$obj->$attr = $value;
				}
				$converted_array[ $key ] = $obj;
			}
			
			return $converted_array;
		}
	}
}
