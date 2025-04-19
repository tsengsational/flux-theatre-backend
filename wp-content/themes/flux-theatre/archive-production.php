<?php
/**
 * Template Name: Productions Archive
 * Template for displaying all productions in a timeline format
 */

get_header();
?>

<main id="primary" class="site-main">
    <header class="page-header">
        <h1 class="page-title">Productions</h1>
    </header>

    <div class="productions-timeline">
        <?php
        // Get all productions ordered by date
        $args = array(
            'post_type' => 'production',
            'posts_per_page' => -1,
            'meta_key' => '_performance_dates',
            'orderby' => 'meta_value',
            'order' => 'DESC'
        );
        
        $productions = new WP_Query($args);
        
        if ($productions->have_posts()) :
            $current_year = '';
            
            while ($productions->have_posts()) : $productions->the_post();
                // Get the first date of the production
                $dates = get_post_meta(get_the_ID(), '_performance_dates', true);
                if (!empty($dates)) {
                    $first_date = strtotime($dates[0]);
                    $year = date('Y', $first_date);
                    
                    // Display year header if it's a new year
                    if ($year !== $current_year) {
                        $current_year = $year;
                        echo '<div class="timeline-year">' . esc_html($current_year) . '</div>';
                    }
                    ?>
                    
                    <article id="post-<?php the_ID(); ?>" <?php post_class('timeline-item'); ?>>
                        <div class="timeline-content">
                            <div class="timeline-date">
                                <?php echo date('F j', $first_date); ?>
                            </div>
                            
                            <div class="timeline-details">
                                <h2 class="entry-title">
                                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                                </h2>
                                
                                <?php if (has_post_thumbnail()) : ?>
                                    <div class="timeline-image">
                                        <a href="<?php the_permalink(); ?>">
                                            <?php the_post_thumbnail('medium'); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="timeline-excerpt">
                                    <?php the_excerpt(); ?>
                                </div>
                                
                                <?php
                                // Get venue information
                                $venue_id = get_post_meta(get_the_ID(), '_production_venue', true);
                                if ($venue_id) : 
                                    $venue = get_post($venue_id);
                                    if ($venue) : ?>
                                        <div class="timeline-venue">
                                            <strong>Venue:</strong> <?php echo esc_html($venue->post_title); ?>
                                        </div>
                                    <?php endif;
                                endif; ?>
                                
                                <a href="<?php the_permalink(); ?>" class="timeline-read-more">View Production Details</a>
                            </div>
                        </div>
                    </article>
                    
                <?php }
            endwhile;
            
            wp_reset_postdata();
        else : ?>
            <p class="no-productions">No productions found.</p>
        <?php endif; ?>
    </div>
</main>

<?php
get_footer();
?> 