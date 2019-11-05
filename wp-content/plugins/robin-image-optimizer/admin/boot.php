<?php
/**
 * Admin boot
 *
 * @author    Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright Webcraftic 25.05.2017
 * @version   1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Проверяем таблицу в базе данных
 *
 * Если таблица не существует или её структура устарела, то обновляем.
 * Проверка проводится при каждой инициализации плагина т.к. структура может измениться
 * после очередного обновления плагина.
 *
 * @return bool
 */
add_action( 'admin_init', function () {
	RIO_Process_Queue::try_create_plugin_tables();
} );

/**
 * Удаляет карточку компонента в плагине Clearfy.
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @since  1.3.0
 */
add_filter( 'wbcr/clearfy/components/items_list', function ( $components ) {
	if ( wrio_is_clearfy_license_activate() ) {
		return $components;
	}
	if ( ! empty( $components ) ) {
		foreach ( $components as $key => $component ) {
			if ( "robin_image_optimizer" == $component['name'] ) {
				unset( $components[ $key ] );
			}
		}
	}

	return $components;
} );

/**
 * Добавляет карточку компонента на страницу компонентов
 * в плагине Clearfy.
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @since  1.3.0
 */
add_action( 'wbcr/clearfy/components/custom_plugins_card', function () {
	if ( ! wrio_is_clearfy_license_activate() ) {
		$view = WRIO_Views::get_instance( WRIO_PLUGIN_DIR );
		$view->print_template( 'clearfy-component-card' );
	}
} );

/**
 * We asset migration scripts to all admin panel pages
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @since  1.3.0
 */
add_action( 'admin_enqueue_scripts', function () {
	if ( ! current_user_can( 'update_plugins' ) || ! wbcr_rio_has_meta_to_migrate() ) {
		return;
	}

	wp_enqueue_script( 'wrio-meta-migrations', WRIO_PLUGIN_URL . '/admin/assets/js/meta-migrations.js', [
		'jquery',
		'wbcr-factory-clearfy-209-global'
	], WRIO_Plugin::app()->getPluginVersion() );
} );

/**
 * Plugin was heavy migrated into new architecture. Specifically, post meta was moved to separate table and
 * therefore it is required to migrate all of them to new table.
 *
 * This action prints a notice, which contains clickable link with JS onclick event, which invokes AJAX request
 * to migrate these post metas to new table.
 *
 * Once all post meta migrated, notice would not be shown anymore.
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @since  1.3.0
 *
 * @param $notices
 *
 * @return array
 * @see    wbcr_rio_migrate_postmeta_to_process_queue() for further information about AJAX processing function.
 * @see    wbcr_rio_has_meta_to_migrate() used to check whether to show notice or not.
 *
 * @see    RIO_Process_Queue for further information about new table.
 */
add_action( "wbcr_factory_notices_000_list", function ( $notices ) {

	if ( ! current_user_can( 'update_plugins' ) || ! wbcr_rio_has_meta_to_migrate() ) {
		return $notices;
	}

	$notices[] = [
		'id'              => WRIO_Plugin::app()->getPrefix() . 'meta_to_migration',
		'type'            => 'warning',
		'dismissible'     => false,
		'dismiss_expires' => 0,
		'text'            => "<p><b>" . WRIO_Plugin::app()->getPluginTitle() . ":</b> " . wrio_get_meta_migration_notice_text() . '</p>'
	];

	return $notices;
} );

/**
 * Plugin was heavy migrated into new architecture. Specifically, post meta was moved to separate table and
 * therefore it is required to migrate all of them to new table.
 *
 * This action prints a notice, which contains clickable link with JS onclick event, which invokes AJAX request
 * to migrate these post metas to new table.
 *
 * Once all post meta migrated, notice would not be shown anymore.
 *
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 * @since  1.3.0
 *
 * @param Wbcr_Factory414_Plugin                   $plugin
 * @param Wbcr_FactoryPages414_ImpressiveThemplate $obj
 *
 * @see    wbcr_rio_migrate_postmeta_to_process_queue() for further information about AJAX processing function.
 * @see    wbcr_rio_has_meta_to_migrate() used to check whether to show notice or not.
 *
 * @see    RIO_Process_Queue for further information about new table.
 */
add_action( 'wbcr/factory/pages/impressive/print_all_notices', function ( $plugin, $obj ) {
	if ( ( $plugin->getPluginName() != WRIO_Plugin::app()->getPluginName() ) || ! wbcr_rio_has_meta_to_migrate() ) {
		return;
	}

	$obj->printWarningNotice( wrio_get_meta_migration_notice_text() );
}, 10, 2 );

/***
 * Flush configuration after saving the settings
 *
 * @param WHM_Plugin                               $plugin
 * @param Wbcr_FactoryPages414_ImpressiveThemplate $obj
 *
 * @return bool
 */
/*add_action('wbcr_factory_414_imppage_after_form_save', function ($plugin, $obj) {
	$is_rio = WRIO_Plugin::app()->getPluginName() == $plugin->getPluginName();

	if( $is_rio ) {
		WRIO_Cron::check();
	}
}, 10, 2);*/

/**
 * Виджет отзывов
 *
 * @param string $page_url
 * @param string $plugin_name
 *
 * @return string
 */
function wio_rating_widget_url( $page_url, $plugin_name ) {
	if ( $plugin_name == WRIO_Plugin::app()->getPluginName() ) {
		return 'https://wordpress.org/support/plugin/robin-image-optimizer/reviews/#new-post';
	}

	return $page_url;
}

add_filter( 'wbcr_factory_pages_414_imppage_rating_widget_url', 'wio_rating_widget_url', 10, 2 );

/**
 * Widget with the offer to buy Clearfy Business
 *
 * @param array                  $widgets
 * @param string                 $position
 * @param Wbcr_Factory414_Plugin $plugin
 */
add_filter( 'wbcr/factory/pages/impressive/widgets', function ( $widgets, $position, $plugin ) {
	if ( $plugin->getPluginName() == WRIO_Plugin::app()->getPluginName() ) {
		require_once WRIO_PLUGIN_DIR . '/admin/includes/sidebar-widgets.php';

		if ( wrio_is_license_activate() ) {
			unset( $widgets['donate_widget'] );

			if ( $position == 'right' ) {
				unset( $widgets['businnes_suggetion'] );
				unset( $widgets['rating_widget'] );
				unset( $widgets['info_widget'] );
			}

			if ( $position == 'bottom' ) {
				$widgets['support'] = wrio_get_sidebar_support_widget();
			}

			return $widgets;
		} else {
			if ( $position == 'right' ) {
				unset( $widgets['info_widget'] );
				unset( $widgets['rating_widget'] );
				$widgets['support'] = wrio_get_sidebar_support_widget();
			}
		}

		if ( $position == 'bottom' ) {
			$widgets['donate_widget'] = wrio_get_sidebar_premium_widget();
		}
	}

	return $widgets;
}, 20, 3 );

/**
 * Заменяет заголовок в рекламном виджете
 *
 * @param array  $features
 * @param string $plugin_name
 * @param string $page_id
 */
add_filter( 'wbcr/clearfy/pages/suggetion_title', function ( $features, $plugin_name, $page_id ) {
	if ( ! empty( $plugin_name ) && ( $plugin_name == WRIO_Plugin::app()->getPluginName() ) ) {
		return __( "ROBIN IMAGE OPTIMIZER PRO", 'robin-image-optimizer' );
	}

	return $features;
}, 20, 3 );

/**
 * Заменяем премиум возможности в рекламном виджете
 *
 * @param array  $features
 * @param string $plugin_name
 * @param string $page_id
 */
add_filter( 'wbcr/clearfy/pages/suggetion_features', function ( $features, $plugin_name, $page_id ) {
	if ( ! empty( $plugin_name ) && ( $plugin_name == WRIO_Plugin::app()->getPluginName() ) ) {
		$upgrade_feature   = [];
		$upgrade_feature[] = __( 'Automatic convertation in Webp', 'robin-image-optimizer' );
		$upgrade_feature[] = __( 'You can optimize custom folders', 'robin-image-optimizer' );
		$upgrade_feature[] = __( 'Support Nextgen gallery', 'robin-image-optimizer' );
		$upgrade_feature[] = __( 'Multisite support', 'robin-image-optimizer' );
		$upgrade_feature[] = __( 'Fast optimization servers', 'robin-image-optimizer' );
		$upgrade_feature[] = __( 'No ads', 'robin-image-optimizer' );
		$upgrade_feature[] = __( 'Best support', 'robin-image-optimizer' );

		return $upgrade_feature;
	}

	return $features;
}, 20, 3 );

/**
 * Заменяем премиум возможности в рекламном виджете
 *
 * @param array  $messages
 * @param string $type
 * @param string $plugin_name
 */
add_filter( 'wbcr/factory/premium/notice_text', function ( $text, $type, $plugin_name ) {
	if ( WRIO_Plugin::app()->getPluginName() != $plugin_name ) {
		return $text;
	}

	$license_page_url = WRIO_Plugin::app()->getPluginPageUrl( 'rio_license' );

	if ( 'need_activate_license' == $type ) {
		return sprintf( __( '<a href="%s">License activation</a> required. A license is required to get premium plugin updates, as well as to get additional services.', 'robin-image-optimizer' ), $license_page_url );
	} else if ( 'need_renew_license' == $type ) {
		return sprintf( __( 'Your <a href="%s">license</a> has expired. You can no longer get premium plugin updates, premium support and your access to Webcraftic services has been suspended.', 'robin-image-optimizer' ), $license_page_url );
	}

	return $text;
}, 10, 3 );



