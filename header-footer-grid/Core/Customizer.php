<?php
/**
 * Customizer class for Header Footer Grid.
 * Takes care of all the Customizer logic.
 *
 * Name:    Header Footer Grid
 * Author:  Bogdan Preda <bogdan.preda@themeisle.com>
 *
 * @version 1.0.0
 * @package HFG
 */

namespace HFG\Core;

use HFG\Core\Builder\Abstract_Builder;
use HFG\Core\Interfaces\Builder;
use HFG\Core\Settings\Config;
use HFG\Main;
use HFG\Traits\Core;
use WP_Customize_Manager;

/**
 * Class Customizer
 *
 * @package HFG\Core
 */
class Customizer {
	use Core;


	/**
	 * Holds the builders to register.
	 *
	 * @since   1.0.0
	 * @access  private
	 * @var array $builders
	 */
	private $builders = array();

	/**
	 * Customizer constructor.
	 *
	 * @since   1.0.0
	 * @access  public
	 */
	public function __construct() {
		$theme_support = get_theme_support( 'hfg_support' );
		$theme_support = apply_filters( 'hfg_theme_support_filter', $theme_support );
		if ( empty( $theme_support ) ) {
			return;
		}
		$theme_support = reset( $theme_support );
		$theme_support = apply_filters( 'hfg_support_components_filter', $theme_support );
		foreach ( $theme_support['builders'] as $builder => $components ) {
			if ( class_exists( $builder ) && in_array( 'HFG\Core\Interfaces\Builder', class_implements( $builder ), true ) ) {
				/**
				 * A new builder instance.
				 *
				 * @var Abstract_Builder $new_builder
				 */
				$new_builder = new $builder();
				foreach ( $components as $component ) {
					$new_builder->register_component( $component );
				}
				$this->builders[ $new_builder->get_id() ] = $new_builder;
			}
		}

		if ( is_admin() ) {
			add_action( 'customize_controls_enqueue_scripts', array( $this, 'scripts' ) );
		}

		if ( is_admin() || is_customize_preview() ) {
			add_action( 'customize_register', array( $this, 'register' ) );
			add_action( 'customize_preview_init', array( $this, 'preview_js' ) );
		}

		add_filter( 'body_class', array( $this, 'hfg_body_classes' ) );
		add_filter( 'neve_react_controls_localization', array( $this, 'add_builders_and_dynamic_tags' ) );
	}

	/**
	 * Add the dynamic tags options.
	 *
	 * @param array $array the localized array.
	 *
	 * @return array
	 */
	public function add_builders_and_dynamic_tags( $array ) {
		$array['HFG'] = $this->get_builders_data();
		$array['dynamicTags']['options'] = Magic_Tags::get_instance()->get_options();

		return $array;
	}

	/**
	 * Classes for the body tag.
	 *
	 * @param array $classes List of body classes.
	 *
	 * @return array
	 * @since   1.0.0
	 * @access  public
	 */
	public function hfg_body_classes( $classes ) {
		if ( is_customize_preview() ) {
			$classes[] = 'customize-previewing';
		}

		$sidebar_class = 'menu_sidebar_' . get_theme_mod( 'hfg_header_layout_sidebar_layout', 'slide_left' );

		$classes[] = $sidebar_class;

		return $classes;
	}

	/**
	 * Customizer script register.
	 *
	 * @since   1.0.0
	 * @access  public
	 */
	public function scripts() {
		// @todo abaicus remove?
		return false;

		$suffix = $this->get_assets_suffix();
		wp_enqueue_style(
			'hfg-customizer-control',
			esc_url( Config::get_url() ) . '/assets/css/admin/customizer/customizer' . $suffix . '.css',
			array(),
			Main::VERSION
		);

		wp_register_script(
			'hfg-layout-builder',
			esc_url( Config::get_url() ) . '/assets/js/customizer/builder' . $suffix . '.js',
			array(
				'customize-controls',
				'jquery-ui-resizable',
				'jquery-ui-droppable',
				'jquery-ui-draggable',
			),
			Main::VERSION,
			true
		);
		wp_localize_script(
			'hfg-layout-builder',
			'HFG_Layout_Builder',
			array(
				'footer_moved_widgets_text' => '',
				'builders'                  => $this->get_builders_data(),
				'isRTL'                     => is_rtl(),
			)
		);
		wp_enqueue_script( 'hfg-layout-builder' );

		/**
		 * A Builder Class instance.
		 *
		 * @var Builder $builder
		 */
		foreach ( $this->builders as $builder ) {
			$builder->scripts();
		}
	}

	/**
	 * Returns list of builders.
	 *
	 * @return array
	 * @since   1.0.0
	 * @access  public
	 */
	public function get_builders_data() {

		$builders_list = array();

		/**
		 * A Builder Class instance.
		 *
		 * @var Builder $builder
		 */
		foreach ( $this->builders as $key => $builder ) {
			$builders_list[ $key ] = $builder->get_builder();
		}

		return $builders_list;
	}

	/**
	 * Return builder object or whole list.
	 *
	 * @param string $name Builder id.
	 *
	 * @return Abstract_Builder[]|Abstract_Builder Builder object or list.
	 */
	public function get_builders( $name = '' ) {
		if ( isset( $this->builders[ $name ] ) ) {
			return $this->builders[ $name ];
		}

		return $this->builders;
	}

	/**
	 * Binds JS handlers to make Theme Customizer preview reload changes asynchronously.
	 *
	 * @since   1.0.0
	 * @access  public
	 */
	public function preview_js() {
		if ( ! is_customize_preview() ) {
			return;
		}
		$suffix = $this->get_assets_suffix();
		wp_enqueue_script(
			'hfg-customizer',
			esc_url( Config::get_url() ) . '/assets/js/customizer/customizer' . $suffix . '.js',
			array(
				'customize-preview',
				'customize-selective-refresh',
			),
			Main::VERSION,
			true
		);

	}

	/**
	 * Register builder controls and settings.
	 *
	 * @param WP_Customize_Manager $wp_customize The Customize Manager.
	 *
	 * @since   1.0.0
	 * @access  public
	 */
	public function register( WP_Customize_Manager $wp_customize ) {
		/**
		 * A Builder Class instance.
		 *
		 * @var Builder $builder
		 */
		foreach ( $this->builders as $builder ) {
			$builder->customize_register( $wp_customize );
		}

		$wp_customize->register_section_type( '\HFG\Core\Customizer\Instructions_Section' );
		$wp_customize->register_control_type( '\HFG\Core\Customizer\Instructions_Control' );
	}
}
