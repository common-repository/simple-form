<?php
/**
 * Responsible for managing ajax endpoints.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */

namespace SIMPLEFORM;

// If direct access than exit the file.
defined( 'ABSPATH' ) || exit;

/**
 * Responsible for handling ajax endpoints.
 *
 * @since 2.12.15
 * @package SIMPLEFORM
 */
class Ajax {

	/**
	 * Contains promotional wppool products.
	 *
	 * @var \SIMPLEFORM\Ajax\FetchProducts
	 */
	public $products;

	/**
	 * Contains plugins notices ajax operations.
	 *
	 * @var \SIMPLEFORM\Ajax\ManageNotices
	 */
	public $notices;

	/**
	 * Contains table delete ajax operations.
	 *
	 * @var \SIMPLEFORM\Ajax\UdTables
	 */
	public $ud_tables;

	/**
	 * Contains plugin tables ajax operations.
	 *
	 * @var mixed
	 */
	public $tables;

	/**
	 * Contains plugin tabs ajax operations.
	 *
	 * @var mixed
	 */
	public $tabs;

	/**
	 * Contains plugin settings ajax endpoints.
	 *
	 * @var mixed
	 */
	public $settings;

	/**
	 * Class constructor.
	 *
	 * @since 2.12.15
	 */
	public function __construct() {
		$this->tables   = new \SIMPLEFORM\Ajax\Tables();
	}
}
