<?php
/**
 * The template for displaying Author Archive pages
 *
 * Methods for TimberHelper can be found in the /lib sub-directory
 *
 * @package  IX
 */

global $wp_query;

$context          = Timber::context();
$context['posts'] = Timber::get_posts();

/*
 * Guard the user lookup. Timber::get_user() returns false for an id that does
 * not resolve, and calling ->name() on that is a fatal — which is what this
 * template used to do.
 *
 * It is reachable without a bad URL. WordPress sets the `author` query var to
 * 0 when an /author/<slug>/ request names someone who does not exist, so any
 * scanner walking author slugs turned every miss into a 500. Verified on
 * vincentragosta.io 2026-08-26: /author/<real>/ rendered, /author/<anything
 * else>/ was an uncaught Error out of line 17.
 *
 * A 500 is worse than the 404 it should have been in two ways: it is an
 * information leak (a distinguishable response for a name that does not
 * exist), and it burns PHP workers on traffic that should be cheap.
 */
$author = false;
if ( isset( $wp_query->query_vars['author'] ) ) {
	$author = Timber::get_user( $wp_query->query_vars['author'] );
}

if ( $author ) {
	$context['author'] = $author;
	$context['title']  = 'Author Archives: ' . $author->name();
} else {
	/*
	 * No such author. Render the 404 rather than an empty archive, so the
	 * response matches what a missing resource should be.
	 *
	 * Note this template is only reached when a site has opted OUT of
	 * DisableAuthorArchives; with the Feature on (the default since v1.7.0)
	 * the request never gets this far.
	 */
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
	Timber::render( array( '404.twig' ), $context );
	return;
}

Timber::render( array( 'author.twig', 'archive.twig' ), $context );
