<?php
/**
 * Template Name: Productions Timeline
 */

get_header();
?>

<main id="primary" class="site-main">
    <div class="container mx-auto px-4 py-8">
        <header class="page-header mb-8">
            <h1 class="text-4xl font-bold mb-2">Productions</h1>
            <p class="text-gray-600">A timeline of our past and upcoming productions</p>
        </header>

        <?php
        $productions = new WP_Query(array(
            'post_type' => 'production',
            'posts_per_page' => -1,
            'orderby' => 'meta_value',
            'meta_key' => '_performance_dates',
            'order' => 'DESC'
        ));

        if ($productions->have_posts()) :
            // Group productions by year
            $productions_by_year = array();
            while ($productions->have_posts()) :
                $productions->the_post();
                $dates = get_post_meta(get_the_ID(), '_performance_dates', true);
                if (!empty($dates)) {
                    $year = date('Y', strtotime($dates[0]));
                    if (!isset($productions_by_year[$year])) {
                        $productions_by_year[$year] = array();
                    }
                    $productions_by_year[$year][] = $post;
                }
            endwhile;

            // Sort years in ascending order
            ksort($productions_by_year);

            // Display productions by year
            foreach ($productions_by_year as $year => $year_productions) :
                ?>
                <div class="year-section mb-12">
                    <h2 class="text-3xl font-bold mb-6"><?php echo $year; ?></h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                        <?php
                        foreach ($year_productions as $production) :
                            setup_postdata($production);
                            $dates = get_post_meta($production->ID, '_performance_dates', true);
                            $bylines = get_post_meta($production->ID, '_production_bylines', true);
                            ?>
                            <article class="production-card bg-white rounded-lg shadow-lg overflow-hidden">
                                <?php if (has_post_thumbnail($production->ID)) : ?>
                                    <div class="aspect-w-16 aspect-h-9">
                                        <?php echo get_the_post_thumbnail($production->ID, 'large', array('class' => 'w-full h-full object-cover')); ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="p-6">
                                    <h3 class="text-xl font-bold mb-2">
                                        <a href="<?php echo get_permalink($production->ID); ?>" class="hover:text-blue-600 transition-colors duration-300">
                                            <?php echo get_the_title($production->ID); ?>
                                        </a>
                                    </h3>
                                    
                                    <?php if (!empty($dates)) : ?>
                                        <div class="text-sm text-gray-600 mb-4">
                                            <?php
                                            $formatted_dates = array_map(function($date) {
                                                return date('F j, Y', strtotime($date));
                                            }, $dates);
                                            echo implode(', ', $formatted_dates);
                                            ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="prose max-w-none mb-4">
                                        <?php echo get_the_excerpt($production->ID); ?>
                                    </div>

                                    <?php if (!empty($bylines)) : ?>
                                        <div class="bylines mt-4">
                                            <h4 class="text-sm font-semibold mb-2">Cast & Crew</h4>
                                            <div class="flex flex-wrap gap-2">
                                                <?php
                                                foreach ($bylines as $bylines_id) :
                                                    $bylines_post = get_post($bylines_id);
                                                    if ($bylines_post) :
                                                        ?>
                                                        <a href="<?php echo get_permalink($bylines_id); ?>" class="text-sm text-blue-600 hover:text-blue-800">
                                                            <?php echo get_the_title($bylines_id); ?>
                                                        </a>
                                                        <?php
                                                    endif;
                                                endforeach;
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <a href="<?php echo get_permalink($production->ID); ?>" class="inline-block mt-4 text-blue-600 hover:text-blue-800 font-medium">
                                        Learn More →
                                    </a>
                                </div>
                            </article>
                            <?php
                        endforeach;
                        ?>
                    </div>
                </div>
                <?php
            endforeach;

            wp_reset_postdata();
        else :
            ?>
            <div class="text-center py-12">
                <p class="text-gray-600">No productions found.</p>
            </div>
            <?php
        endif;
        ?>
    </div>
</main>

<?php
get_footer(); 