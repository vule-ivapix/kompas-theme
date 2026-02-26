/* global wp, kompasPublishGuard */
/**
 * Publish guard: prevents publishing a post without a featured image and a
 * non-default category. Shows an error notice and locks saving when conditions
 * are not met.
 */
( function () {
	'use strict';

	if ( ! wp || ! wp.data ) {
		return;
	}

	var subscribe        = wp.data.subscribe;
	var select           = wp.data.select;
	var dispatch         = wp.data.dispatch;
	var LOCK_KEY         = 'kompas-publish-guard';
	var NOTICE_ID        = 'kompas-publish-guard';
	var uncategorizedId  = ( window.kompasPublishGuard || {} ).uncategorizedId || 1;

	// Track previous error string to avoid re-dispatching on every store change.
	var prevIssues = undefined; // eslint-disable-line no-undefined

	subscribe( function () {
		var editor = select( 'core/editor' );
		if ( ! editor ) {
			return;
		}

		var status = editor.getEditedPostAttribute( 'status' );

		// Only guard when publishing or scheduling; drafts / pending are fine.
		if ( 'publish' !== status && 'future' !== status ) {
			if ( prevIssues !== null ) {
				prevIssues = null;
				dispatch( 'core/editor' ).unlockPostSaving( LOCK_KEY );
				dispatch( 'core/notices' ).removeNotice( NOTICE_ID );
			}
			return;
		}

		var featuredImageId = editor.getEditedPostAttribute( 'featured_media' );
		var categories      = editor.getEditedPostAttribute( 'categories' ) || [];
		var realCategories  = categories.filter( function ( id ) {
			return id !== uncategorizedId;
		} );

		var errors = [];
		if ( ! featuredImageId ) {
			errors.push( 'naslovna slika nije postavljena' );
		}
		if ( realCategories.length === 0 ) {
			errors.push( 'kategorija nije izabrana' );
		}

		var issues = errors.length > 0 ? errors.join( ', ' ) : null;

		// Bail early if nothing changed (prevents infinite re-renders).
		if ( issues === prevIssues ) {
			return;
		}
		prevIssues = issues;

		if ( issues ) {
			dispatch( 'core/editor' ).lockPostSaving( LOCK_KEY );
			dispatch( 'core/notices' ).createErrorNotice(
				'Vest ne može biti objavljena: ' + issues + '.',
				{ id: NOTICE_ID, isDismissible: true }
			);
		} else {
			dispatch( 'core/editor' ).unlockPostSaving( LOCK_KEY );
			dispatch( 'core/notices' ).removeNotice( NOTICE_ID );
		}
	} );
} )();
