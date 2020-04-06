<?php
/**
 *  Add support for pre-loading class properties.
 *
 * @package Privacy_My_Way
 * @subpackage Traits
 * @since 20170128
 * @author Richard Coffee <richard.coffee@rtcenterprises.net>
 * @copyright Copyright (c) 2018, Richard Coffee
 * @link https://github.com/RichardCoffee/custom-post-type/blob/master/classes/Trait/ParseArgs.php
 */
defined( 'ABSPATH' ) || exit;


trait PMW_Trait_ParseArgs {

	/**
	 *  Parse key/value pairs that have a corresponding property.
	 *
	 * @since 20170128
	 * @param array $args  Values to be parsed.
	 */
	protected function parse_args( $args ) {
		if ( ! $args ) return;
		foreach( $args as $prop => $value ) {
			if ( property_exists( $this, $prop ) ) {
				$this->{$prop} = $value;
			}
		}
	}

	/**
	 *  Parse all key/value pairs into either existing properties or create new public properties.
	 *
	 * @since 20170128
	 * @param array $args  Values to be parsed.
	 */
	protected function parse_all_args( $args ) {
		if ( ! $args ) return;
		foreach( $args as $prop => $value ) {
			$this->{$prop} = $value;
		}
	}

	/**
	 *  Parse all args into existing properties.  Will do a top level merge of arrays.
	 *
	 * @since 20190624
	 * @param array $args  Values to be parsed.
	 */
	protected function parse_args_merge( $args ) {
		if ( ! $args ) return;
		foreach( $args as $prop => $value ) {
			if ( property_exists( $this, $prop ) ) {
				if ( is_array( $this->{$prop} ) ) {
					$this->{$prop} = array_merge( $this->{$prop}, $value );
				} else {
					$this->{$prop} = $value;
				}
			}
		}
	}


}
