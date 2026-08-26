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
 * Resolve the author, and never hand Timber a falsy id.
 *
 * Two bugs lived here. When an /author/<slug>/ request names someone who is
 * not a real user, WordPress leaves the `author` query var falsy (observed as
 * boolean false, not 0) and silently drops the constraint, so the query
 * returns every post.
 *
 *   1. Timber::get_user( false ) returns NULL for an anonymous visitor, and
 *      the old code called ->name() on it — an uncaught Error, HTTP 500.
 *      Anonymous is the case that matters: it is what a scanner walking author
 *      slugs hits, so every miss burned a PHP worker and returned a response
 *      distinguishable from a real 404.
 *
 *   2. Timber::get_user( false ) returns the CURRENT USER when someone is
 *      logged in. So the same URL that fatalled for the public rendered
 *      "Author Archives: <your own name>" for an administrator — a page for a
 *      user that does not exist, titled after whoever happened to be viewing.
 *      This is also why the fatal was easy to miss while testing logged in.
 *
 * Casting to int and requiring > 0 kills both: a falsy query var can no longer
 * reach Timber, so the lookup either finds a real user or we fall through to
 * the 404 below, identically for every viewer.
 */
$author_id = (int) ( $wp_query->query_vars['author'] ?? 0 );
$author    = $author_id > 0 ? Timber::get_user( $author_id ) : false;

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
