<?php
// Minimal smoke test for the checks. It does not replace testing in WordPress.
define( 'ABSPATH', __DIR__ . '/' );
$GLOBALS['test_options'] = array();
$GLOBALS['test_home_url'] = '';
$GLOBALS['test_site_url'] = '';
$GLOBALS['test_updates'] = null;
function add_action( $name, $callback ) {}
function __( $text, $domain = null ) { return $text; }
function _n( $single, $plural, $number, $domain = null ) { return 1 === $number ? $single : $plural; }
function wp_parse_url( $url, $component ) { return parse_url( $url, $component ); }
function home_url( $path = '' ) { return $GLOBALS['test_home_url'] . $path; }
function site_url( $path = '' ) { return $GLOBALS['test_site_url'] . $path; }
function get_option( $name, $default = '' ) { return $GLOBALS['test_options'][ $name ] ?? $default; }
function get_site_transient( $name ) { return $GLOBALS['test_updates']; }

require dirname( __DIR__ ) . '/whispar-launch-audit.php';

function assert_statuses( $expected, $context ) {
    $actual = array_column( whispar_launch_audit_collect_checks(), 'status' );
    if ( $expected !== $actual ) {
        fwrite( STDERR, "FAIL: $context\nExpected: " . json_encode( $expected ) . "\nActual: " . json_encode( $actual ) . "\n" );
        exit( 1 );
    }
    echo "PASS: $context\n";
}

$GLOBALS['test_home_url'] = 'https://example.test';
$GLOBALS['test_site_url'] = 'https://example.test/wp';
$GLOBALS['test_options'] = array(
    'blog_public' => '1',
    'permalink_structure' => '/%postname%/',
    'blogname' => 'Sample Site',
    'blogdescription' => 'A useful tagline',
);
$GLOBALS['test_updates'] = (object) array( 'response' => array() );
assert_statuses( array_fill( 0, 8, 'pass' ), 'Nominal public site' );

$GLOBALS['test_home_url'] = 'http://example.test';
$GLOBALS['test_site_url'] = 'http://example.test/wp';
$GLOBALS['test_options'] = array(
    'blog_public' => '0',
    'permalink_structure' => '',
    'blogname' => '',
    'blogdescription' => 'Just another WordPress site',
);
$GLOBALS['test_updates'] = (object) array( 'response' => array( 'example/plugin.php' => (object) array() ) );
assert_statuses( array( 'review', 'review', 'review', 'review', 'review', 'review', 'pass', 'review' ), 'Staging-like site and pending plugin updates' );
