<?php
/**
 * Shortcode display for list view (Schedule)
 *
 * @package    Gotham_Showclix
 * @subpackage Gotham_Showclix/public/partials
 */
?>
<div class="gsc-schedule-list">
    <?php if (empty($events)) : ?>
        <p>No upcoming events found.</p>
    <?php else : ?>
        <?php foreach ($events as $event) : 
            $evt_link = esc_url(site_url('/events/' . (!empty($event->slug) ? $event->slug : $event->event_id)));
            $ts = strtotime($event->event_date);
        ?>
            <div class="gsc-schedule-row">
                <!-- Date Column -->
                <div class="gsc-schedule-date-col">
                    <!-- <div class="gsc-schedule-dow"><?php echo esc_html(wp_date('l', $ts, Gotham_Showclix::get_timezone())); ?></div> -->
                    <div class="gsc-schedule-date day"><?php echo esc_html(wp_date('j', $ts, Gotham_Showclix::get_timezone())); ?></div>
                    <div class="month-year">
                        <div class="gsc-schedule-date"><?php echo esc_html(wp_date('M', $ts, Gotham_Showclix::get_timezone())); ?></div>
                        <div class="gsc-schedule-date"><?php echo esc_html(wp_date('Y', $ts, Gotham_Showclix::get_timezone())); ?></div>
                    </div>
                </div>

                <!-- Main Info Column -->
                <div class="gsc-schedule-info-col">
                    <?php if (!empty($event->image_url)) : ?>
                        <div class="gsc-schedule-thumb">
                            <img src="<?php echo esc_url($event->image_url); ?>" alt="<?php echo esc_attr($event->title); ?>">
                        </div>
                    <?php else: ?>
                        <div class="gsc-schedule-thumb gsc-no-thumb"></div>
                    <?php endif; ?>
                    
                    <div class="gsc-schedule-details">
                        <h3 class="gsc-schedule-title"><a href="<?php echo $evt_link; ?>"><?php echo esc_html($event->title); ?></a></h3>
                        <!-- <div class="gsc-schedule-time"><?php echo esc_html(wp_date('g:i A', $ts, Gotham_Showclix::get_timezone())); ?></div> -->
                    </div>
                </div>

                <div class="gsc-schedule-time-col">
                    <div class="gsc-schedule-time"><?php echo esc_html(wp_date('g:i A', $ts, Gotham_Showclix::get_timezone())); ?></div>
                </div>

                <!-- Action Column -->
                <div class="gsc-schedule-action-col">
                    <a href="<?php echo $evt_link; ?>" class="gsc-schedule-btn">GET TICKETS</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
