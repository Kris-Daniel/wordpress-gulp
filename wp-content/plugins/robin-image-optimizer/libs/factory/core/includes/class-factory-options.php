<?php

namespace WBCR\Factory_414;

// Exit if accessed directly
use Exception;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Работа с опциями плагинов
 *
 * @author Webcraftic <wordpress.webraftic@gmail.com>, Alex Kovalev <alex.kovalevv@gmail.com>
 * @link https://webcraftic.com
 * @copyright (c) 2018 Webraftic Ltd
 * @version 1.0
 */
trait Options {
	
	abstract public function getPrefix();
	
	/**
	 * Получает все опции плагина
	 *
	 * @since 4.0.8
	 * @return array
	 */
	public function loadAllOptions() {
		global $wpdb;
		
		$is_option_loaded = wp_cache_get( $this->getPrefix() . 'all_options_loaded', $this->getPrefix() . 'options' );
		
		if ( false === $is_option_loaded ) {
			$result = $wpdb->get_results( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE '{$this->getPrefix()}%'" );
			
			$options = array();
			
			if ( ! empty( $result ) ) {
				wp_cache_add( $this->getPrefix() . 'all_options_loaded', 1, $this->getPrefix() . 'options' );
				
				foreach ( $result as $option ) {
					$value = maybe_unserialize( $option->option_value );
					$value = $this->normalizeValue( $value );
					
					wp_cache_add( $option->option_name, $value, $this->getPrefix() . 'options' );
					$options[ $option->option_name ] = $value;
				}
				
				/**
				 * @since 4.0.9
				 */
				do_action( 'wbcr/factory/all_options_loaded', $options, $this->plugin_name );
			}
		}
	}
	
	/**
	 * Получает все опции плагина
	 *
	 * @since 4.0.8
	 * @return void
	 */
	public function loadAllNetworkOptions() {
		global $wpdb;
		
		$network_id = (int) get_current_network_id();
		
		$is_option_loaded = wp_cache_get( $network_id . ":" . $this->getPrefix() . 'all_options_loaded', $this->getPrefix() . 'network_options' );
		
		if ( false === $is_option_loaded ) {
			wp_cache_add_global_groups( array( $this->getPrefix() . 'network_options' ) );
			
			$result = $wpdb->get_results( "SELECT meta_key, meta_value FROM {$wpdb->sitemeta} WHERE site_id='{$network_id}' AND meta_key LIKE '{$this->getPrefix()}%'" );
			
			$options = array();
			if ( ! empty( $result ) ) {
				wp_cache_add( $network_id . ":" . $this->getPrefix() . 'all_options_loaded', 1, $this->getPrefix() . 'network_options' );
				
				foreach ( $result as $option ) {
					$value = maybe_unserialize( $option->meta_value );
					$value = $this->normalizeValue( $value );
					
					$cache_key = $network_id . ":" . $option->meta_key;
					wp_cache_add( $cache_key, $value, $this->getPrefix() . 'network_options' );
					$options[ $option->meta_key ] = $value;
				}
				
				/**
				 * @since 4.0.9
				 */
				do_action( 'wbcr/factory/all_network_options_loaded', $options, $this->plugin_name );
			}
		}
	}
	
	/**
	 * Если плагин установлен для сети, то метод возвращает опции только для сети,
	 * иначе метод возвращает опцию для текущего сайта.
	 *
	 * @since 4.0.8
	 *
	 * @param string $option_name
	 * @param string $default
	 *
	 * @return bool|mixed
	 */
	public function getPopulateOption( $option_name, $default = false ) {
		if ( $this->isNetworkActive() ) {
			$option_value = $this->getNetworkOption( $option_name, $default );
		} else {
			$option_value = $this->getOption( $option_name, $default );
		}
		
		return apply_filters( "wbcr/factory/populate_option_{$option_name}", $option_value, $option_name, $default );
	}
	
	/**
	 * Получает опцию для сети, используется в режиме мультисайтов
	 *
	 * @param $option_name
	 * @param bool $default
	 *
	 * @return bool|mixed
	 */
	public function getNetworkOption( $option_name, $default = false ) {
		if ( empty( $option_name ) || ! is_string( $option_name ) ) {
			throw new Exception( 'Option name must be a string and must not be empty.' );
		}
		
		if ( ! is_multisite() ) {
			return $this->getOption( $option_name, $default );
		}
		
		$this->loadAllNetworkOptions();
		
		$network_id   = (int) get_current_network_id();
		$cache_key    = $network_id . ':' . $this->getPrefix() . $option_name;
		$option_value = wp_cache_get( $cache_key, $this->getPrefix() . 'network_options' );
		
		if ( false === $option_value ) {
			$option_value = $default;
		}
		
		/**
		 * @param mixed $option_value
		 * @param string $option_name
		 * @param mixed $default
		 * @param int $network_id
		 *
		 * @since 4.0.8
		 */
		
		return apply_filters( "wbcr/factory/network_option_{$option_name}", $option_value, $option_name, $default, $network_id );
	}
	
	/**
	 * Получает опцию из кеша или из базы данныеs
	 *
	 * @since 4.0.0
	 * @since 4.0.8 - полностью переделан
	 *
	 * @param string $option_name
	 * @param bool $default
	 *
	 * @return mixed
	 */
	public function getOption( $option_name, $default = false ) {
		if ( empty( $option_name ) || ! is_string( $option_name ) ) {
			throw new Exception( 'Option name must be a string and must not be empty.' );
		}
		
		$this->loadAllOptions();
		
		$option_value = wp_cache_get( $this->getPrefix() . $option_name, $this->getPrefix() . 'options' );
		
		if ( false === $option_value ) {
			$option_value = $default;
		}
		
		/**
		 * @param mixed $option_value
		 * @param string $option_name
		 * @param mixed $default
		 *
		 * @since 4.0.8
		 */
		
		return apply_filters( "wbcr/factory/option_{$option_name}", $option_value, $option_name, $default );
	}
	
	/**
	 * @param $option_name
	 * @param $option_value
	 *
	 * @return bool
	 */
	public function updatePopulateOption( $option_name, $option_value ) {
		if ( $this->isNetworkActive() ) {
			$this->updateNetworkOption( $option_name, $option_value );
		} else {
			$this->updateOption( $option_name, $option_value );
		}
	}
	
	/**
	 * Обновляет опцию для сети в базе данных и в кеше
	 *
	 * @since 4.0.8
	 *
	 * @param string $option_name
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function updateNetworkOption( $option_name, $option_value ) {
		$network_id = (int) get_current_network_id();
		$cache_key  = $network_id . ':' . $this->getPrefix() . $option_name;
		wp_cache_set( $cache_key, $option_value, $this->getPrefix() . 'network_options' );
		
		$result = update_site_option( $this->getPrefix() . $option_name, $option_value );
		
		/**
		 * @param mixed $option_value
		 * @param string $option_name
		 *
		 * @since 4.0.8
		 */
		do_action( "wbcr/factory/update_network_option", $option_name, $option_value );
		
		return $result;
	}
	
	/**
	 * Обновляет опцию в базе данных и в кеше
	 *
	 * @since 4.0.0
	 * @since 4.0.8 - полностью переделан
	 *
	 * @param string $option_name
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function updateOption( $option_name, $option_value ) {
		wp_cache_set( $this->getPrefix() . $option_name, $option_value, $this->getPrefix() . 'options' );
		$result = update_option( $this->getPrefix() . $option_name, $option_value );
		
		/**
		 * @param mixed $option_value
		 * @param string $option_name
		 *
		 * @since 4.0.8
		 */
		do_action( "wbcr/factory/update_option", $option_name, $option_value );
		
		return $result;
	}
	
	/**
	 * Удаляет опцию из базы данных, если опция есть в кеше,
	 * индивидуально удаляет опцию из кеша.
	 *
	 * @param string $option_name
	 *
	 * @return void
	 */
	public function deletePopulateOption( $option_name ) {
		if ( $this->isNetworkActive() ) {
			$this->deleteNetworkOption( $option_name );
		} else {
			$this->deleteOption( $option_name );
		}
	}
	
	/**
	 * Удаляет опцию из базы данных, если опция есть в кеше,
	 * индивидуально удаляет опцию из кеша.
	 *
	 * @param string $option_name
	 *
	 * @return bool
	 */
	public function deleteNetworkOption( $option_name ) {
		$network_id   = (int) get_current_network_id();
		$cache_key    = $network_id . ':' . $this->getPrefix() . $option_name;
		$delete_cache = wp_cache_delete( $cache_key, $this->getPrefix() . 'network_options' );
		
		$delete_opt1 = delete_site_option( $this->getPrefix() . $option_name );
		
		return $delete_cache && $delete_opt1;
	}
	
	/**
	 * Удаляет опцию из базы данных, если опция есть в кеше,
	 * индивидуально удаляет опцию из кеша.
	 *
	 * @param string $option_name
	 *
	 * @return bool
	 */
	public function deleteOption( $option_name ) {
		$delete_cache = wp_cache_delete( $this->getPrefix() . $option_name, $this->getPrefix() . 'options' );
		
		// todo: удалить, когда большая часть пользователей обновятся до современных релизов
		$delete_opt1 = delete_option( $this->getPrefix() . $option_name . '_is_active' );
		$delete_opt2 = delete_option( $this->getPrefix() . $option_name );
		
		return $delete_cache && $delete_opt1 && $delete_opt2;
	}
	
	/**
	 * Сбрасывает объектный кеш опций
	 *
	 * @return bool
	 */
	public function flushOptionsCache() {
		return wp_cache_flush();
	}
	
	/**
	 * Возвращает название опции в пространстве имен плагина
	 *
	 * @param string $option_name
	 *
	 * @return null|string
	 */
	public function getOptionName( $option_name ) {
		$option_name = trim( rtrim( $option_name ) );
		if ( empty( $option_name ) || ! is_string( $option_name ) ) {
			return null;
		}
		
		return $this->getPrefix() . $option_name;
	}
	
	/**
	 * Приведение значений опций к строгому типу данных
	 *
	 * @param mixed $string
	 *
	 * @return bool|int
	 */
	public function normalizeValue( $data ) {
		if ( is_string( $data ) ) {
			$check_string = rtrim( trim( $data ) );
			
			if ( $check_string == "1" || $check_string == "0" ) {
				return intval( $data );
			} else if ( $check_string === 'false' ) {
				return false;
			} else if ( $check_string === 'true' ) {
				return true;
			}
		}
		
		return $data;
	}
}
