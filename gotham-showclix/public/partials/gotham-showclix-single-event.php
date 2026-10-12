<?php
/**
 * Single event dynamic page display
 *
 * @package    Gotham_Showclix
 * @subpackage Gotham_Showclix/public/partials
 */

global $gotham_showclix_event;
$event = $gotham_showclix_event;

get_header();

$db = new Gotham_Showclix_DB_Manager();
$all_upcoming = $db->get_events();
$upcoming = array_filter($all_upcoming, function($uev) use ($event) {
    return $uev->event_id !== $event->event_id;
});
$upcoming = array_slice($upcoming, 0, 8);
?>

<div class="gsc-event-page">

    <div class="gsc-event-wrap">

        <!-- Image -->
        <div class="gsc-event-image-col">
            <?php if ( ! empty( $event->image_url ) ) : ?>
                <img src="<?php echo esc_url( $event->image_url ); ?>" alt="<?php echo esc_attr( $event->title ); ?>">
            <?php else : ?>
                <div class="gsc-event-image-placeholder">No Image Available</div>
            <?php endif; ?>
        </div>

        <!-- Info -->
        <div class="gsc-event-info-col">
            <h1 class="gsc-event-title"><?php echo esc_html( $event->title ); ?></h1>

            <div class="gsc-event-meta">
                <p><strong>Showtime:</strong> <?php echo esc_html( wp_date( 'F j, Y \a\t g:i a', strtotime( $event->event_date ), Gotham_Showclix::get_timezone() ) ); ?></p>
                <?php if ( ! empty( $event->venue ) ) : ?>
                    <p><strong>Venue:</strong> <?php echo esc_html( $event->venue ); ?></p>
                <?php endif; ?>
            </div>

            <?php if ( ! empty( $event->description ) ) : ?>
                <div class="gsc-event-description">
                    <?php echo apply_filters( 'the_content', $event->description ); ?>
                </div>
            <?php endif; ?>

            <!-- Get Tickets -->
            <?php
            $is_embed_url = ! empty( $event->ticket_url ) && strpos( $event->ticket_url, '/embed/' ) !== false;
            ?>
            <?php if ( $is_embed_url ) : ?>
                <div id="gsc-ticket-embed-container" class="gsc-ticket-embed-container" data-ticket-url="<?php echo esc_attr( $event->ticket_url ); ?>">
                    <button id="gsc-ticket-loader" class="gsc-get-tickets-btn" style="pointer-events: none; opacity: 0.85; display: inline-flex; align-items: center; justify-content: center; gap: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="gsc-spin">
						  <line x1="12" y1="2" x2="12" y2="6"/>
						  <line x1="12" y1="18" x2="12" y2="22"/>
						  <line x1="2" y1="12" x2="6" y2="12"/>
						  <line x1="18" y1="12" x2="22" y2="12"/>
						  <line x1="5" y1="5" x2="8" y2="8"/>
						  <line x1="16" y1="16" x2="19" y2="19"/>
						  <line x1="5" y1="19" x2="8" y2="16"/>
						  <line x1="16" y1="8" x2="19" y2="5"/>
                        </svg>
                        Loading...
                    </button>
                </div>
            <?php elseif ( ! empty( $event->ticket_url ) ) : ?>
                <!-- Fallback: direct link if URL is not an embed URL -->
                <a href="<?php echo esc_url( $event->ticket_url ); ?>" target="_blank" rel="noopener" class="gsc-get-tickets-btn">Get Tickets</a>
            <?php else : ?>
                <p class="gsc-no-tickets">Ticket options are currently unavailable.</p>

            <?php endif; ?>

        </div><!-- /.gsc-event-info-col -->
    </div><!-- /.gsc-event-wrap -->

    <?php if ( ! empty( $upcoming ) ) : ?>
        <div class="gsc-upcoming-section">
            <h2>Upcoming Events</h2>
            <div class="gsc-upcoming-grid">
                <?php foreach ( $upcoming as $uev ) :
                    $uslug     = ! empty( $uev->slug ) ? $uev->slug : $uev->event_id;
                    $uurl      = site_url( '/events/' . $uslug );
                    $uts       = strtotime( $uev->event_date );
                    $u_month   = wp_date( 'M', $uts, Gotham_Showclix::get_timezone() );
                    $u_day     = wp_date( 'j', $uts, Gotham_Showclix::get_timezone() );
                    $u_dow     = wp_date( 'D', $uts, Gotham_Showclix::get_timezone() );
                    $u_datetime = wp_date( 'F j, Y \a\t g:i a', $uts, Gotham_Showclix::get_timezone() );
                ?>
                <div class="gsc-event-card">
                    <a href="<?php echo esc_url( $uurl ); ?>" style="text-decoration:none;color:inherit;">
                        <div class="gsc-card-image-wrap">
                            <?php if ( ! empty( $uev->image_url ) ) : ?>
                                <img src="<?php echo esc_url( $uev->image_url ); ?>" alt="<?php echo esc_attr( $uev->title ); ?>">
                            <?php else : ?>
                                <div class="gsc-card-no-image">No Image</div>
                            <?php endif; ?>
                            <div class="gsc-date-badge">
                                <div class="month"><?php echo esc_html( $u_month ); ?></div>
                                <div class="day"><?php echo esc_html( $u_day ); ?></div>
                                <div class="dow"><?php echo esc_html( $u_dow ); ?></div>
                            </div>
                        </div>
                    </a>
                    <div class="gsc-card-body">
                        <h3 class="gsc-card-title"><?php echo esc_html( $uev->title ); ?></h3>
                        <p class="gsc-card-datetime"><?php echo esc_html( $u_datetime ); ?></p>
                        <a href="<?php echo esc_url( $uurl ); ?>" class="gsc-card-btn">Get Tickets</a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

</div><!-- /.gsc-event-page -->

<?php get_footer(); ?>
