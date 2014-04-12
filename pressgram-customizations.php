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
	
	static $instance = false;
	
	private function __construct() {
		add_filter( 'the_title', array( $this, 'the_title' ) );
		add_action( 'init', array( $this, 'custom_taxonomy' ) );
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
		// make @ mentions and hastags clickable
		if ( in_the_loop() ) {
			$title = $this->clickable_hashtag( $this->clickable_username( $title ) );			
		}
		return $title;
	}
	
	private function clickable_username( $title ) {
		if ( !empty( $title ) && is_string( $title ) ) {
			preg_match_all( '/@([a-zA-Z0-9]{1,15})/', $title, $matches );
			if ( is_array( $matches[0] ) ) {
				foreach ( $matches[0] as $username ) {
					$replacement = "<a href=\"". trailingslashit( set_url_scheme( 'http://twitter.com' ) ). ltrim( $username, '@' ). "\">$username</a>";
					$title = preg_replace( "/$username/", $replacement, $title );
				}
			}
		}
		return $title;
	}
	
	private function clickable_hashtag( $title ) {
		if ( !empty( $title ) && is_string( $title ) ) {
			preg_match_all( '/(?<=\s)#\w\w+/', $title, $matches );
			foreach ( $matches[0] as $hashtag ) {
				$lowercase = strtolower( ltrim( $hashtag, '#' ) );
				$term = get_term_by( 'slug', $lowercase, 'hashtag' );
				if ( $term ) {
					$replacement = "<a href=\"". esc_url( get_term_link( $term->slug, 'hashtag' ) ). "\">$hashtag</a>";
					$title = preg_replace( "/$hashtag/", $replacement, $title );
				}
			}
		}
		return $title;
	}
	
	public function custom_taxonomy() {
		$labels = array(
				'name'                       => 'Hashtags',
				'singular_name'              => 'Hashtag',
				'search_items'               => 'Search Hashtags',
				'popular_items'              => 'Popular Hashtags',
				'all_items'                  => 'All Hashtags',
				'parent_item'                => null,
				'parent_item_colon'          => null,
				'edit_item'                  => 'Edit Hashtag',
				'update_item'                => 'Update Hashtag',
				'add_new_item'               => 'Add New Hashtag',
				'new_item_name'              => 'New Hashtag Name',
				'separate_items_with_commas' => 'Separate hashtags with commas',
				'add_or_remove_items'        => 'Add or remove hashtags',
				'choose_from_most_used'      => 'Choose from the most used hashtags',
				'not_found'                  => 'No hashtags found.',
				'menu_name'                  => 'Hashtags',
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
	
	public function save_post( $post_ID, $post, $update ) {
		preg_match_all( '/(?<=\s)#\w\w+/', $post->post_title, $matches );
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
}

pressgram_customizations::create_instance();