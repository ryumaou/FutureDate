<?php
/*
Plugin Name: Future Date
Plugin URI: https://jkhoffman.com/about/wordpress-projects/future-date/
Version: 1.0
License: GPL2
Description: Convert all dates to one of several scifi formats, including Terran Computational Calendar time, Warhammer 40K Imperial Date, and Classic Stardate, or projected into the future. 
Author: J. K. Hoffman
Author URI: https://jkhoffman.com
*/

// Security recommendation from wp.
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

// the options page etc
include( plugin_dir_path( __FILE__ ) . 'library/options.php' );
include( plugin_dir_path( __FILE__ ) . 'library/TCDate.class.php' );

// Actions
// For the permalinks
add_action( 'init', 'futuredate_init' );

//filters
futuredate_add_filters_actions_hooks();

// Functions
function futuredate_add_filters_actions_hooks() {
	/*
	 * Get add filters (and actions)
	 *
	 */
	// For the taxonomy
	add_filter( 'post_link', 'futuredate_permalink', 10, 3 );
	add_filter( 'post_type_link', 'futuredate_permalink', 10, 3 );

	if ( get_option( 'futuredate_override_get_date' ) == 1 ) {
		// Should get_date func. be filtered??
		add_filter( 'get_the_date', 'get_the_futuredate', 10, 3 );
	}

	if ( get_option( 'futuredate_override_time' ) == 1 ) {
		// Should the_time  be filtered??
		add_filter( 'the_time', 'the_futuredate', 10, 3 );
	}

	// On save, associate futuredate with post...
	add_action( 'save_post', 'futuredate_post', 10, 2 );

	// On rewrite update, associate futuredate with ALL posts...
	add_action( 'generate_rewrite_rules', 'futuredate_all_posts', 10, 0 );

	// Creates the futuredate shortcode
	add_shortcode( 'futuredate', 'futuredate_shortcode' );

	// When plugins is activated, it should set some sensible default values!!
	register_activation_hook( __FILE__, 'futuredate_activate' );

	// When plugin is deactivated, it should clean up after itself!!
	register_deactivation_hook( __FILE__, 'futuredate_deactivate' );

}

function futuredate_init() {
	if ( ! taxonomy_exists( 'futuredate' ) ) {
		register_taxonomy(
			'futuredate',
			'post',
			array(
				'hierarchical' => false,
				'label'        => __( 'Future Date' ),
				'public'       => true,
				'show_ui'      => false,
				'query_var'    => 'futuredate',
				'terms'        => 'futuredate',
				'rewrite'      => true
			)
		);
	}
}

function futuredate_permalink( $permalink, $post_id, $leavename ) {
	/**
	 * Manages the futuredate permalinks.
	 *
	 * @var string $permalink the permalink
	 * @var int $post_id the post_id
	 * @var string $leavename some leavename
	 */

	$post          = get_post( $post_id );
	$taxonomy_slug = "unknown";

	if ( ! $post ) {
		return $permalink;
	}
	if ( strpos( $permalink, '%futuredate%' ) === false ) {
		return $permalink;
	}

	$terms = wp_get_object_terms( $post->ID, 'futuredate' );

	if ( ! is_wp_error( $terms ) && ! empty( $terms ) && is_object( $terms[0] ) ) {
		$taxonomy_slug = $terms[0]->slug;
	} else {
		// Does this post have a date associated with it maybe?
		if ( $post->post_date ) {
			// if so generate the slug like this:
			$taxonomy_slug = sanitize_title_with_dashes(
				calculate_futuredate( mysql2date( 'c', $post->post_date ) ) );
		}
	}

	return str_replace( '%futuredate%', $taxonomy_slug, $permalink );
}

function futuredate_shortcode() {
	/**
	 * The futuredate shortcode
	 */
	return futuredate_now();
}

function the_futuredate( $the_date = "", $date_format = "", $post = null ) {
	/**
	 * echo the timestamp in one of the various formats with optional prefix. (like FutureDate)
	 * also sets or update the futuredate post_term
	 *
	 * @var string $the_date the date passed on from the filters section.
	 * @var string $date_format Date format sent from filters. not used.
	 * @var post $post The word press post to read date from
	 *
	 */
	echo get_the_futuredate( $the_date, $date_format, $post );
}

function get_the_futuredate( $the_date = "", $date_format = "", $post = null ) {
	/**
	 * Return the timestamp in the selected format with optional prefix. (like FutureDate)
	 * also sets or update the futuredate post_term
	 *
	 * @var string $the_date the date passed on from the filters section.
	 * @var string $date_format Date format sent from filters.
	 * @var post $post The word press post to read date from
	 */
	$post = get_post( $post );

	if ( ! $post ) {
		return $the_date;
	} elseif ( ! get_option( 'futuredate_override_only_when_kw_in_format' ) ) {
		if ( ! empty( $date_format ) ) {
			return $the_date;
		}
	} elseif ( ! get_option( 'futuredate_override_even_when_explicit_format' ) ) {
		// if someone has requested explicit formatting, then to not break things, don't filter unless explicitly configured
		if ( ! empty( $date_format ) ) {
			return $the_date;
		}
	}

	// Check if there is any futuredate to be found for post already...
	$terms = wp_get_post_terms( $post->ID, 'futuredate' );

	if ( is_wp_error( $terms ) || empty( $terms ) || empty( $terms[0]->name ) ) {
		// If not, associate one with the post
		futuredate_post( $post->ID, $post );

		// then try again....
		$terms    = wp_get_post_terms( $post->ID, 'futuredate' );
		$futuredate = $terms[0]->name;
	} else {
		$futuredate = $terms[0]->name;
	}

	return $futuredate;
}


function futuredate_now( $style = null ) {
	/**
	 * Get the current timestamp in FutureDate fmt.
	 */
	return calculate_futuredate( date( 'c' ), $style );
}

function calculate_futuredate( $date, $style = null ) {
	/**
	 * Uses logic suggested on trekguide (http://trekguide.com/Stardates.htm)
	 * or wikipedia which does not seem to 100% agree to translate date
	 *
	 * @param date $date date to translate to futuredate.
	 * @param string $style the style. will get from options unless set.
	 *
	 * @return string futuredate
	 */


	if ( empty( $style ) ) {
		$style = get_option( 'futuredate_style' );
	}
	$prefix = get_option( 'futuredate_prefix' );
	$adjustment = get_option( 'futuredate_adjustment');
	$fortyk_check = get_option( 'futuredate_fortyk_check');

	// TODO: add TNG

	if ( $style == "OrdinalDate" ) {
		/*  This just adds the future date adjustment and displays the date stamp in an unsual format
		 */
		// $futuredate = mysql2date( "Y.z", $date );
    $Ordinalyy       = (int) mysql2date( 'Y', $date ) + $adjustment;
		$OrdinalDOY      = mysql2date( 'z', $date );
		$OrdinalHour     = mysql2date( 'H', $date );
		$OrdinalMinute   = mysql2date( 'i', $date );
		$OrdinalSeconds  = mysql2date( 's', $date );
		$futuredate = sprintf( "%s.%s-%s:%s:%s", $Ordinalyy, $OrdinalDOY, $OrdinalHour, $OrdinalMinute, $OrdinalSeconds );
	} elseif ( $style == 'TC' ) {
		/*
		 * This is, essentially, the Terran Computational Calendar date stamp with the addition of the future date adjustment
		*/
		$Iyy       = mysql2date( "Y", $date ) + $adjustment;
		$Imm       = mysql2date( 'm', $date );
		$My_Imperial_Now = sprintf( "%s-%s-%02s %s:%s:%s", $Iyy, $Imm, mysql2date( "d", $date ), mysql2date("H", $date), mysql2date("i",$date), mysql2date("s",$date));
		$My_Imperial_Date = tcdate($My_Imperial_Now);
		$futuredate = $My_Imperial_Date['padded_date'];
	} elseif ( $style == '40KFuture' ) {
		/*
		 * The future adjusted time in the Warhammer 40K format as described at https://warhammer40k.fandom.com/wiki/Imperial_Dating_System
		*/
		if ( $fortyk_check == "Zero" ){
			$FortyKCheck = '0';
		} elseif ( $fortyk_check == "One" ){
			$FortyKCheck = '1';
		} elseif ( $fortyk_check == "Two" ){
			$FortyKCheck = '2';
		} elseif ( $fortyk_check == "Three" ){
			$FortyKCheck = '3';
		} elseif ( $fortyk_check == "Four" ){
			$FortyKCheck = '4';
		} elseif ( $fortyk_check == "Five" ){
			$FortyKCheck = '5';
		} elseif ( $fortyk_check == "Six" ){
			$FortyKCheck = '6';
		} elseif ( $fortyk_check == "Seven" ){
			$FortyKCheck = '7';
		} elseif ( $fortyk_check == "Eight" ){
			$FortyKCheck = '8';
		} else {
			$FortyKCheck = '9';
		} 
		$FortyKFutureyy       = mysql2date( 'Y', $date ) + $adjustment;
		$FortyKFuturemm       = mysql2date( 'm', $date );
		$FortyKFuture_Hour    = mysql2date( 'H', $date );
		$FortyKFuture_DOY     = mysql2date( 'z', $date );
		$FortyKFuture_Millenia = floor(($FortyKFutureyy/1000)+1);
		$FortyKFuture_FracYear = floor((($FortyKFuture_DOY*24) + $FortyKFuture_Hour)*0.11407955);
		$FortyKFuture_Year     = substr($FortyKFutureyy, -3);
		$My_FortyK_Future = sprintf( "%s.%s.%s.M%s", $FortyKCheck, $FortyKFuture_FracYear, $FortyKFuture_Year, $FortyKFuture_Millenia);
		$futuredate = $My_FortyK_Future;
	} else {
		/* Represent the current date in YYMM.DD format, where "YY" is the current year minus 1900,
		 * MM is the current month (01-12), and DD is the current day of the month (01-31).
		*/
		$yy       = (int) mysql2date( 'Y', $date ) - 1900 + $adjustment;
		$mm       = mysql2date( 'm', $date );
		$futuredate = sprintf( "%s%s.%02s", $yy, $mm, mysql2date( "d", $date ) );
	}

	return implode( ' ', array( $prefix, $futuredate ) );
}

function futuredate_post( $post_id, $post ) {
	/**
	 * Associates futuredate with the post
	 *
	 * @param int $post_id The post ID.
	 * @param post $post The post object.
	 *
	 * @return mixed what ever was result of wp_set_post_terms
	 */
	if ( $post->post_date ) {
		$futuredate = calculate_futuredate( mysql2date( 'c', $post->post_date ) );

		return wp_set_post_terms( $post_id, $futuredate, 'futuredate', false );
	}
}

function unfuturedate_post( $post_id, $post = null ) {
	/**
	 * Unasociate futuredate with the post
	 *
	 * @param int $post_id The post ID
	 * @param post $post The post object
	 */

	wp_delete_object_term_relationships( $post_id, 'futuredate' );
}

function unfuturedate_all_posts() {
	/**
	 * Unassociates futuredates with *ALL* posts
	 *
	 */

	foreach ( get_posts() as $post ) {
		if ( $post->ID ) {
			unfuturedate_post( $post->ID );
		}
	}
}

function futuredate_all_posts() {
	/**
	 * Associates and recalculates futuredate with *ALL* posts
	 *
	 * Useful when there've been a change in what futuredate format to display.
	 * Or when first activating this plugin, for the rewrites to work.
	 *
	 * Returns a array with two arrays in it. First element of nested array are
	 * post->ID:s of successful updates. The second one are the ID:s of failed ones.
	 */
	$failures  = array();
	$successes = array();

	foreach ( get_posts() as $post ) {
		if ( $post->ID && $post->post_date ) {
			$r = futuredate_post( $post->ID, $post );
			if ( is_wp_error( $r ) || empty( $r ) ) {
				array_push( $failures, $post->ID );
			} else {
				array_push( $successes, $post->ID );
			}
		}
	}

	return array( $successes, $failures );
}

function futuredate_activate() {
	/**
	 * Activation hook, set default values for params.
	 *
	 */
	add_option( 'futuredate_prefix', 'Future Date' );
	add_option( 'futuredate_adjustment', '0' );
	add_option( 'futuredate_style', 'Classic' );
	add_option( 'futuredate_fortyk_check', 'Zero');

}

function futuredate_deactivate() {
	/**
	 * Remove all stuff added by this hook from the posts, settings etc
	 *
	 */

	if ( ! is_admin() ) {
		return;
	}

	delete_option( 'futuredate_prefix' );
	delete_option( 'futuredate_adjustment');
	delete_option( 'futuredate_style' );
	delete_option( 'futuredate_fortyk_check');
	delete_option( 'futuredate_override_date' );
	delete_option( 'futuredate_override_get_date' );
	delete_option( 'futuredate_override_time' );
	delete_option( 'futuredate_override_even_when_explicit_format' );


	unfuturedate_all_posts();

	$terms = get_terms( 'futuredate', array( 'fields' => 'ids', 'hide_empty' => false ) );
	foreach ( $terms as $value ) {
		wp_delete_term( $value, 'futuredate' );
	}

}

?>
