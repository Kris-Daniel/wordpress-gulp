<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RIO_Smushit_Extra_Data is a  DTO model for saving extra data from post attachments in `extra_data`.
 *
 * @see RIO_Process_Queue::$extra_data for further information
 *
 * @author Alexander Teshabaev <sasha.tesh@gmail.com>
 */
class RIO_Smushit_Extra_Data extends RIO_Attachment_Extra_Data {

	/**
	 * @var int Final size in bytes.
	 */
	protected $optimized_size;
}
