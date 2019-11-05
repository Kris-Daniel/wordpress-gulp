<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Класс отвечает за работу страницы логов.
 *
 * @author        Eugene Jokerov <jokerov@gmail.com>
 * @author        Alexander Teshabaev <sasha.tesh@gmail.com>
 * @copyright (c) 2018, Webcraftic
 * @version       1.0
 */
class WRIO_LogPage extends WRIO_Page {

	/**
	 * {@inheritdoc}
	 */
	public $id = 'rio_logs'; // Уникальный идентификатор страницы

	/**
	 * {@inheritdoc}
	 */
	public $page_menu_dashicon = 'dashicons-admin-tools';

	/**
	 * {@inheritdoc}
	 */
	public $type = 'page';

	/**
	 * @param WRIO_Plugin $plugin
	 */
	public function __construct( WRIO_Plugin $plugin ) {

		$this->menu_title                  = __( 'Error Log', 'robin-image-optimizer' );
		$this->page_menu_short_description = __( 'Plugin debug report', 'robin-image-optimizer' );

		parent::__construct( $plugin );
	}

	/**
	 * {@inheritdoc}
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function assets( $scripts, $styles ) {
		parent::assets( $scripts, $styles );

		$this->styles->add( WRIO_PLUGIN_URL . '/admin/assets/css/base-statistic.css' );

		// Add Clearfy styles for HMWP pages
		if ( defined( 'WBCR_CLEARFY_PLUGIN_ACTIVE' ) ) {
			$this->styles->add( WCL_PLUGIN_URL . '/admin/assets/css/general.css' );
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getMenuTitle() {
		return defined( 'LOADING_ROBIN_IMAGE_OPTIMIZER_AS_ADDON' ) ? __( 'Image optimizer', 'robin-image-optimizer' ) : __( 'Error Log', 'robin-image-optimizer' );
	}

	/**
	 * {@inheritdoc}
	 */
	public function showPageContent() {
		?>
        <div class="wbcr-factory-page-group-header" style="margin-top:0;">
            <strong><?php _e( 'Error Log', 'robin-image-optimizer' ) ?></strong>
            <p>
				<?php _e( 'In this section, you can track image optimization errors. Sending this log to us, will help in solving possible optimization issues.', 'robin-image-optimizer' ) ?>
            </p>
        </div>
        <div class="wbcr-factory-page-group-body" style="padding:20px">
            <div class="btn-group">
                <a href="<?php echo wp_nonce_url( $this->getPageUrl() . 'action=export' ) ?>"
                   class="btn btn-default"><?php _e( 'Export Debug Information', 'robin-image-optimizer' ) ?></a>
                <a href="#"
                   onclick="wrioLogCleanup(this);return false;"
                   data-working="<?php echo esc_attr__( 'Working...', 'robin-image-optimizer' ) ?>"
                   class="btn btn-default"><?php echo sprintf( __( 'Clean-up Logs (<span id="wbcr-log-size">%s</span>)', 'robin-image-optimizer' ), $this->get_log_size_formatted() ) ?></a>
            </div>
            <script>
				function wrioLogCleanup(element) {

					var btn = jQuery(element),
						currentBtnText = btn.html();

					console.log(btn.data('working'), btn);

					btn.text(btn.data('working'));

					jQuery.ajax({
						url: ajaxurl,
						method: 'post',
						data: {
							action: 'wrio_logs_cleanup',
							nonce: '<?php echo wp_create_nonce( 'wrio_clean_logs' ) ?>'
						},
						success: function(data) {
							btn.html(currentBtnText);

							jQuery('#wbcr-log-viewer').html('');
							jQuery('#wbcr-log-size').text('0B');
							jQuery.wbcr_factory_clearfy_209.app.showNotice(data.message, data.type);
						},
						error: function(jqXHR, textStatus, errorThrown) {
							jQuery.wbcr_factory_clearfy_209.app.showNotice('Error: ' + errorThrown + ', status: ' + textStatus, 'danger');
							btn.html(currentBtnText);
						}
					});
				}
            </script>
            <style>
                .wbcr-log-viewer {
                    width: 100%;
                    height: 650px;
                    font-family: "Menlo", "DejaVu Sans Mono", "Liberation Mono", "Consolas", "Ubuntu Mono", "Courier New", "andale mono", "lucida console", monospace;
                    font-size: 12px;
                    word-break: break-all;
                    word-wrap: break-word;
                    overflow: auto;
                    -ms-overflow-style: scrollbar;
                    background-color: #e9e9e9;
                    padding: 8px;
                    border: 1px solid #cfcfcf;
                }
            </style>
            <div class="wbcr-log-viewer" id="wbcr-log-viewer">
				<?php echo WIO_Log_Reader::prettify() ?>
            </div>
        </div>
		<?php
	}

	/**
	 * Processing log export action in form of ZIP archive.
	 */
	public function exportAction() {
		$export = new WIO_Log_Export();

		if ( $export->prepare() ) {
			$export->download( true );
		}
	}

	/**
	 * Get log size formatted.
	 *
	 * @return false|string
	 */
	private function get_log_size_formatted() {

		try {
			return size_format( WRIO_Logger::get_total_size() );
		} catch( \Exception $exception ) {
			WRIO_Logger::error( sprintf( 'Failed to get total log size as exception was thrown: %s', $exception->getMessage() ) );
		}

		return '';
	}
}
