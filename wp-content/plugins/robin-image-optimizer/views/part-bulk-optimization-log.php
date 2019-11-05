<?php

defined( 'ABSPATH' ) || die( 'Cheatin’ uh?' );

/**
 * @var array                           $data
 * @var Wbcr_FactoryClearfy209_PageBase $page
 */
?>
<style>
    /**
	 * Стили временно в коде.
	 * Если такой вариант реализации прокрутки для таблицы подойдёт, то стили нужно будет перенести в основной файл
	 * Пример взят с https://jsfiddle.net/tsayen/xuvsncr2/28/
	 */

    .wrio-table-container {
        height: 25em;
    }

    .wrio-table-container table {
        display: flex;
        flex-flow: column;
        height: 100%;
        width: 100%;
    }

    .wrio-table-container table thead {
        /* head takes the height it requires,
		and it's not scaled when table is resized */
        flex: 0 0 auto;
        width: calc(100% - 0.9em);
    }

    .wrio-table-container table tbody {
        /* body takes all the remaining available space */
        flex: 1 1 auto;
        display: block;
        overflow-y: scroll;
    }

    .wrio-table-container table tbody tr {
        width: 100%;
    }

    .wrio-table-container table thead,
    .wrio-table-container table tbody tr {
        display: table;
        table-layout: fixed;
    }

    .wrio-table-container table tbody tr {
        width: 100%;
        word-break: break-all;

    }

    .flash {
        -moz-animation: flash 1s ease-out;
        -webkit-animation: flash 1s ease-out;
        -ms-animation: flash 1s ease-out;
        animation: flash 1s ease-out;
    }

    @-webkit-keyframes flash {
        0% {
            background-color: transparent;
        }
        30% {
            background-color: #fffade;
        }
        100% {
            background-color: transparent;
        }
    }

    @-moz-keyframes flash {
        0% {
            background-color: transparent;
        }
        30% {
            background-color: #fffade;
        }

        100% {
            background-color: transparent;
        }
    }

    @-ms-keyframes flash {
        0% {
            background-color: transparent;
        }
        30% {
            background-color: #fffade;
        }
        100% {
            background-color: transparent;
        }
    }
</style>
<div class="wrio-optimization-progress">
    <div class="wbcr-factory-page-group-header" style="margin-bottom:0;">
        <strong><?php _e( 'Optimization log', 'robin-image-optimizer' ); ?></strong>
        <p><?php _e( 'Optimization log shows the last 100 optimized images. You can check the quality of the image by clicking on the file name.', 'robin-image-optimizer' ) ?></p>
    </div>
    <div class="wrio-table-container<?php if ( empty( $data['process_log'] ) ) : ?>-empty<?php endif; ?>">
        <table class="wrio-table">
            <thead>
            <tr>
                <th></th>
                <th><?php _e( 'File name', 'robin-image-optimizer' ); ?></th>
                <th><?php _e( 'Inital size', 'robin-image-optimizer' ); ?></th>
                <th><?php _e( 'Current size', 'robin-image-optimizer' ); ?></th>
                <th><?php _e( 'WebP size', 'robin-image-optimizer' ); ?></th>
                <th><?php _e( 'Original Saving', 'robin-image-optimizer' ); ?></th>
				<?php if ( $data['scope'] !== 'custom-folders' ): ?>
                    <th><?php _e( 'Compressed thumbnails', 'robin-image-optimizer' ); ?></th>
				<?php endif; ?>
                <th><?php _e( 'Overall Saving', 'robin-image-optimizer' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php if ( empty( $data['process_log'] ) ) : ?>
                <tr>
                    <td colspan="<?php echo( $data['scope'] !== 'custom-folders' ? '8' : '7' ); ?>"><?php _e( "You don't have optimized images.", 'robin-image-optimizer' ); ?></td>
                </tr>
			<?php else: ?>
				<?php foreach ( (array) $data['process_log'] as $item ) : ?>
					<?php if ( isset( $item['type'] ) && $item['type'] == 'error' ): ?>
                        <tr class="wrio-table-item wrio-row-id-<?php echo esc_attr( $item['id'] ); ?> wrio-error">
                            <td>
                                <img width="40" height="40" src="<?php echo esc_attr( $item['thumbnail_url'] ); ?>" alt="">
                            </td>
                            <td>
                                <a href="<?php echo esc_attr( $item['url'] ); ?>" target="_blank"><?php echo esc_attr( $item['file_name'] ); ?></a>
                            </td>
                            <td colspan="<?php echo( $data['scope'] !== 'custom-folders' ? '6' : '5' ); ?>">
								<?php _e( 'Error', 'robin-image-optimizer' ); ?>:
								<?php if ( isset( $item['error_msg'] ) ): ?>
									<?php echo esc_attr( $item['error_msg'] ); ?>
								<?php else: ?>
									<?php _e( 'Unknown error.', 'robin-image-optimizer' ); ?>
								<?php endif; ?>
                            </td>
                        </tr>
					<?php else: ?>
                        <tr class="wrio-table-item wrio-row-id-<?php echo esc_attr( $item['id'] ); ?>">
                            <td>
                                <img width="40" height="40" src="<?php echo esc_attr( $item['thumbnail_url'] ); ?>" alt="">
                            </td>
                            <td>
                                <a href="<?php echo esc_attr( $item['url'] ); ?>"><?php echo esc_attr( $item['file_name'] ); ?></a>
                            </td>
                            <td>
								<?php echo esc_attr( $item['original_size'] ); ?>
                            <td>
								<?php echo esc_attr( $item['optimized_size'] ); ?>
                            </td>
                            <td>
								<?php echo( ! empty( $item['webp_size'] ) ? esc_attr( $item['webp_size'] ) : '-' ); ?>
                            </td>
                            <td>
								<?php echo esc_attr( $item['original_saving'] ); ?>
                            </td>
							<?php if ( $data['scope'] !== 'custom-folders' ): ?>
                                <td>
									<?php echo esc_attr( $item['thumbnails_count'] ); ?>
                                </td>
							<?php endif; ?>
                            <td>
								<?php echo esc_attr( $item['total_saving'] ); ?>
                            </td>
                        </tr>
					<?php endif; ?>
				<?php endforeach; ?>
			<?php endif; ?>
            </tbody>
        </table>
    </div>
</div>