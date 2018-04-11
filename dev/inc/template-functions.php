<?php
/**
 * Functions which enhance the theme by hooking into WordPress
 *
 * @package WPAMPTheme
 */

/**
 * Adds custom classes to the array of body classes.
 *
 * @param array $classes Classes for the body element.
 * @return array
 */
function travel_body_classes( $classes ) {
	// Adds a class of hfeed to non-singular pages.
	if ( ! is_singular() ) {
		$classes[] = 'hfeed';
	}

	if ( is_active_sidebar( 'sidebar-1' ) ) {
		global $template;
		if ( 'front-page.php' !== basename( $template ) ) {
			$classes[] = 'has-sidebar';
		}
	}

	return $classes;
}
add_filter( 'body_class', 'travel_body_classes' );

/**
 * Add a pingback url auto-discovery header for singularly identifiable articles.
 */
function travel_pingback_header() {
	if ( is_singular() && pings_open() ) {
		echo '<link rel="pingback" href="', esc_url( get_bloginfo( 'pingback_url' ) ), '">';
	}
}
add_action( 'wp_head', 'travel_pingback_header' );

/**
 * Adds async/defer attributes to enqueued / registered scripts.
 *
 * If #12009 lands in WordPress, this function can no-op since it would be handled in core.
 *
 * @link https://core.trac.wordpress.org/ticket/12009
 * @param string $tag    The script tag.
 * @param string $handle The script handle.
 * @return array
 */
function travel_filter_script_loader_tag( $tag, $handle ) {

	foreach ( array( 'async', 'defer' ) as $attr ) {
		if ( ! wp_scripts()->get_data( $handle, $attr ) ) {
			continue;
		}

		// Prevent adding attribute when already added in #12009.
		if ( ! preg_match( ":\s$attr(=|>|\s):", $tag ) ) {
			$tag = preg_replace( ':(?=></script>):', " $attr", $tag, 1 );
		}

		// Only allow async or defer, not both.
		break;
	}

	return $tag;
}

add_filter( 'script_loader_tag', 'travel_filter_script_loader_tag', 10, 2 );

/**
 * Generate stylesheet URI.
 *
 * @param object $wp_styles Registered styles.
 * @param string $handle The style handle.
 */
function travel_get_preload_stylesheet_uri( $wp_styles, $handle ) {
	$preload_uri = $wp_styles->registered[ $handle ]->src . '?ver=' . $wp_styles->registered[ $handle ]->ver;
	return $preload_uri;
}

/**
 * Adds preload for in-body stylesheets depending on what templates are being used.
 *
 * @link https://developer.mozilla.org/en-US/docs/Web/HTML/Preloading_content
 */
function travel_add_body_style() {

	// Get the current template global.
	global $template;

	// Get registered styles.
	$wp_styles = wp_styles();
	// var_dump($wp_styles->registered['travel-sidebar']);

	$prelods = array();

	// Preload content.css.
	$preloads['travel-content'] = travel_get_preload_stylesheet_uri( $wp_styles, 'travel-content' );

	// Preload sidebar.css and widget.css.
	if ( is_active_sidebar( 'sidebar-1' ) ) {
		$preloads['travel-sidebar'] = travel_get_preload_stylesheet_uri( $wp_styles, 'travel-sidebar' );
		$preloads['travel-widgets'] = travel_get_preload_stylesheet_uri( $wp_styles, 'travel-widgets' );
	}

	// Preload comments.css.
	if ( ! post_password_required() && is_singular() && ( comments_open() || get_comments_number() ) ) {
		$preloads['travel-comments'] = travel_get_preload_stylesheet_uri( $wp_styles, 'travel-comments' );
	}

	// Preload front-page.css.
	if ( 'front-page.php' === basename( $template ) ) {
		$preloads['travel-front-page'] = travel_get_preload_stylesheet_uri( $wp_styles, 'travel-front-page' );
	}

	// Output the preload markup in <head>.
	foreach ( $preloads as $handle => $src ) {
		echo '<link rel="preload" id="' . esc_attr( $handle ) . '-preload" href="' . esc_url( $src ) . '" as="style" />';
		echo "\n";
	}

}
add_action( 'wp_head', 'travel_add_body_style' );