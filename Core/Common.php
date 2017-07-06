<?php
namespace ixavier\Libraries\Core;

use Illuminate\Support\Debug\Dumper;

/**
 * Class Common holds common helper functions that are used throughout
 *
 * @package ixavier\Libraries\Core
 */
class Common
{
	/** @var array Keeps characters to clean */
	private static $diacriticsFromChars;

	/** @var array Keeps characters to map to */
	private static $diacriticsToChars;

	/**
	 * Remove any weird characters
	 *
	 * @param string $str
	 * @return string
	 */
	public static function cleanDiacritics( $str ) {
		if ( empty( static::$diacriticsFromChars ) ) {
			static::$diacriticsFromChars = "ąàáäâãåæăćčĉęèéëêĝĥìíïîĵłľńňòóöőôõðøśșşšŝťțţŭùúüűûñÿýçżźž";
			static::$diacriticsFromChars .= strtoupper( static::$diacriticsFromChars );
			static::$diacriticsFromChars .= "ß";
		}
		if ( empty( static::$diacriticsToChars ) ) {
			static::$diacriticsToChars = "aaaaaaaaaccceeeeeghiiiijllnnoooooooossssstttuuuuuunyyczzz";
			static::$diacriticsToChars .= strtoupper( static::$diacriticsToChars );
		}

		$str = preg_replace_callback( "/.{1}/", function( $c ) {
			$c = array_pop( $c );
			$index = strpos( static::$diacriticsFromChars, $c );

			return $index === FALSE ? $c : static::$diacriticsToChars[ $index ];
		}, $str );

		return $str;
	}

	// @todo: Recognize latin characters: i.e. ñ, ó, etc
	/**
	 * Converts given $str string to a proper slug being cut off at given $length
	 *
	 * @param string $str
	 * @param int $length
	 * @return string
	 */
	public static function slugify( $str, $length = 140 ) {
		$str = strtolower( $str );
		// dasherize
		$str = preg_replace( "/([A-Z])/", "-$1", $str );
		$str = preg_replace( "/[-_\s]+/", "-", $str );

		// cleanDiacritics
		$str = self::cleanDiacritics( $str );

		// spaces
		$str = preg_replace( "/[^\w\s-]/", "-", $str );

		// no '-' at edges
		$str = trim( $str, '-' );

		/// limit
		if ( strlen( $str ) > $length ) {
			$str = substr( $str, strlen( $str ) - $length );
		}

		return $str;
	}

	/**
	 * Converts options into an array of key=>bool pair to check as flags.
	 *
	 * @param array|string $options An array of flag=>value pair or a space-separate string of flags to be ON; i.e. "flag1 flag2" ==> [flag1=>true, flag2=>true]
	 * @param array|string $defaults If nothing found on $options, will fall back on this.
	 *  If string passed, it needs to be a space-separate string of flags to be OFF; i.e. "flag1 flag2" ==> [flag1=>false, flag2=>false]
	 * @return array
	 */
	public static function fixOptions( $options, $defaults = [] ) {
		if ( is_string( $options ) && !empty( $options ) ) {
			$options = explode( ' ', $options );
			$options = array_combine( $options, array_fill( 0, count( $options ), true ) );
		}
		else if ( !is_array( $options ) ) {
			$options = [];
		}

		if ( is_string( $defaults ) && !empty( $defaults ) ) {
			$defaults = explode( ' ', $defaults );
			$defaults = array_combine( $defaults, array_fill( 0, count( $defaults ), false ) );
		}
		else if ( !is_array( $defaults ) ) {
			$defaults = [];
		}

		return array_merge( $defaults, $options );
	}

	/**
	 * @param string $class Class name
	 * @return string Basename of this class
	 */
	public static function getClassBasename( $class ) {
		return basename( str_replace( "\\", "/", $class ) );
	}

	/**
	 * @param string $class Class name
	 * @return string Namespace of this class
	 */
	public static function getClassNamespace( $class ) {
		return str_replace( "/", "\\", dirname( str_replace( "\\", "/", $class ) ) );
	}

	/**
	 * Same as PHP's array_walk_recursive, but works with objects as well
	 * @param array|object $array
	 * @param \Closure $closure
	 * @param mixed $userData
	 */
	public static function array_walk_recursive( &$array, $closure, $userData = null ) {
		foreach ( $array as $key => &$value ) {
			$closure( $value, $key, $userData );

			if ( is_object( $value ) || is_array( $value ) ) {
				static::array_walk_recursive( $value, $closure, $userData );
			}
		}
	}

	/**
	 * Same as dd(), but doesn't die.
	 */
	public static function pd() {
		array_map( function( $x ) {
			( new Dumper )->dump( $x );
		}, func_get_args() );
	}

	/**
	 * Prints backtrace for HTML
	 */
	public static function printBacktrace() {
		print "<pre>";
		debug_print_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		print "</pre>";
	}
}
