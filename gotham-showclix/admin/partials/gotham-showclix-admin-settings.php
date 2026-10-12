<?php
/**
 * Settings display
 *
 * @package    Gotham_Showclix
 * @subpackage Gotham_Showclix/admin/partials
 */
?>
<div class="wrap">
    <h1>Gotham Showclix Settings</h1>
    
    <?php if (isset($_GET['sync']) && $_GET['sync'] === 'success'): ?>
        <div class="updated notice is-dismissible"><p>Sync successful! Updated <?php echo intval($_GET['count']); ?> events.</p></div>
    <?php elseif (isset($_GET['sync']) && $_GET['sync'] === 'failed'): ?>
        <div class="error notice is-dismissible"><p>Sync failed. Please check your API credentials.</p></div>
    <?php elseif (isset($_GET['delete_all']) && $_GET['delete_all'] === 'success'): ?>
        <div class="updated notice is-dismissible"><p>All events have been successfully deleted and state reset.</p></div>
    <?php elseif (isset($_GET['reconcile']) && $_GET['reconcile'] === 'success'): ?>
        <div class="updated notice is-dismissible"><p>Reconcile complete! <strong><?php echo intval($_GET['removed']); ?></strong> stale or canceled event(s) were removed from the local database.</p></div>
    <?php elseif (isset($_GET['reconcile']) && $_GET['reconcile'] === 'failed'): ?>
        <div class="error notice is-dismissible"><p>Reconcile failed. Could not reach the API. Please check your credentials.</p></div>
    <?php endif; ?>

    <form method="post" action="options.php">
        <?php
            settings_fields( 'gotham_showclix_option_group' );
            do_settings_sections( 'gotham_showclix_option_group' );
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">API Token</th>
                <td>
                    <input type="password" name="gotham_showclix_api_token" value="<?php echo esc_attr( get_option('gotham_showclix_api_token') ); ?>" class="regular-text" autocomplete="off" />
                    <p class="description">Your Showclix/LeapEvents x-api-token.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Seller ID</th>
                <td>
                    <input type="text" name="gotham_showclix_seller_id" value="<?php echo esc_attr( get_option('gotham_showclix_seller_id') ); ?>" class="regular-text" autocomplete="off" />
                    <p class="description">Your Showclix/LeapEvents Seller ID.</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Timezone</th>
                <td>
                    <input type="text" name="gotham_showclix_timezone" value="<?php echo esc_attr( get_option('gotham_showclix_timezone', 'America/New_York') ); ?>" class="regular-text" autocomplete="off" />
                    <p class="description">Target timezone for event display (e.g. <code>America/New_York</code>).</p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">Update Frequency</th>
                <td>
                    <select name="gotham_showclix_sync_interval">
                        <?php $current_interval = get_option('gotham_showclix_sync_interval', 'halfhourly'); ?>
                        <option value="halfhourly" <?php selected($current_interval, 'halfhourly'); ?>>Every 30 Minutes</option>
                        <option value="hourly" <?php selected($current_interval, 'hourly'); ?>>Hourly</option>
                        <option value="twicedaily" <?php selected($current_interval, 'twicedaily'); ?>>Twice Daily (Every 12 Hours)</option>
                        <option value="daily" <?php selected($current_interval, 'daily'); ?>>Daily</option>
                    </select>
                    <p class="description">How often the background script should check LeapEvents for updates.</p>
                </td>
            </tr>
        </table>
        
        <?php submit_button('Save Settings'); ?>
    </form>
    
    <hr style="margin-top: 30px; margin-bottom: 30px;" />

    <h2>Manual Sync</h2>
    <p>Click the button below to manually force a sync cycle from Showclix to the local database. Background batches also run automatically based on your selected Update Frequency.</p>
    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
        <input type="hidden" name="action" value="gotham_showclix_sync">
        <?php wp_nonce_field('gotham_showclix_sync_nonce'); ?>
        <?php submit_button('Force Event Sync', 'secondary'); ?>
    </form>

    <hr style="margin-top: 30px; margin-bottom: 30px;" />

    <h2>Reconcile Events</h2>
    <p>Use this to check which events are currently active in the API and automatically remove any that are no longer listed (e.g. canceled or paused shows). This does <strong>not</strong> delete events from Showclix/LeapEvents — only from your local database.</p>
    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
        <input type="hidden" name="action" value="gotham_showclix_reconcile">
        <?php wp_nonce_field('gotham_showclix_reconcile_nonce'); ?>
        <?php submit_button('Reconcile Events (Remove Canceled/Paused)', 'primary'); ?>
    </form>

    <hr style="margin-top: 30px; margin-bottom: 30px;" />

    <h2>Database Management</h2>
    <p>Click the button below to permanently remove all synchronized events from the database. This action cannot be undone.</p>
    <form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" onsubmit="return confirm('Are you sure you want to delete all events? This will not delete them from Showclix/LeapEvents, only from your local database.');">
        <input type="hidden" name="action" value="gotham_showclix_delete_all">
        <?php wp_nonce_field('gotham_showclix_delete_all_nonce'); ?>
        <?php submit_button('Delete All Events', 'delete', 'submit', false); ?>
    </form>

    <hr style="margin-top: 30px; margin-bottom: 30px;" />

    <h2>Sync Logs</h2>
    <p>Recent synchronization activity and errors are logged below (most recent first).</p>
    <div style="background: #1d2327; color: #f0f0f1; padding: 15px; font-family: monospace; height: 250px; overflow-y: scroll; border-radius: 4px; box-shadow: inset 0 1px 2px rgba(0,0,0,0.1);">
        <?php
        $logs = get_option('gotham_showclix_sync_logs', array());
        if (empty($logs) || !is_array($logs)) {
            echo '<em>No sync logs recorded yet.</em>';
        } else {
            foreach ($logs as $log) {
                echo '<div>' . esc_html($log) . '</div>';
            }
        }
        ?>
    </div>
</div>
