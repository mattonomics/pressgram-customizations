<?php
/*
Plugin Name: Pressgram Customizations
Version: 0.1-alpha
Description: Make @ mentions linked on singles and insert hashtags as tags and make them clickable.
Author: Matt Gross
Author URI: http://mattonomics.com
Plugin URI: https://github.com/mattonomics/pressgram-customizations
Text Domain: pressgram_customizations
Domain Path: /languages
*/

final class pressgram_customizations {
	
	private static $instance = false;
	
	private function __construct() {
		add_action( 'plugins_loaded', array( $this, 'textdomain' ) );
		add_action( 'init', array( $this, 'custom_taxonomy' ) );
		add_filter( 'the_title', array( $this, 'the_title' ) );
		if ( is_admin() ) {
			add_action( 'save_post', array( $this, 'save_post' ), 10, 3 );
		}
	}
	
	public static function create_instance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
	}
	
	public function the_title( $title ) {
		if ( in_the_loop() ) {
			$title = $this->clickable_hashtag( $this->clickable_username( $title ) );			
		}
		return $title;
	}
	
	private function clickable_username( $title ) {
		if ( !empty( $title ) && is_string( $title ) ) {
			preg_match_all( $this->get_username_regex(), $title, $matches );
			if ( is_array( $matches[0] ) ) {
				foreach ( $matches[0] as $username ) {
					$replacement = "<a target=\"_blank\" href=\"". esc_url( 'https://twitter.com/' . esc_attr( ltrim( $username, '@' ) ) ). "\">" . esc_attr( $username ) . "</a>";
					$title = preg_replace( "/$username/", $replacement, $title );
				}
			}
		}
		return $title;
	}
	
	private function clickable_hashtag( $title ) {
		if ( !empty( $title ) && is_string( $title ) ) {
			preg_match_all( $this->get_hashtag_regex(), $title, $matches );
			foreach ( $matches[0] as $hashtag ) {
				$lowercase = strtolower( ltrim( $hashtag, '#' ) );
				$term = get_term_by( 'slug', $lowercase, 'hashtag' );
				if ( $term && is_object( $term ) ) {
					$replacement = "<a href=\"". esc_url( get_term_link( $term->slug, 'hashtag' ) ). "\">" . esc_attr( $hashtag ) . "</a>";
					$title = preg_replace( "/$hashtag/", $replacement, $title );
				}
			}
		}
		return $title;
	}
	
	public function save_post( $post_ID, $post, $update ) {
		preg_match_all( $this->get_hashtag_regex(), $post->post_title, $matches );
		$terms_to_add = array();
		if ( !empty( $matches[0] ) ) {
			foreach ( $matches[0] as $hashtag ) {
				$lowercase = strtolower( ltrim( $hashtag, '#' ) );
				$term = get_term_by( 'slug', $lowercase, 'hashtag' );
				if ( $term == null ) {
					wp_insert_term( $lowercase, 'hashtag', array( 'slug' => $lowercase ) );
				}
				$terms_to_add[] = $lowercase;
			}
			wp_set_post_terms( $post_ID, implode( ',', $terms_to_add ), 'hashtag' );
		}
	}
	
	public function custom_taxonomy() {
		$labels = array(
				'name'                       => __( 'Hashtags', 'pressgram_customizations' ),
				'singular_name'              => __( 'Hashtag', 'pressgram_customizations' ),
				'search_items'               => __( 'Search Hashtags', 'pressgram_customizations' ),
				'popular_items'              => __( 'Popular Hashtags', 'pressgram_customizations' ),
				'all_items'                  => __( 'All Hashtags', 'pressgram_customizations' ),
				'parent_item'                => null,
				'parent_item_colon'          => null,
				'edit_item'                  => __( 'Edit Hashtag', 'pressgram_customizations' ),
				'update_item'                => __( 'Update Hashtag', 'pressgram_customizations' ),
				'add_new_item'               => __( 'Add New Hashtag', 'pressgram_customizations' ),
				'new_item_name'              => __( 'New Hashtag Name', 'pressgram_customizations' ),
				'separate_items_with_commas' => __( 'Separate hashtags with commas', 'pressgram_customizations' ),
				'add_or_remove_items'        => __( 'Add or remove hashtags', 'pressgram_customizations' ),
				'choose_from_most_used'      => __( 'Choose from the most used hashtags', 'pressgram_customizations' ),
				'not_found'                  => __( 'No hashtags found.', 'pressgram_customizations' ),
				'menu_name'                  => __( 'Hashtags', 'pressgram_customizations' ),
			);

			$args = array(
				'hierarchical'          => false,
				'labels'                => $labels,
				'show_ui'               => true,
				'show_admin_column'     => true,
//				'update_count_callback' => '_update_post_term_count',
				'query_var'             => true,
				'rewrite'               => array( 'slug' => 'hashtag' ),
			);

			register_taxonomy( 'hashtag', 'post', $args );
	}
	
	private function get_username_regex() {
		return '/@([a-zA-Z0-9_]{1,15})/';
	}
	
	private function get_hashtag_regex() {
		return '/\W*#\w\w+/';
	}
	
	public function textdomain() {
		load_plugin_textdomain( 'pressgram_customizations', false, trailingslashit( dirname( plugin_basename( __FILE__ ) ) ) . 'languages/' );
	}
}

pressgram_customizations::create_instance();