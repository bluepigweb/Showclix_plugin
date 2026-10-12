<?php
/**
 * One-time sync helper — run once via browser while logged in as admin, then delete this file.
 * Clears all stale state/transients and immediately runs a full sync.
 */

$wp_load = dirname( __FILE__, 5 ) . '/wp-load.php';
if ( ! file_exists( $wp_load ) ) {
    die( 'wp-load.php not found.' );
}
require_once $wp_load;

if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( 'Unauthorized. Please <a href="' . wp_login_url( $_SERVER['REQUEST_URI'] ) . '">log in as admin</a> first.' );
}

// ── 1. Clear stale state & transients ──────────────────────────────────────

$cleared = array();

if ( delete_option( 'gotham_showclix_state' ) ) {
    $cleared[] = 'Dedupe state (gotham_showclix_state)';
}

if ( delete_transient( 'gotham_showclix_events_master_v2' ) ) {
    $cleared[] = 'Events master transient';
}

global $wpdb;
$stale = $wpdb->get_col(
    "SELECT option_name FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_gotham_showclix_series_children_%'
        OR option_name LIKE '_transient_timeout_gotham_showclix_series_children_%'"
);
foreach ( $stale as $opt ) {
    delete_option( $opt );
}
if ( $stale ) {
    $cleared[] = count( $stale ) . ' series children transients';
}

// ── 2. Run a fresh full sync ───────────────────────────────────────────────

set_time_limit( 300 ); // allow up to 5 minutes for large syncs

$sync   = new Gotham_Showclix_Sync_Manager();
$result = $sync->run_sync();

// ── 3. Report ─────────────────────────────────────────────────────────────

$db      = new Gotham_Showclix_DB_Manager();
$events  = $db->get_events();
$db_count = count( $events );
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Gotham Showclix — Force Sync</title>
<style>
    body { font-family: monospace; background: #1d2327; color: #f0f0f1; padding: 30px; }
    h2 { color: #00b9eb; }
    .ok  { color: #00e676; }
    .err { color: #ff5252; }
    pre  { background: #101417; padding: 15px; border-radius: 4px; overflow-x: auto; }
    a    { color: #00b9eb; }
    table { border-collapse: collapse; width: 100%; margin-top: 20px; }
    th, td { border: 1px solid #3c434a; padding: 8px 12px; text-align: left; }
    th { background: #2c3338; }
</style>
</head>
<body>

<h2>Gotham Showclix — Force Sync Results</h2>

<h3>1. Cleared</h3>
<ul>
<?php foreach ( $cleared as $c ) : ?>
    <li class="ok">✓ <?php echo esc_html( $c ); ?></li>
<?php endforeach; ?>
<?php if ( empty( $cleared ) ) : ?>
    <li>Nothing to clear (already fresh)</li>
<?php endif; ?>
</ul>

<h3>2. Sync Result</h3>
<?php if ( $result !== false ) : ?>
    <p class="ok">✓ Sync completed. Upserted: <strong><?php echo (int) $result; ?></strong> events.</p>
<?php else : ?>
    <p class="err">✗ Sync failed — check API token and seller ID in settings.</p>
<?php endif; ?>

<h3>3. Database — <?php echo $db_count; ?> upcoming events stored</h3>
<table>
<tr><th>ID</th><th>Title</th><th>Date</th><th>Slug</th></tr>
<?php foreach ( $events as $ev ) : ?>
<tr>
    <td><?php echo esc_html( $ev->event_id ); ?></td>
    <td><?php echo esc_html( $ev->title ); ?></td>
    <td><?php echo esc_html( $ev->event_date ); ?></td>
    <td><?php echo esc_html( $ev->slug ); ?></td>
</tr>
<?php endforeach; ?>
</table>

<h3>4. Sync Logs</h3>
<pre><?php
$logs = get_option( 'gotham_showclix_sync_logs', array() );
echo esc_html( implode( "\n", array_slice( $logs, 0, 30 ) ) );
?></pre>

<p><a href="<?php echo esc_url( admin_url( 'admin.php?page=gotham-showclix-settings' ) ); ?>">→ Go to plugin settings</a></p>
<p><strong style="color:#ff5252">⚠ Delete this file after use:</strong> <code>/wp-content/plugins/gotham-showclix/clear-state.php</code></p>

</body>
</html>
