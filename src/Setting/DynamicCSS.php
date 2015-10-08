<?php

namespace ProteusThemes\CustomizerUtils\Setting;

/**
 * Custom setting data type, capable of auto-generating the CSS output for the color variants.
 *
 * Since quite some settings in the customizer are color-CSS related, we can abstract out that
 * in a way that we have a custom data type `ProteusThemes_Customize_Setting_Dynamic_CSS` which is capable
 * of dynamically generate the CSS out from the provided array `$css_props`.
 */

class DynamicCSS extends \WP_Customize_Setting {
	/**
	 * 2D Array the CSS properties maped to the CSS selectors.
	 * Each propery can have multiple selectors.
	 *
	 * @var array( // list of all css properties this setting controls
	 * 	array( // each property in it's own array
	 * 		'name'  => 'color',
	 * 		'selectors' => array(
	 * 			'noop' => array( // regular selectors
	 * 				'.selector1',
	 * 				'.selector2',
	 * 			),
	 * 			'@media (min-width: 900px)' => array( // selectors which should be in MQ
	 * 				'.selector3',
	 * 				'.selector4',
	 * 			),
	 * 		),
	 * 		'modifier'  => $darker10, // separate data type
	 * 	)
	 */
	public $css_props = array();

	/**
	 * Default transport method for this setting type is 'postMessage'.
	 *
	 * @access public
	 * @var string
	 */
	public $transport = 'postMessage';

	/**
	 * Getter function for the raw $css_props property.
	 * @return 2D array
	 */
	public function get_css_props() {
		return $this->css_props;
	}

	/**
	 * Return all variants of the CSS propery with selectors.
	 * Optionally filtered with the modifier.
	 *
	 * @param string $name     Name of the css propery, ie. color, background-color
	 * @param string $modifier Optional modifier to further filter down the css props returned.
	 * @return array
	 */
	public function get_single_css_prop( $name, $modifier = false ) {
		return array_filter( $this->css_props, function ( $property ) {
			if ( false !== $modifier ) {
				return $name === $property['name'] && $modifier == $property['modifier'];
			}
			else {
				return $name === $property['name'];
			}
		} );
	}

	/**
	 * Render the CSS for this setting.
	 * @return string text/css
	 */
	public function render_css() {
		$out = '';

		foreach ( $this->css_props as $property ) {
			foreach ( $property['selectors'] as $mq => $selectors ) {
				$css_selectors = implode( ', ', $selectors );
				$value         = $this->value();

				if ( array_key_exists( 'modifier', $property ) ) {
					$value = $this->apply_modifier( $value, $property['modifier'] );
				}

				if ( 'noop' === $mq ) { // essentially no media query
					$out .= sprintf( '%1$s { %2$s: %3$s; }', $css_selectors, $property['name'], $value );
				}
				else { // we have an actual media query
					$out .= sprintf( '%4$s { %1$s { %2$s: %3$s; } }', $css_selectors, $property['name'], $value, $mq );
				}

				$out .= PHP_EOL;
			}
		}

		return $out;
	}

	/**
	 * Apply modifier to the untouched value.
	 * @param  string $in       Setting value.
	 * @param  callable|DynamicCSS\ModInterface $modifier
	 * @return string           Modified value.
	 */
	private function apply_modifier( $in, $modifier ) {
		$out = $in;

		if ( $modifier instanceof DynamicCSS\ModInterface ) {
			$out = $modifier->modify( $out );
		}
		else if ( is_callable( $modifier ) ) {
			$out = call_user_func( $modifier, $out );
		}

		return $out;
	}
}