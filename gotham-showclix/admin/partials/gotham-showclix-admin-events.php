<?php
/**
 * All Events display
 *
 * @package    Gotham_Showclix
 * @subpackage Gotham_Showclix/admin/partials
 */
?>
<div class="wrap">
    <h1>All Events</h1>
    
    <?php
        require_once plugin_dir_path( dirname( __FILE__ ) ) . '../includes/class-db-manager.php';
        $db = new Gotham_Showclix_DB_Manager();
        $events = $db->get_events();
    ?>
    <?php if (empty($events)): ?>
        <p>No events found in the local database. Please click "Force Event Sync" in the Settings page after entering your token.</p>
    <?php else: ?>
        <p><strong>Total Local Events:</strong> <?php echo count($events); ?></p>
        <div style="max-height: 800px; overflow-y: auto; border: 1px solid #ccd0d4; box-shadow: 0 1px 1px rgba(0,0,0,.04);">
            <table class="wp-list-table widefat fixed striped table-view-list">
                <thead>
                    <tr>
                        <th style="width: 10%;">ID</th>
                        <th style="width: 20%;">Date & Time</th>
                        <th style="width: 40%;">Title</th>
                        <th style="width: 30%;">Venue</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo esc_html($event->event_id); ?></td>
                            <td>
                                <strong><?php echo esc_html(wp_date('M j, Y', strtotime($event->event_date), Gotham_Showclix::get_timezone())); ?></strong><br/>
                                <span style="color:#666;"><?php echo esc_html(wp_date('g:i a', strtotime($event->event_date), Gotham_Showclix::get_timezone())); ?></span>
                            </td>
                            <td><strong><?php echo esc_html($event->title); ?></strong></td>
                            <td><?php echo esc_html($event->venue); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
