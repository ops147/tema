<?php
/**
 * Uninstall — removes plugin options.
 *
 * Imported pages and Media Library uploads are intentionally kept: deleting
 * them would break the site content the user built from the templates.
 *
 * @package ARC_Starter_Templates
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

delete_option( 'arc_st_page_map' );
delete_option( 'arc_st_media_map' );
delete_option( 'arc_st_import_state' );
delete_option( 'arc_st_last_import' );
delete_option( 'arc_st_contact_email' );
delete_option( 'arc_st_consent_text' );
delete_transient( 'arc_st_import_lock' );
delete_transient( 'arc_st_activated' );
