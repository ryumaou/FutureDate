<?php
/**
 * Server-side rendering of the `post-futuredate` block.
 *
 * @package WordPress
 */

/**
 * Renders the `post-futuredate` block on the server.
 *
 * @param array    $attributes Block attributes.
 * @param string   $content    Block default content.
 * @param WP_Block $block      Block instance.
 * @return string Returns the filtered post date for the current post wrapped inside "time" tags.
 */
function render_block_core_post_futuredate( $attributes, $content, $block ) {
	if ( ! isset( $block->context['postId'] ) ) {
		return '';
	}

	$post_ID            = $block->context['postId'];
	$align_class_name   = empty( $attributes['textAlign'] ) ? '' : "has-text-align-{$attributes['textAlign']}";
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $align_class_name ) );
	$formatted_date     = get_the_date( empty( $attributes['format'] ) ? '' : $attributes['format'], $post_ID );
	if ( isset( $attributes['isLink'] ) && $attributes['isLink'] ) {
		$formatted_date = sprintf( '<a href="%1s">%2s</a>', get_the_permalink( $post_ID ), $formatted_date );
	}

	return sprintf(
		'<div %1$s><time datetime="%2$s">%3$s</time></div>',
		$wrapper_attributes,
		esc_attr( get_the_date( 'c', $post_ID ) ),
		$formatted_date
	);
}

/**
 * Registers the `core/post-futuredate` block on the server.
 */
function register_block_core_post_futuredate() {
	register_block_type_from_metadata(
		__DIR__ . '/post-futuredate',
		array(
			'render_callback' => 'render_block_core_post_futuredate',
		)
	);
}
add_action( 'init', 'register_block_core_post_futuredate' );
