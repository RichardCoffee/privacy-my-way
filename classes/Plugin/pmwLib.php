<?php
/**
 *
 * @package Privacy_My_Way
 * @subpackage Core
 * @since 20200429
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2020, Richard Coffee
 * @link https://github.com/RichardCoffee/privacy-my-way/blob/master/classes/Plugin/pmwLib.php
 */
defined( 'ABSPATH' ) || exit;


class PMW_Plugin_pmwLib extends PMW_Plugin_Library {


	public function php_version_reported() {
#		return '7.2.30';
#		return '7.3.17';
		return '7.4.5';
	}


}
