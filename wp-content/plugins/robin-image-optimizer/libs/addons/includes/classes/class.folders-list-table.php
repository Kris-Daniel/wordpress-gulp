<?php

/**
 * Class WRIO_Folders_List_Table
 * @author Alexander Kovalev <alex.kovalevv@gmail.com>
 */
class WRIO_Folders_List_Table extends WP_List_Table {
	
	public function __construct() {
		// Set parent defaults.
		parent::__construct( array(
			'singular' => 'media',     // Singular name of the listed records.
			'plural'   => 'media',    // Plural name of the listed records.
			'ajax'     => false,       // Does this table support ajax?
		) );
	}
	
	public function get_columns() {
		$columns = array(
			'cb'           => '<input type="checkbox" />', // Render a checkbox instead of text.
			'preview'      => _x( 'Preview', 'Column label', 'robin-image-optimizer' ),
			'file'         => _x( 'File path', 'Column label', 'robin-image-optimizer' ),
			'folder'       => _x( 'Folder', 'Column label', 'robin-image-optimizer' ),
			'status'       => _x( 'Status', 'Column label', 'robin-image-optimizer' ),
			'optimization' => _x( 'Optimization', 'Column label', 'robin-image-optimizer' )
		);
		
		return $columns;
	}
	
	public function prepare_items() {
		global $wpdb;
		
		$per_page = 20;
		
		$hidden                = array();
		$columns               = $this->get_columns();
		$sortable              = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;
		
		$folder_uid = WRIO_Plugin::app()->request->get( 'folder-filter', null, true );
		$status     = WRIO_Plugin::app()->request->get( 'status-filter', null, true );
		
		if ( ! in_array( $status, array( 'success', 'unoptimized', 'error' ) ) ) {
			$status = null;
		}
		
		$db_table = RIO_Process_Queue::table_name();
		
		$sql_filter = '';
		
		if ( ! empty( $status ) ) {
			$sql_filter .= " AND result_status = '" . esc_sql( $status ) . "' ";
		}
		
		if ( ! empty( $folder_uid ) ) {
			$sql_filter .= " AND item_hash_alternative = '" . esc_sql( $folder_uid ) . "' ";
		}
		
		$total_items = $wpdb->get_var( "
            SELECT COUNT(*)
            FROM {$db_table}
            WHERE item_type = 'cf_image' {$sql_filter}" );
		
		$rows = $wpdb->get_results( $wpdb->prepare( "
            SELECT *
            FROM {$db_table}
            WHERE item_type = 'cf_image' {$sql_filter}
            ORDER BY id DESC
            LIMIT %d
            OFFSET %d", $per_page, $offset ) );
		
		if ( empty( $rows ) ) {
			$this->items = array();
			
			return;
		}
		
		foreach ( (array) $rows as $key => $row ) {
			$rows[ $key ] = new RIO_Process_Queue( $row );
		}
		
		$this->items = $rows;
		
		$this->set_pagination_args( array(
			'total_items' => $total_items,                     // WE have to calculate the total number of items.
			'per_page'    => $per_page,                        // WE have to determine how many items to show on a page.
			'total_pages' => ceil( $total_items / $per_page ), // WE have to calculate the total number of pages.
		) );
	}
	
	
	public function display() {
		$cf      = WRIO_Custom_Folders::get_instance();
		$folders = $cf->getFolders();
		
		$folder_uid = WRIO_Plugin::app()->request->get( 'folder-filter', null, true );
		$status     = WRIO_Plugin::app()->request->get( 'status-filter', null, true );
		
		if ( ! in_array( $status, array( 'success', 'unoptimized', 'error' ) ) ) {
			$status = null;
		}
		
		$optimized_count   = RIO_Process_Queue::count_by_type_status( 'cf_image', 'success' );
		$unoptimized_count = RIO_Process_Queue::count_by_type_status( 'cf_image', 'unoptimized' );
		$error_count       = RIO_Process_Queue::count_by_type_status( 'cf_image', 'error' );
		
		?>
        <div class="wrap wriop-files-list">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form method="get" id="wriop-files-list-form" action="<?php echo admin_url( 'upload.php?page=rio-custom-media' ); ?>">
                <input type="hidden" name="page" value="rio-custom-media"/>
                <div class="wp-filter">
                    <div class="filter-items">
                        <label for="folder-filter" class="screen-reader-text"><?php _e( 'Filter by folder', 'robin-image-optimizer' ); ?></label>
                        <select class="folder-filters" name="folder-filter" id="folder-filter">
                            <option value="" selected="selected"><?php _e( 'All Folders', 'robin-image-optimizer' ); ?></option>
							<?php if ( ! empty( $folders ) ): ?>
								<?php foreach ( (array) $folders as $folder ): ?>
                                    <option <?php selected( $folder_uid, $folder->get( 'uid' ) ); ?> value="<?php echo esc_attr( $folder->get( 'uid' ) ); ?>"><?php echo esc_attr( $folder->get( 'path' ) ); ?>
                                        (<?php echo esc_attr( $folder->get( 'files_count' ) ); ?>)
                                    </option>
								<?php endforeach; ?>
							<?php endif; ?>
                        </select>
                        <label for="status-filter" class="screen-reader-text"><?php _e( 'Filter by status', 'robin-image-optimizer' ); ?></label>
                        <select class="folder-filters" name="status-filter" id="status-filter">
                            <option value="" selected="selected"><?php _e( 'All Media Files', 'robin-image-optimizer' ); ?></option>
                            <option <?php selected( $status, 'success' ); ?> value="success"><?php _e( 'Optimized', 'robin-image-optimizer' ); ?>
                                (<?php echo esc_attr( $optimized_count ); ?>)
                            </option>
                            <option <?php selected( $status, 'unoptimized' ); ?> value="unoptimized"><?php _e( 'Unoptimized', 'robin-image-optimizer' ); ?>
                                (<?php echo esc_attr( $unoptimized_count ); ?>)
                            </option>
                            <option <?php selected( $status, 'error' ); ?> value="error"><?php _e( 'Errors', 'robin-image-optimizer' ); ?>
                                (<?php echo esc_attr( $error_count ); ?>)
                            </option>
                        </select>
                        <input type="submit" id="folders-query-submit" class="button" value="Filter">
                    </div>
                </div>
				<?php parent::display(); ?>
            </form>
        </div>
		<?php
	}
	
	protected function get_sortable_columns() {
		$sortable_columns = array();
		
		return $sortable_columns;
	}
	
	protected function get_bulk_actions() {
		$actions = array();
		
		return $actions;
	}
	
	/**
	 * @param RIO_Process_Queue $item
	 *
	 * @return string
	 */
	protected function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'],  // Let's simply repurpose the table's singular label ("movie").
			$item->get_id() // The value of the checkbox should be the record's ID.
		);
	}
	
	/**
	 * @param RIO_Process_Queue $item
	 */
	protected function column_preview( $item ) {
		
		/** @var WRIO_CF_Image_Extra_Data $extra_data */
		$extra_data = $item->get_extra_data();
		
		if ( ! empty( $extra_data ) ) {
			$file_relative_path = wp_normalize_path( $extra_data->get_file_path() );
			$image_url          = home_url( $file_relative_path );
			
			printf( '
            <span class="media-icon image-icon">
               <a href="%s"><img src="%s" class="attachment-60x60 size-60x60" alt="" width="60" height="60"></a>
            </span>', esc_url( $image_url ), esc_url( $image_url ) );
		}
	}
	
	/**
	 * @param RIO_Process_Queue $item
	 */
	protected function column_file( $item ) {
		
		/** @var WRIO_CF_Image_Extra_Data $extra_data */
		$extra_data = $item->get_extra_data();
		
		if ( ! empty( $extra_data ) ) {
			$file_relative_path = wp_normalize_path( $extra_data->get_file_path() );
			$file_name          = wp_basename( $file_relative_path );
			$image_url          = home_url( $file_relative_path );
			
			printf( '
            <p class="filename">
                <a href="%s">%s</a>
            </p>', esc_url( $image_url ), $file_name );
		}
	}
	
	/**
	 * @param RIO_Process_Queue $item
	 */
	protected function column_folder( $item ) {
		
		/** @var WRIO_CF_Image_Extra_Data $extra_data */
		$extra_data = $item->get_extra_data();
		
		if ( ! empty( $extra_data ) ) {
			$file_relative_path = wp_normalize_path( $extra_data->get_file_path() );
			$file_name          = wp_basename( $file_relative_path );
			$folder             = str_replace( $file_name, '', $file_relative_path );
			
			printf( '<code>%s</code > ', $folder );
		}
	}
	
	/**
	 * @param RIO_Process_Queue $item
	 */
	protected function column_status( $item ) {
		$statuses = array(
			'success'     => __( 'Success', 'robin-image-optimizer' ),
			'error'       => __( 'Error', 'robin-image-optimizer' ),
			'processing'  => __( 'Processing', 'robin-image-optimizer' ),
			'unoptimized' => __( 'Unoptimized', 'robin-image-optimizer' ),
			'skip'        => __( 'Skipped', 'robin-image-optimizer' ),
		);
		if ( isset( $statuses[ $item->get_result_status() ] ) ) {
			echo esc_attr( $statuses[ $item->get_result_status() ] );
		}
	}
	
	/**
	 * @param RIO_Process_Queue $item
	 */
	protected function column_optimization( $item ) {
		$cf = WRIO_Custom_Folders::get_instance();
		echo $cf->getMediaColumnContent( $item->get_id() );
	}
	
	protected function process_bulk_action() {
		// Detect when a bulk action is being triggered.
		if ( 'delete' === $this->current_action() ) {
			wp_die( 'Items deleted(or they would be if we had items to delete)! ' );
		}
	}
}