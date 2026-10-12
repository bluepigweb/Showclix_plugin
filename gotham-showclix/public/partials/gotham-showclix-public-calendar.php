<?php
/**
 * Shortcode display for the interactive calendar
 *
 * @package    Gotham_Showclix
 * @subpackage Gotham_Showclix/public/partials
 */

$calendar_events = array();
if (!empty($events)) {
    foreach ($events as $event) {
        $date_key = wp_date('Y-m-d', strtotime($event->event_date), Gotham_Showclix::get_timezone());
        if (!isset($calendar_events[$date_key])) {
            $calendar_events[$date_key] = array();
        }
        $calendar_events[$date_key][] = array(
            'title'     => $event->title,
            'time'      => wp_date('g:i A', strtotime($event->event_date), Gotham_Showclix::get_timezone()),
            'timestamp' => strtotime($event->event_date),
            'url'       => site_url('/events/' . (!empty($event->slug) ? $event->slug : $event->event_id)),
            'image'     => !empty($event->image_url) ? esc_url($event->image_url) : ''
        );
    }
}
?>
<div class="custom_event_data gotham-calendar">
    <script>
        window.gothamCalEvents = <?php echo wp_json_encode($calendar_events); ?>;
    </script>

    <div class="custom_top_filters">
        <div class="events-search">
            <input id="gotham-search-input" type="text" placeholder="Search events&hellip;" aria-label="Search events">
            <button id="gotham-search-btn">SEARCH</button>
        </div>
        <div class="custom_drop_down_filters">
            <div class="events-dropdown custom-view-dropdown" style="position: relative;">
                <div id="gotham-view-dropdown-btn" class="events-dropdown-button custom-events-dropdown-button">
                    <div class="events-filter-icon calendar" style="margin-right: 6px;"></div>
                    <span id="gotham-view-label">Calendar View</span>
                </div>
                <div id="gotham-view-dropdown-content" class="events-dropdown-content custom-events-dropdown-content">
                    <div class="events-dropwdown-option events-view-option gotham-view-option" data-view="dayGridMonth">
                        <div class="events-filter-icon calendar" style="margin-right: 6px;"></div>
                        Calendar View
                    </div>
                    <div class="events-dropwdown-option events-view-option gotham-view-option" data-view="listMonth">
                        <div class="events-filter-icon list" style="margin-right: 6px;"></div>
                        List View
                    </div>
                    <div class="events-dropwdown-option events-view-option gotham-view-option" data-view="listDay">
                        <div class="events-filter-icon day" style="margin-right: 6px;"></div>
                        Day View
                    </div>
                </div>
            </div>

            <div class="events-clear-filters">
                <button id="gotham-clear-btn">
                    <div class="events-clear-filters-icon"></div>
                    Clear Filters
                </button>
            </div>
        </div>
    </div>

    <div class="second_nav_icon">
        <button id="gotham-prev-btn" class="events-nav-arrow previous" aria-label="Previous month">&lsaquo;</button>
        <span id="gotham-month-label" class="events-nav-arrow">Loading&hellip;</span>
        <button id="gotham-next-btn" class="events-nav-arrow next" aria-label="Next month">&rsaquo;</button>
    </div>

    <div id="gotham-fullcalendar"></div>
    <div id="gotham-custom-list-view" style="display: none;"></div>
</div>
