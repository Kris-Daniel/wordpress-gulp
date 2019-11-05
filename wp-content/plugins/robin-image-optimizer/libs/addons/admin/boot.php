<?php
/**
 * Обычно в этом файле размещает код, который отвечает за уведомление, совместимость с другими плагинами,
 * незначительные функции, которые должны быть выполнены на всех страницах админ панели.
 *
 * В этом файле должен быть размещен код, которые относится только к области администрирования.
 *
 * @author    Webcraftic <wordpress.webraftic@gmail.com>
 * @copyright Webcraftic 19.09.2018
 * @version   1.0
 */

// Exit if accessed directly
use WRIO\WEBP\HTML\Delivery;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Flush configuration after saving the settings
 *
 * @param WHM_Plugin                               $plugin
 * @param Wbcr_FactoryPages414_ImpressiveThemplate $page
 *
 * @return bool
 */
add_action( 'wbcr/factory/pages/impressive/after_form_save', function ( $plugin, $page ) {
	$is_rio_plugin    = WRIO_Plugin::app()->getPluginName() == $plugin->getPluginName();
	$is_settings_page = "rio_settings" == $page->id;
	$is_apache        = WRIO\WEBP\Server::is_apache();
	$is_use_htaccess  = WRIO\WEBP\Server::server_use_htaccess();

	if ( $is_rio_plugin && $is_settings_page ) {
		if ( WRIO\WEBP\HTML\Delivery::is_webp_enabled() && WRIO\WEBP\HTML\Delivery::is_redirect_delivery_mode() ) {
			if ( $is_apache && $is_use_htaccess ) {
				WRIO\WEBP\Server::htaccess_update_webp_rules();
			}

			return;
		}

		if ( $is_apache && $is_use_htaccess ) {
			WRIO\WEBP\Server::htaccess_clear_webp_rules();
		}
	}
}, 10, 2 );


