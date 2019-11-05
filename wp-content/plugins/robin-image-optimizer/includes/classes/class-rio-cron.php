<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс для работы оптимизации по расписанию
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_Cron {

	/**
	 * Инициализация оптимизации по расписанию
	 */
	public function __construct() {
		$this->initHooks();
	}

	/**
	 * Подключение хуков
	 */
	public function initHooks() {
		add_filter( 'wbcr/rio/settings_page/options', [ $this, 'settings_form' ], 10, 1 );
		add_action( 'wrio/cron/optimization_process', [ $this, 'process' ] );
		add_filter( 'cron_schedules', [ $this, 'intervals' ], 100, 1 );
	}

	/**
	 * Хук на фильтр wbcr/rio/settings_page/options
	 *
	 * Добавляет в настройки плагина элементы управления cron
	 *
	 * @param array $options   настройки
	 *
	 * @return array $options настройки
	 */
	public function settings_form( $options ) {
		// cron
		$options[]     = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __( 'Scheduled optimization', 'robin-image-optimizer' ) . '</strong><p>' . __( 'Schedule your images optimization.', 'robin-image-optimizer' ) . '</p></div>'
		];
		$group_items[] = [
			'type'    => 'dropdown',
			'way'     => 'buttons',
			'name'    => 'image_autooptimize_shedule_time',
			'data'    => [
				[ 'wio_1_min', __( '1 min', 'robin-image-optimizer' ) ],
				[ 'wio_2_min', __( '2 min', 'robin-image-optimizer' ) ],
				[ 'wio_5_min', __( '5 min', 'robin-image-optimizer' ) ],
				[ 'wio_10_min', __( '10 min', 'robin-image-optimizer' ) ],
				[ 'wio_30_min', __( '30 min', 'robin-image-optimizer' ) ],
				[ 'wio_hourly', __( 'Hour', 'robin-image-optimizer' ) ],
				[ 'wio_daily', __( 'Day', 'robin-image-optimizer' ) ],
			],
			'default' => 'wio_5_min',
			'title'   => __( 'Run every', 'robin-image-optimizer' ),
			'hint'    => __( 'Select time at which the task will be repeated.', 'robin-image-optimizer' )
		];

		$group_items[] = [
			'type'    => 'textbox',
			'name'    => 'image_autooptimize_items_number_per_interation',
			'title'   => __( 'Images per iteration', 'robin-image-optimizer' ),
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'Specify the number of images that will be optimized during the job. For example, if you enter 5 and select 5 min, the plugin will optimize 5 images every 5 minutes.', 'robin-image-optimizer' ),
			'default' => '3'
		];

		$options[] = [
			'type'  => 'div',
			'id'    => 'wbcr-io-shedule-options',
			'items' => $group_items
		];

		return $options;
	}

	/**
	 * Кастомные интервалы выполнения cron задачи
	 *
	 * @param array $intervals   Зарегистророванные интервалы
	 *
	 * @return array $intervals Новые интервалы
	 */
	public function intervals( $intervals ) {
		$intervals['wio_1_min']  = [
			'interval' => 60,
			'display'  => __( '1 min', 'robin-image-optimizer' ),
		];
		$intervals['wio_2_min']  = [
			'interval' => 60 * 2,
			'display'  => __( '2 min', 'robin-image-optimizer' ),
		];
		$intervals['wio_5_min']  = [
			'interval' => 60 * 5,
			'display'  => __( '5 min', 'robin-image-optimizer' ),
		];
		$intervals['wio_10_min'] = [
			'interval' => 60 * 10,
			'display'  => __( '10 min', 'robin-image-optimizer' ),
		];
		$intervals['wio_30_min'] = [
			'interval' => 60 * 30,
			'display'  => __( '30 min', 'robin-image-optimizer' ),
		];
		$intervals['wio_hourly'] = [
			'interval' => 60 * 60,
			'display'  => __( '60 min', 'robin-image-optimizer' ),
		];
		$intervals['wio_daily']  = [
			'interval' => 60 * 60 * 24,
			'display'  => __( 'daily', 'robin-image-optimizer' ),
		];

		return $intervals;
	}

	/**
	 * Запуск Cron задачи
	 */
	public static function start() {
		$interval = WRIO_Plugin::app()->getPopulateOption( 'image_autooptimize_shedule_time', 'wio_5_min' );
		if ( ! wp_next_scheduled( 'wrio/cron/optimization_process' ) ) {
			$intervals = wp_get_schedules();
			wp_schedule_event( time(), $interval, 'wrio/cron/optimization_process' );
		}
	}

	/**
	 * Остановка Cron задачи
	 */
	public static function stop() {
		if ( wp_next_scheduled( 'wrio/cron/optimization_process' ) ) {
			wp_clear_scheduled_hook( 'wrio/cron/optimization_process' );
			WRIO_Plugin::app()->updatePopulateOption( 'cron_running', false ); // останавливаем крон
		}
	}

	/**
	 * Метод оптимизирует изображения при выполнении cron задачи
	 */
	public function process() {
		$max_process_per_request = WRIO_Plugin::app()->getPopulateOption( 'image_autooptimize_items_number_per_interation', 3 );
		$cron_running_page       = WRIO_Plugin::app()->getPopulateOption( 'cron_running', false );

		if ( ! $cron_running_page ) {
			return;
		}

		WRIO_Logger::info( sprintf( "Start cron job. Scope: %s", $cron_running_page ) );

		if ( 'media-library' == $cron_running_page ) {
			$media_library = WRIO_Media_Library::get_instance();
			$result        = $media_library->processUnoptimizedImages( $max_process_per_request );
		} else if ( 'nextgen' == $cron_running_page ) {
			$nextgen_gallery = WRIO_Nextgen_Gallery::get_instance();
			$result          = $nextgen_gallery->processUnoptimizedImages( $max_process_per_request );
		} else if ( 'custom-folders' == $cron_running_page ) {
			$cf     = WRIO_Custom_Folders::get_instance();
			$result = $cf->processUnoptimizedImages( $max_process_per_request );
		}

		if ( is_wp_error( $result ) ) {
			WRIO_Logger::info( sprintf( "Cron job failed. Error: %s", $result->get_error_message() ) );
			WRIO_Plugin::app()->deletePopulateOption( 'cron_running' );

			return;
		}

		if ( $result['remain'] <= 0 ) {
			WRIO_Plugin::app()->deletePopulateOption( 'cron_running' );
		}

		WRIO_Logger::info( sprintf( "End cron job. Scope: %s", $cron_running_page ) );
	}

}
