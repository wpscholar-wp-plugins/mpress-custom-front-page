<?php

/**
 * Plugin Name: mPress Custom Front Page
 * Plugin URI: https://wpscholar.com/wordpress-plugins/mpress-custom-front-page/
 * Description: Easily set a custom post type as your front page.
 * Author: Micah Wood
 * Author URI: https://wpscholar.com
 * Version: 1.1
 * License: GPL3
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 *
 * Copyright 2012-2016 by Micah Wood - All rights reserved.
 */

define( 'MPRESS_CUSTOM_FRONT_PAGE_VERSION', '1.1' );

if ( ! class_exists( 'mPress_Custom_Front_Page' ) ) {

	/**
	 * Class mPress_Custom_Front_Page
	 */
	class mPress_Custom_Front_Page {

		private static $instance;

		/**
		 * Get or create instance of this class
		 *
		 * @return mPress_Custom_Front_Page
		 */
		public static function get_instance() {
			return isset( self::$instance ) ? self::$instance : new self();
		}

		/**
		 * Add our WordPress actions and filters
		 */
		private function __construct() {
			self::$instance = $this;
			if ( is_admin() ) {
				add_filter( 'wp_dropdown_pages', array( $this, 'wp_dropdown_pages' ) );
			} else {
				add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ) );
				add_action( 'template_redirect', array( $this, 'template_redirect' ) );
			}
		}

		/**
		 * This filter swaps out the normal dropdown for the front page with our own list
		 * of posts.
		 *
		 * @param $output
		 *
		 * @return string
		 */
		public function wp_dropdown_pages( $output ) {
			global $pagenow;
			if ( ('options-reading.php' === $pagenow || 'customize.php' === $pagenow) && preg_match( '#page_on_front#', $output ) ) {
				$output = $this->posts_dropdown();
			}

			return $output;
		}

		/**
		 * Generate a list of available posts to be used as the homepage
		 *
		 * @param string $post_type
		 *
		 * @return string $output
		 */
		protected function posts_dropdown( $post_type = 'any' ) {
			$output = '';
			if ( 'any' !== $post_type && ! post_type_exists( $post_type ) ) {
				$post_type = 'page';
			}
			$posts = get_posts(
				array(
					'posts_per_page' => - 1,
					'orderby'        => 'title',
					'order'          => 'ASC',
					'post_type'      => $post_type,
					'post_status'    => 'publish',
				)
			);
			$front_page_id = get_option( 'page_on_front' );
			$select = __( 'Select', 'mpress-custom-front-page' );
			$output .= '<select name="page_on_front" id="page_on_front">';
			$output .= "<option value=\"0\">&mdash; {$select} &mdash;</option>";
			foreach ( $posts as $post ) {
				$selected = selected( $front_page_id, $post->ID, false );
				$post_type_obj = get_post_type_object( $post->post_type );
				$output .= "<option value=\"{$post->ID}\"{$selected}>{$post->post_title} ({$post_type_obj->labels->singular_name})</option>";
			}
			$output .= '</select>';

			return $output;
		}

		/**
		 * A custom post type set as the homepage will still load under its original URL by default.
		 * This code ensures that it loads under the homepage URL.
		 *
		 * @param WP_Query $query
		 */
		public function pre_get_posts( $query ) {
			if ( $query->is_main_query() ) {
				$post_type = $query->get( 'post_type' );
				$page_id = $query->get( 'page_id' );
				if ( empty( $post_type ) && ! empty( $page_id ) ) {
					$query->set( 'post_type', get_post_type( $page_id ) );
				}
			}
		}

		/**
		 * If the front page is loaded under its original URL, do a 301 redirect to the homepage.
		 */
		public function template_redirect() {
			global $post;
			if ( is_singular() && ! is_front_page() && $post->ID == get_option( 'page_on_front' ) ) {
				wp_safe_redirect( site_url(), 301 );
			}
		}

	}

	mPress_Custom_Front_Page::get_instance();

}
