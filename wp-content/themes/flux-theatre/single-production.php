<?php
/**
 * Template Name: Single Production
 * Template for displaying a single production
 */

get_header();
?>

<main id="primary" class="site-main">
    <?php while (have_posts()) : the_post(); ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class('production-single'); ?>>
            <header class="entry-header">
                <h1 class="entry-title"><?php the_title(); ?></h1>
                
                <?php if (has_post_thumbnail()) : ?>
                    <div class="production-featured-image">
                        <?php the_post_thumbnail('large'); ?>
                    </div>
                <?php endif; ?>
            </header>

            <div class="entry-content">
                <div class="production-details">
                    <?php
                    // Get production dates
                    $dates = get_post_meta(get_the_ID(), '_performance_dates', true);
                    if (!empty($dates)) : ?>
                        <section class="performance-dates">
                            <h2>Performance Dates</h2>
                            <ul class="dates-list">
                                <?php foreach ($dates as $date) : ?>
                                    <li><?php echo date('l, F j, Y', strtotime($date)); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endif; ?>

                    <?php
                    // Get venue information
                    $venue_id = get_post_meta(get_the_ID(), '_production_venue', true);
                    if ($venue_id) : 
                        $venue = get_post($venue_id);
                        if ($venue) : ?>
                            <section class="production-venue">
                                <h2>Venue</h2>
                                <h3><?php echo esc_html($venue->post_title); ?></h3>
                                <div class="venue-content">
                                    <?php echo wpautop($venue->post_content); ?>
                                </div>
                            </section>
                        <?php endif;
                    endif; ?>

                    <?php
                    // Get associated bylines
                    $bylines = get_post_meta(get_the_ID(), '_production_bylines', true);
                    if (!empty($bylines)) : ?>
                        <section class="production-bylines">
                            <h2>Cast & Crew</h2>
                            <div class="bylines-grid">
                                <?php foreach ($bylines as $bylines_id) : 
                                    $bylines_post = get_post($bylines_id);
                                    if ($bylines_post) : ?>
                                        <div class="bylines-card">
                                            <?php if (has_post_thumbnail($bylines_id)) : ?>
                                                <div class="bylines-image">
                                                    <?php echo get_the_post_thumbnail($bylines_id, 'thumbnail'); ?>
                                                </div>
                                            <?php endif; ?>
                                            <h3><?php echo esc_html($bylines_post->post_title); ?></h3>
                                            <div class="bylines-content">
                                                <?php echo wpautop($bylines_post->post_content); ?>
                                            </div>
                                            <?php
                                            $social_links = get_post_meta($bylines_id, '_bylines_social_links', true);
                                            if (!empty($social_links)) : ?>
                                                <div class="bylines-social">
                                                    <?php foreach ($social_links as $platform => $url) : 
                                                        if (!empty($url)) : ?>
                                                            <a href="<?php echo esc_url($url); ?>" target="_blank" rel="noopener noreferrer">
                                                                <span class="screen-reader-text"><?php echo esc_html(ucfirst($platform)); ?></span>
                                                            </a>
                                                        <?php endif;
                                                    endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif;
                                endforeach; ?>
                            </div>
                        </section>
                    <?php endif; ?>

                    <section class="production-content">
                        <?php the_content(); ?>
                    </section>
                </div>
            </div>
        </article>
    <?php endwhile; ?>
</main>

<?php
get_footer();
?> 