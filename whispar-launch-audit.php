<?php
/**
 * Plugin Name: Whispar Launch Audit
 * Description: Read-only pre-launch checks for WordPress websites.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Whispar Design
 * Author URI: https://whispardesign.com/
 * License: GPL-2.0-or-later
 * Text Domain: whispar-launch-audit
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register a read-only report under Tools > Launch Audit.
 */
function whispar_launch_audit_register_page() {
    add_management_page(
        __( 'Launch Audit', 'whispar-launch-audit' ),
        __( 'Launch Audit', 'whispar-launch-audit' ),
        'manage_options',
        'whispar-launch-audit',
        'whispar_launch_audit_render_page'
    );
}
add_action( 'admin_menu', 'whispar_launch_audit_register_page' );

/**
 * Collect launch checks without changing site settings or making remote requests.
 *
 * Each check is for review, not a certification of security or production readiness.
 *
 * @return array<int, array{label: string, status: string, details: string}>
 */
function whispar_launch_audit_collect_checks() {
    $checks = array();
    $home_scheme = wp_parse_url( home_url( '/' ), PHP_URL_SCHEME );
    $site_scheme = wp_parse_url( site_url( '/' ), PHP_URL_SCHEME );

    $checks[] = array(
        'label'   => __( 'Public home URL', 'whispar-launch-audit' ),
        'status'  => 'https' === strtolower( (string) $home_scheme ) ? 'pass' : 'review',
        'details' => 'https' === strtolower( (string) $home_scheme )
            ? __( 'Home URL uses HTTPS.', 'whispar-launch-audit' )
            : __( 'Confirm HTTPS is enabled before launch.', 'whispar-launch-audit' ),
    );

    $checks[] = array(
        'label'   => __( 'WordPress installation URL', 'whispar-launch-audit' ),
        'status'  => 'https' === strtolower( (string) $site_scheme ) ? 'pass' : 'review',
        'details' => 'https' === strtolower( (string) $site_scheme )
            ? __( 'WordPress URL uses HTTPS.', 'whispar-launch-audit' )
            : __( 'Confirm the WordPress address uses HTTPS.', 'whispar-launch-audit' ),
    );

    $is_indexable = '1' === (string) get_option( 'blog_public', '1' );
    $checks[] = array(
        'label'   => __( 'Search engine visibility', 'whispar-launch-audit' ),
        'status'  => $is_indexable ? 'pass' : 'review',
        'details' => $is_indexable
            ? __( 'The WordPress search engine visibility setting allows indexing.', 'whispar-launch-audit' )
            : __( 'WordPress requests that search engines avoid indexing. This may be intentional on staging.', 'whispar-launch-audit' ),
    );

    $has_pretty_permalinks = '' !== (string) get_option( 'permalink_structure', '' );
    $checks[] = array(
        'label'   => __( 'Permalink structure', 'whispar-launch-audit' ),
        'status'  => $has_pretty_permalinks ? 'pass' : 'review',
        'details' => $has_pretty_permalinks
            ? __( 'Pretty permalinks are enabled.', 'whispar-launch-audit' )
            : __( 'Plain permalinks are configured. Review Settings > Permalinks.', 'whispar-launch-audit' ),
    );

    $site_title = trim( (string) get_option( 'blogname', '' ) );
    $checks[] = array(
        'label'   => __( 'Site title', 'whispar-launch-audit' ),
        'status'  => '' !== $site_title ? 'pass' : 'review',
        'details' => '' !== $site_title
            ? __( 'A site title is configured.', 'whispar-launch-audit' )
            : __( 'The site title is empty. Review Settings > General.', 'whispar-launch-audit' ),
    );

    $tagline = trim( (string) get_option( 'blogdescription', '' ) );
    $placeholder_tagline = 'just another wordpress site' === strtolower( $tagline );
    $checks[] = array(
        'label'   => __( 'Default tagline', 'whispar-launch-audit' ),
        'status'  => $placeholder_tagline ? 'review' : 'pass',
        'details' => $placeholder_tagline
            ? __( 'Replace the default WordPress tagline.', 'whispar-launch-audit' )
            : __( 'The default WordPress tagline was not detected. An empty tagline can be intentional.', 'whispar-launch-audit' ),
    );

    $debugging = defined( 'WP_DEBUG' ) && WP_DEBUG;
    $checks[] = array(
        'label'   => __( 'Debug mode', 'whispar-launch-audit' ),
        'status'  => $debugging ? 'review' : 'pass',
        'details' => $debugging
            ? __( 'WP_DEBUG is enabled. Review production logging and display settings before launch.', 'whispar-launch-audit' )
            : __( 'WP_DEBUG is not enabled.', 'whispar-launch-audit' ),
    );

    $updates = get_site_transient( 'update_plugins' );
    $pending = is_object( $updates ) && isset( $updates->response ) && is_array( $updates->response )
        ? count( $updates->response )
        : null;

    $checks[] = array(
        'label'   => __( 'Plugin updates', 'whispar-launch-audit' ),
        'status'  => null === $pending || $pending > 0 ? 'review' : 'pass',
        'details' => null === $pending
            ? __( 'Cached plugin update information is unavailable. Check Dashboard > Updates.', 'whispar-launch-audit' )
            : ( $pending > 0
                ? sprintf(
                    /* translators: %d is the number of pending plugin updates in the current cache. */
                    _n( '%d plugin update appears pending.', '%d plugin updates appear pending.', $pending, 'whispar-launch-audit' ),
                    $pending
                )
                : __( 'No plugin updates appear pending in the current cache.', 'whispar-launch-audit' ) ),
    );

    return $checks;
}

/**
 * Render a restricted, escaped report in the WordPress administration area.
 */
function whispar_launch_audit_render_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( esc_html__( 'You do not have permission to view this report.', 'whispar-launch-audit' ) );
    }

    $checks = whispar_launch_audit_collect_checks();
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__( 'WordPress Launch Audit', 'whispar-launch-audit' ); ?></h1>
        <p><?php echo esc_html__( 'A read-only launch checklist. Review findings in the context of your site, especially on staging environments.', 'whispar-launch-audit' ); ?></p>
        <table class="widefat striped">
            <thead>
                <tr>
                    <th scope="col"><?php echo esc_html__( 'Check', 'whispar-launch-audit' ); ?></th>
                    <th scope="col"><?php echo esc_html__( 'Result', 'whispar-launch-audit' ); ?></th>
                    <th scope="col"><?php echo esc_html__( 'Details', 'whispar-launch-audit' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $checks as $check ) : ?>
                    <tr>
                        <td><?php echo esc_html( $check['label'] ); ?></td>
                        <td>
                            <strong><?php echo esc_html( 'pass' === $check['status'] ? __( 'Pass', 'whispar-launch-audit' ) : __( 'Review', 'whispar-launch-audit' ) ); ?></strong>
                        </td>
                        <td><?php echo esc_html( $check['details'] ); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><em><?php echo esc_html__( 'This checklist does not replace backups, accessibility testing, security review, browser QA, or a full SEO audit.', 'whispar-launch-audit' ); ?></em></p>
    </div>
    <?php
}
