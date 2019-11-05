<?php

namespace WBCR\Factory_414\Updates;

use Exception;
use stdClass;
use Wbcr_Factory414_Plugin;
use WBCR\Factory_Freemius_103\Updates\Freemius_Repository;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @author        Webcraftic <wordpress.webraftic@gmail.com>, Alex Kovalev <alex.kovalevv@gmail.com>
 * @link          https://webcraftic.com
 * @copyright (c) 2018 Webraftic Ltd
 * @version       1.0
 */
class Upgrader {

	const CHECK_UPDATES_INTERVAL = "43200";

	/**
	 * Тип апгрейдера, может быть default, premium
	 *
	 * @var string
	 */
	protected $type = 'default';

	/**
	 * @var Wbcr_Factory414_Plugin
	 */
	protected $plugin;

	/**
	 * @var string
	 */
	protected $plugin_basename;

	/**
	 * @var string
	 */
	protected $plugin_main_file;

	/**
	 * @var string
	 */
	protected $plugin_absolute_path;

	/**
	 * Имя плагина, для которого нужно проверять обновления
	 *
	 * @var string
	 */
	protected $plugin_slug;

	/**
	 * @var Repository
	 */
	protected $repository;

	/**
	 * @var array
	 */
	protected $rollback = [
		'prev_stable_version' => null
	];

	/**
	 * @var bool
	 */
	protected $is_debug = false;

	/**
	 * Manager constructor.
	 *
	 * @since 4.1.1
	 *
	 * @param Wbcr_Factory414_Plugin $plugin
	 * @param                        $args
	 * @param bool                   $is_premium
	 *
	 * @throws Exception
	 */
	public function __construct( Wbcr_Factory414_Plugin $plugin ) {

		$this->plugin = $plugin;

		$this->plugin_basename      = $plugin->get_paths()->basename;
		$this->plugin_main_file     = $plugin->get_paths()->main_file;
		$this->plugin_absolute_path = $plugin->get_paths()->absolute;
		$this->is_debug             = defined( 'FACTORY_UPDATES_DEBUG' ) && FACTORY_UPDATES_DEBUG;

		$settings = $this->get_settings();

		$this->plugin_slug = $settings['slug'];
		$this->rollback    = $settings['rollback_settings'];

		if ( empty( $this->plugin_slug ) || ! is_string( $this->plugin_slug ) ) {
			throw new Exception( 'Argument {slug} can not be empty and must be of type string.' );
		}

		$this->set_repository();

		if ( $this->repository->need_check_updates() ) {
			$this->init_hooks();
		}
	}

	/**
	 * @throws Exception
	 */
	protected function set_repository() {
		$settings         = $this->get_settings();
		$this->repository = $this->get_repository( $settings['repository'] );
		$this->repository->init();
	}

	/**
	 * @return array
	 */
	protected function get_settings() {
		$settings = $this->plugin->getPluginInfoAttr( 'updates_settings' );

		return wp_parse_args( $settings, [
			'repository'        => 'wordpress',
			'slug'              => '',
			'maybe_rollback'    => false,
			'rollback_settings' => [
				'prev_stable_version' => '0.0.0'
			]
		] );
	}

	/**
	 * @since 4.1.1
	 * @throws Exception
	 */
	protected function init_hooks() {
		add_filter( 'site_transient_update_plugins', [
			$this,
			'site_transient_update_plugins_hook'
		] );

		add_action( 'wp_update_plugins', [ $this, 'reset_check_update_timer' ], 9 ); // WP Cron.
		add_action( 'deleted_site_transient', [ $this, 'reset_check_update_timer' ] );
		add_action( 'setted_site_transient', [ $this, 'reset_check_update_timer' ] );
	}


	/**
	 * When WP sets the update_plugins site transient, we set our own transient
	 *
	 * @since 4.1.1
	 *
	 * @param Object $transient   Site transient object.
	 *
	 * @throws Exception
	 */
	public function site_transient_update_plugins_hook( $transient ) {

		if ( ! $transient || ! is_object( $transient ) ) {
			return $transient;
		}

		$temp_object = $this->check_updates();

		if ( ! empty( $temp_object ) && is_object( $temp_object ) && version_compare( $this->get_plugin_version(), $temp_object->new_version, '<' ) ) {
			$transient->response[ $temp_object->plugin ] = $temp_object;

			return $transient;
		}

		return $transient;
	}

	/**
	 * When WP deletes the update_plugins site transient or updates the plugins, we delete our own transients to avoid another 12 hours waiting
	 *
	 * @since 4.1.1
	 *
	 * @param string $transient   Transient name.
	 * @param object $value       Transient object.
	 */
	public function reset_check_update_timer( $transient = 'update_plugins', $value = null ) {
		$options_prefix = $this->type == "default" ? "" : "_" . $this->type;

		// $value used by setted.
		if ( 'update_plugins' === $transient ) {
			if ( is_null( $value ) || is_object( $value ) && ! isset( $value->response ) ) {

				$last_check_time = (int) $this->plugin->getPopulateOption( "last_check{$options_prefix}_update_time", 0 );

				if ( 0 !== $last_check_time && time() > ( $last_check_time + MINUTE_IN_SECONDS ) ) {
					$this->plugin->deletePopulateOption( "last_check{$options_prefix}_update_time" );
					$this->plugin->deletePopulateOption( "last_check{$options_prefix}_update" );
				}
			}
		}
	}

	/**
	 * Проверяет последние обновления для текущего или премиум плагина.
	 *
	 * @since 4.1.1
	 * @return object|null
	 * @throws Exception
	 */
	protected function check_updates( $force = false ) {

		$options_prefix         = $this->type == "default" ? "" : "_" . $this->type;
		$check_updates_interval = self::CHECK_UPDATES_INTERVAL;
		$last_check_time        = (int) $this->plugin->getPopulateOption( "last_check{$options_prefix}_update_time", 0 );

		if ( $this->is_debug && defined( 'FACTORY_CHECK_UPDATES_INTERVAL' ) ) {
			$check_updates_interval = FACTORY_CHECK_UPDATES_INTERVAL;
			if ( empty( $check_updates_interval ) || ! is_numeric( $check_updates_interval ) ) {
				$check_updates_interval = MINUTE_IN_SECONDS;
			}
		}

		if ( $force || ( time() > ( $last_check_time + $check_updates_interval ) ) ) {

			$this->plugin->updatePopulateOption( "last_check{$options_prefix}_update_time", time() );

			$last_version = $this->repository->get_last_version();

			if ( ! empty( $last_version ) ) {
				$temp_object              = new stdClass();
				$temp_object->slug        = $this->plugin_slug;
				$temp_object->plugin      = $this->plugin_basename;
				$temp_object->new_version = $last_version;
				$temp_object->package     = $this->repository->get_download_url();

				$this->plugin->updatePopulateOption( "last_check{$options_prefix}_update", $temp_object );

				return $temp_object;
			}
		}

		return $this->plugin->getPopulateOption( "last_check{$options_prefix}_update" );
	}

	/**
	 * @since 4.1.1
	 *
	 * @param $args
	 *
	 * @return string
	 */
	protected function get_admin_url( $args ) {
		$url = admin_url( 'plugins.php', $args );

		if ( $this->plugin->isNetworkActive() ) {
			$url = network_admin_url( 'plugins.php', $args );
		}

		return add_query_arg( $args, $url );
	}

	/**
	 * @since 4.1.1
	 *
	 * @param $repository_name
	 *
	 * @return Repository
	 * @throws Exception
	 */
	protected function get_repository( $repository_name ) {
		switch ( $repository_name ) {
			case 'wordpress':
				return new Wordpress_Repository( $this->plugin );
				break;
			case 'freemius':
				if ( ! defined( 'FACTORY_FREEMIUS_103_LOADED' ) ) {
					throw new Exception( 'If you have to get updates from the Freemius repository, you need to install the freemius module.' );
				}

				return new Freemius_Repository( $this->plugin );
				break;
			default:
				return $this->instance_other_repository( $repository_name );
				break;
		}
	}

	/**
	 * @since 4.1.1
	 *
	 * @param string $name
	 * @param bool   $is_premium
	 *
	 * @return Repository
	 * @throws Exception
	 */
	protected function instance_other_repository( $name ) {
		$other_repositories = [];

		/**
		 * @since 4.1.1
		 * @type array $other_repositories
		 */
		$other_repositories = apply_filters( 'wbcr/factory/updates/repositories', $other_repositories );

		if ( ! isset( $other_repositories[ $name ] ) ) {
			return null;
		}

		$repository_data = $other_repositories[ $name ];

		if ( ! isset( $repository_data['name'] ) || ! isset( $repository_data['class_path'] ) || ! isset( $repository_data['class_name'] ) ) {
			throw new Exception( 'Repository data must contain the required attributes name, class_path, class_name!' );
		}

		if ( ! file_exists( $repository_data['class_path'] ) ) {
			throw new Exception( 'File with new repository class not found. Please check the correctness of used path: ' . $repository_data['class_path'] );
		}

		if ( ! class_exists( $repository_data['class_name'] ) ) {
			throw new Exception( 'Class ' . $repository_data['class_name'] . ' is not found. Please check if class name is filled out correctly.' );
		}

		require_once $repository_data['class_path'];

		return new $repository_data['class_name']( $this->plugin );
	}

	/**
	 * @since 4.1.1
	 * @return string
	 */
	protected function get_plugin_version() {
		return $this->plugin->getPluginVersion();
	}

	/**
	 * @since 4.1.1
	 */
	protected function rollback() {

	}
}