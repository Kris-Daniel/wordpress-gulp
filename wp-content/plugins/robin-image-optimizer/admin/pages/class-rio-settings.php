<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс отвечает за работу страницы настроек
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_SettingsPage extends WRIO_Page {

	/**
	 * {@inheritdoc}
	 */
	public $id = 'rio_settings';

	/**
	 * {@inheritdoc}
	 */
	public $page_menu_dashicon = 'dashicons-admin-generic';

	/**
	 * {@inheritdoc}
	 */
	public $show_right_sidebar_in_options = true;

	/**
	 * @param WRIO_Plugin $plugin
	 */
	public function __construct( WRIO_Plugin $plugin ) {

		$this->menu_title                  = __( 'Settings', 'robin-image-optimizer' );
		$this->page_menu_short_description = __( 'Plugin configuration', 'robin-image-optimizer' );

		parent::__construct( $plugin );
	}

	/**
	 * Подключаем скрипты и стили для страницы
	 *
	 * @since 1.0.0
	 * @return void
	 * @see   Wbcr_FactoryPages414_AdminPage
	 *
	 */
	public function assets( $scripts, $styles ) {
		parent::assets( $scripts, $styles );

		$this->styles->add( WRIO_PLUGIN_URL . '/admin/assets/css/base-statistic.css' );
		$this->scripts->add( WRIO_PLUGIN_URL . '/admin/assets/js/restore-backup.js' );

		// Add Clearfy styles for HMWP pages
		if ( defined( 'WBCR_CLEARFY_PLUGIN_ACTIVE' ) ) {
			$this->styles->add( WCL_PLUGIN_URL . '/admin/assets/css/general.css' );
		}
	}


	/**
	 * Выводим предупреждения
	 *
	 */
	protected function warningNotice() {
		$upload_dir = wp_upload_dir();

		if ( ! wp_is_writable( $upload_dir['basedir'] ) ) {
			$this->printErrorNotice( __( 'Folder wp-content/uploads/ is unavailable for writing', 'robin-image-optimizer' ) );
		}

		$wio_backup = $upload_dir['basedir'] . '/wio_backup/';
		if ( file_exists( $wio_backup ) && ! wp_is_writable( $wio_backup ) ) {
			$this->printErrorNotice( __( 'Folder wp-content/uploads/wio-backup/ is unavailable for writing', 'robin-image-optimizer' ) );
		}

		if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON == true ) {
			$this->printErrorNotice( __( 'Cron is disabled in wp-config.php', 'robin-image-optimizer' ) );
		}
	}


	/**
	 * Метод должен передать массив опций для создания формы с полями.
	 * Созданием страницы и формы занимается фреймворк
	 *
	 * @since 1.0.0
	 * @return mixed[]
	 */
	public function getPageOptions() {
		$options = [];

		$options[] = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __( 'Main Settings', 'robin-image-optimizer' ) . '</strong><p>' . __( 'This section you can set main images optimization settings.', 'robin-image-optimizer' ) . '</p></div>'
		];

		$options[] = [
			'type'    => 'dropdown',
			'name'    => 'image_optimization_server',
			'title'   => __( 'Optimization server', 'robin-image-optimizer' ),
			'data'    => [
				[
					'server_1',
					__( 'Server 1 (✰✰✰✰✰) - image size limit up to 5 MB', 'robin-image-optimizer' ),

				],
				[
					'server_2',
					__( 'Server 2 (✰✰) - poor compression, image size limit up to 1 MB', 'robin-image-optimizer' )

				],
				[
					'server_3',
					__( "Server 3 (✰✰) - poor compression, you can't use it on a localhost", 'robin-image-optimizer' )
				],
				[
					'server_4',
					__( 'Server 4 (✰✰✰✰) no limits (beta)', 'robin-image-optimizer' )
				],
			],
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'We use several free servers for image optimization and can’t fully guarantee their stable performance. The server can be not available in some countries due to the political reasons. There is a solution: if one of the servers is not available or can’t optimize the image, you can try to switch to the alternative server. Each server has individual limitations for image weight and optimization level. By default, you have the best server with minimum limitations.', 'robin-image-optimizer' ),
			'default' => 'server_1',
		];

		// Радио переключатель
		$options[] = [
			'type'    => 'dropdown',
			'name'    => 'image_optimization_level',
			'way'     => 'buttons',
			'title'   => __( 'Compression mode', 'robin-image-optimizer' ),
			'data'    => [
				[
					'normal',
					__( 'Lossless', 'robin-image-optimizer' ),
					__( 'This mode provides lossless compression and your images will be optimized without visible changes. If you want an ideal image quality, we recommend this mode. The size of the files will be reduced approximately 2 times. If this is not enough for you, try other modes.', 'robin-image-optimizer' )
				],
				[
					'aggresive',
					__( 'Lossy', 'robin-image-optimizer' ),
					__( 'This mode provides an ideal optimization of your images without significant quality loss. The file size will be reduced approximately 5 times with a slight decrease in image quality. In most cases that cannot be seen with the naked eye.', 'robin-image-optimizer' )
				],
				[
					'ultra',
					__( 'High', 'robin-image-optimizer' ),
					__( 'This mode will use all available optimization methods for maximum image compression. The file size will be reduced approximately 7 times. The quality of some images may deteriorate slightly. Use this mode if you need the maximum weight reduction, and you are ready to accept the loss of image quality.', 'robin-image-optimizer' )
				],
				[
					'custom',
					__( 'Custom', 'robin-image-optimizer' ),
					__( 'This mode will use all available optimization methods for maximum image compression. The file size will be reduced approximately 7 times. The quality of some images may deteriorate slightly. Use this mode if you need the maximum weight reduction, and you are ready to accept the loss of image quality.', 'robin-image-optimizer' )
				]
			],
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'Select the compression mode appropriate for your case.', 'robin-image-optimizer' ),
			'default' => 'normal',
			'events'  => [
				'normal'    => [
					'hide' => '.factory-control-image_optimization_level_custom'
				],
				'aggresive' => [
					'hide' => '.factory-control-image_optimization_level_custom'
				],
				'ultra'     => [
					'hide' => '.factory-control-image_optimization_level_custom'
				],
				'custom'    => [
					'show' => '.factory-control-image_optimization_level_custom',
				],
			]
		];

		// Текстовое поле
		$options[] = [
			'type'    => 'textbox',
			'name'    => 'image_optimization_level_custom',
			'title'   => __( 'Enter custom quality', 'robin-image-optimizer' ),
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'custom quality 1-100', 'robin-image-optimizer' ),
			'default' => '70'
		];

		// Переключатель
		$options[] = [
			'type'    => 'checkbox',
			'way'     => 'buttons',
			'name'    => 'auto_optimize_when_upload',
			'title'   => __( 'Auto optimization on upload', 'robin-image-optimizer' ),
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'Automatically compress all images that you upload directly to the WordPress media library, when editing pages and posts or using themes and plugins.', 'robin-image-optimizer' ),
			'default' => false
		];

		// Переключатель
		$options[] = [
			'type'    => 'checkbox',
			'way'     => 'buttons',
			'name'    => 'backup_origin_images',
			'title'   => __( 'Backup images', 'robin-image-optimizer' ),
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'green' ],
			'hint'    => __( 'Before optimizing, all your images will be saved in a separate folder for future recovery.', 'robin-image-optimizer' ),
			'default' => true
		];

		// Переключатель
		$options[] = [
			'type'      => 'checkbox',
			'way'       => 'buttons',
			'name'      => 'error_log',
			'title'     => __( 'Error Log', 'robin-image-optimizer' ),
			'layout'    => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'      => __( 'Enable error logging. The log will be displayed on a separate tab.', 'robin-image-optimizer' ),
			'default'   => false,
			'eventsOn'  => [
				'show' => '#wrio-error-log-options'
			],
			'eventsOff' => [
				'hide' => '#wrio-error-log-options'
			]
		];

		$options[] = [
			'type' => 'html',
			'html' => [ $this, 'error_log_options' ]
		];

		$options[] = [
			'type'      => 'checkbox',
			'way'       => 'buttons',
			'name'      => 'convert_webp_format',
			'title'     => __( 'Convert Images to WebP', 'robin-image-optimizer' ),
			'layout'    => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'      => __( 'Convert JPEG & PNG images into WebP format and replace them for browsers which support it. Unsupported browsers would be skipped.', 'robin-image-optimizer' ),
			'default'   => false,
			'cssClass'  => ! wrio_is_license_activate() ? [ 'factory-checkbox-disabled wrio-checkbox-premium-label' ] : [],
			'eventsOn'  => [
				'show' => '#wrio-webp-options'
			],
			'eventsOff' => [
				'hide' => '#wrio-webp-options'
			]
		];

		$options[] = [
			'type' => 'html',
			'html' => [ $this, 'conver_webp_options' ]
		];

		// восстановление
		$options[] = [
			'type' => 'html',
			'html' => [ $this, 'rollbackButton' ],
		];

		// Переключатель
		$options[] = [
			'type'    => 'checkbox',
			'way'     => 'buttons',
			'name'    => 'save_exif_data',
			'title'   => __( 'Leave EXIF data', 'robin-image-optimizer' ),
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'EXIF is information stored in photos: camera model, shutter speed, exposure compensation, ISO, GPS, etc. By default, the plugin removes EXIF extended data. If your project is dedicated to photography and you need to display this data, then enable this option.', 'robin-image-optimizer' ),
			'default' => true
		];

		$options[] = [
			'type' => 'html',
			'html' => '<div class="wbcr-factory-page-group-header"><strong>' . __( 'Optimization', 'robin-image-optimizer' ) . '</strong><p>' . __( 'Here you can specify additional image optimization options.', 'robin-image-optimizer' ) . '</p></div>'
		];

		// Переключатель
		$options[] = [
			'type'      => 'checkbox',
			'way'       => 'buttons',
			'name'      => 'resize_larger',
			'title'     => __( 'Resizing large images', 'robin-image-optimizer' ),
			'layout'    => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'      => __( 'When you upload images from a camera or stock, they may be too high resolution and it is not necessary for web. The option allows you to automatically change images resolution on upload.', 'robin-image-optimizer' ),
			'default'   => false,
			// когда чекбокс включен показываем поле с классом .factory-control-resize_larger_w
			'eventsOn'  => [
				'show' => '.factory-control-resize_larger_w,.factory-control-resize_larger_h'
			],
			// когда чекбокс выключен, скрываем поле с классом .factory-control-resize_larger_w
			'eventsOff' => [
				'hide' => '.factory-control-resize_larger_w,.factory-control-resize_larger_h'
			]
		];

		// Текстовое поле
		$options[] = [
			'type'    => 'textbox',
			'name'    => 'resize_larger_w',
			'title'   => __( 'Enter the maximum width (px)', 'robin-image-optimizer' ),
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'Set the maximum images resolution on the long side. For horizontal images, this will be the width, and for vertical images - the height. The resolution of the images will be changed proportionally according to the set value.', 'robin-image-optimizer' ),
			'default' => '1600'
		];

		// Текстовое поле
		$options[] = [
			'type'    => 'textbox',
			'name'    => 'resize_larger_h',
			'title'   => __( 'Enter the maximum height (px)', 'robin-image-optimizer' ),
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'Set the maximum images resolution on the long side. For horizontal images, this will be the width, and for vertical images - the height. The resolution of the images will be changed proportionally according to the set value.', 'robin-image-optimizer' ),
			'default' => '1600'
		];

		// получаем зарегистрированные размеры изображений
		$wp_image_sizes  = wrio_get_image_sizes();
		$wio_image_sizes = [];
		foreach ( $wp_image_sizes as $key => $value ) {
			$wio_image_sizes[] = [
				$key,
				$key . ' - ' . $value['width'] . 'x' . $value['height'],
			];
		}

		$options[] = [
			'type'    => 'list',
			'way'     => 'checklist',
			'name'    => 'allowed_sizes_thumbnail',
			'title'   => __( 'Optimize thumbnails', 'robin-image-optimizer' ),
			'data'    => $wio_image_sizes,
			'layout'  => [ 'hint-type' => 'icon', 'hint-icon-color' => 'grey' ],
			'hint'    => __( 'Choose which sizes of thumbnails should be optimized and uncheck those that do not need optimization.', 'robin-image-optimizer' ),
			'default' => 'thumbnail,medium'
		];

		$options = apply_filters( 'wbcr/rio/settings_page/options', $options );

		$formOptions = [];

		$formOptions[] = [
			'type'  => 'form-group',
			'items' => $options,
			//'cssClass' => 'postbox'
		];

		return $formOptions;
	}

	/**
	 * Save advanced options in database
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 */
	public function beforeFormSave() {

		/**
		 * Used to save webp options. It can also be used to intercept
		 * other unregistered fields.
		 *
		 * @since 1.3.6
		 */
		do_action( "wrio/settings_page/berfore_form_save" );

		$error_log = (int) WRIO_Plugin::app()->request->post( WRIO_Plugin::app()->getPrefix() . 'error_log', 0 );

		if ( ! $error_log ) {
			return;
		}

		$keep_error_log_on_frontend = (int) WRIO_Plugin::app()->request->post( 'wrio_keep_error_log_on_frontend', 0 );

		WRIO_Plugin::app()->updatePopulateOption( 'keep_error_log_on_frontend', $keep_error_log_on_frontend );
	}

	/**
	 * This method adds advanced options for the "Convert Images to WebP" checkbox.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 */
	public function conver_webp_options() {

		/**
		 * This hook prints options for delivering webp images.
		 *
		 * @since 1.3.6
		 */
		do_action( "wrio/settings_page/conver_webp_options" );
	}

	/**
	 * This method adds advanced options for the "Error log" checkbox.
	 *
	 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
	 * @since  1.3.6
	 */
	public function error_log_options() {
		$this->view->print_template( 'part-settings-page-error-log-options', [
			'keep_error_log_on_frontend' => (int) WRIO_Plugin::app()->getPopulateOption( 'keep_error_log_on_frontend', 0 )
		] );
	}

	/**
	 * Кнопка восстановления изображений
	 */
	public function rollbackButton() {
		?>
        <div class="form-group form-group-checkbox factory-control-rollback-button">
            <label for="wio-clear-backup-btn" class="col-sm-6 control-label">
				<?php _e( 'Manage backups', 'robin-image-optimizer' ); ?>
                <span class="factory-hint-icon factory-hint-icon-red" data-toggle="factory-tooltip"
                      data-placement="right" title=""
                      data-original-title="<?php _e( 'You can restore the original images from a backup or clear them.', 'robin-image-optimizer' ); ?>">
							<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAkAAAAJCAQAAABKmM6bAAAAUUlEQVQIHU3BsQ1AQABA0X/komIrnQHYwyhqQ1hBo9KZRKL9CBfeAwy2ri42JA4mPQ9rJ6OVt0BisFM3Po7qbEliru7m/FkY+TN64ZVxEzh4ndrMN7+Z+jXCAAAAAElFTkSuQmCC"
                                 alt="">
						</span>
            </label>
            <input type="hidden" value="<?php echo wp_create_nonce( 'wio-iph' ) ?>" id="wio-iph-nonce">
            <div class="control-group col-sm-6">
                <div class="factory-buttons-way btn-group">
                    <a class="btn btn-default" id="wio-restore-backup-btn"
                       data-confirm="<?php _e( 'Are you sure?', 'robin-image-optimizer' ); ?>"
                       href="#"><?php _e( 'Restore', 'robin-image-optimizer' ); ?></a>
                    <a class="btn btn-default" id="wio-clear-backup-btn"
                       data-confirm="<?php _e( 'Are you sure that you want to clear image backups folder?', 'robin-image-optimizer' ); ?>"
                       href="#"><?php _e( 'Clear Backup', 'robin-image-optimizer' ); ?></a>
                </div>
				<?php //if ( WRIO_Plugin::app()->isNetworkAdmin() ) : ?>
                <!--    <div id="wio-multisite-mode" style="display:none;">-->
                <!--        <ul class="factory-list factory-from-control-list factory-checklist-way">-->
                <!--			--><?php
				//			$blogs = WIO_Multisite::getBlogs();
				//			?>
                <!--            <li>-->
                <!--                <label for="wbcr_io_multisite_blog_all">-->
                <!--						<span>-->
                <!--							<input value="all" id="wbcr_io_multisite_blog_all" type="checkbox">-->
                <!--						</span>-->
                <!--                    <span>-->
				<?php //echo __( 'Select all', 'robin-image-optimizer' ); ?><!--</span>-->
                <!--                </label>-->
                <!--            </li>-->
                <!--			--><?php //foreach ( $blogs as $blog ) : ?>
                <!--                <li>-->
                <!--                    <label for="wbcr_io_multisite_blog_-->
				<?php //echo esc_attr( $blog->blog_id ); ?><!--">-->
                <!--						--><?php
				//						$blog_name = $blog->domain . $blog->path;
				//						if ( defined( 'SUBDOMAIN_INSTALL' ) && SUBDOMAIN_INSTALL ) {
				//							$blog_name = $blog->domain;
				//						}
				//						?>
                <!--                        <span>-->
                <!--								<input data-name="--><?php //echo esc_attr( $blog_name ); ?><!--"-->
                <!--                                       class="wbcr_io_multisite_blogs"-->
                <!--                                       value="--><?php //echo esc_attr( $blog->blog_id ); ?><!--"-->
                <!--                                       id="wbcr_io_multisite_blog_-->
				<?php //echo esc_attr( $blog->blog_id ); ?><!--"-->
                <!--                                       type="checkbox">-->
                <!--							</span>-->
                <!--                        <span>--><?php //echo esc_attr( $blog_name ); ?><!--</span>-->
                <!--                    </label>-->
                <!--                </li>-->
                <!--			--><?php //endforeach; ?>
                <!--        </ul>-->
                <!--        <button id="wio-multisite-confirm" class="btn btn-default" type="button"-->
                <!--                data-action="">-->
				<?php //echo __( 'Start', 'robin-image-optimizer' ); ?><!--</button>-->
                <!--    </div>-->
                <!--    <div id="wio-multisite-restore-progress" style="display:none;">-->
                <!--    </div>-->
				<?php //endif; ?>
                <div class="progress" id="wio-restore-backup-progress" style="display:none;">
                    <div class="progress-bar progress-bar-success" role="progressbar" aria-valuenow="0"
                         aria-valuemin="0" aria-valuemax="100" style="width:0%">
                    </div>
                </div>
                <p id="wio-restore-backup-msg"
                   style="display:none;"><?php _e( 'Restore completed.', 'robin-image-optimizer' ); ?></p>
                <p id="wio-clear-backup-msg"
                   style="display:none;"><?php _e( 'The backup folder was cleared.', 'robin-image-optimizer' ); ?></p>
            </div>
        </div>
		<?php
	}
}
