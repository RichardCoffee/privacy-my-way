<?php
/**
 * Class PMW_Trait_ParseArgs
 *
 * Add support for parsing incoming arrays
 *
 * @package Fluidity\Classes\Traits
 * @since 2.1.1
 *
 */
trait PMW_Trait_ParseArgs {

	protected function parse_args( $args ) {
		if ( ! $args ) return;
		foreach( $args as $prop => $value ) {
			if ( property_exists( $this, $prop ) ) {
				$this->{$prop} = $value;
			}
		}
	}

	protected function parse_all_args( $args ) {
		if ( ! $args ) return;
		foreach( $args as $prop => $value ) {
			$this->{$prop} = $value;
		}
	}

}
