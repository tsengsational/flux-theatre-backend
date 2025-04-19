<?php
/**
 * Customizer Control for Hero Carousel Images
 *
 * @package Flux_Theatre
 */

if (!class_exists('WP_Customize_Control')) {
    return;
}

/**
 * Customize Carousel Control class.
 */
class Flux_Customize_Carousel_Control extends WP_Customize_Control {
    /**
     * Control type.
     *
     * @var string
     */
    public $type = 'carousel';

    /**
     * Render the control's content.
     */
    public function render_content() {
        ?>
        <label>
            <?php if (!empty($this->label)) : ?>
                <span class="customize-control-title"><?php echo esc_html($this->label); ?></span>
            <?php endif; ?>
            <?php if (!empty($this->description)) : ?>
                <span class="description customize-control-description"><?php echo esc_html($this->description); ?></span>
            <?php endif; ?>
            <div class="carousel-images-container">
                <ul class="carousel-images-list">
                    <?php
                    $images = $this->value();
                    if (!empty($images)) {
                        foreach ($images as $image_id) {
                            $image_url = wp_get_attachment_image_url($image_id, 'thumbnail');
                            if ($image_url) {
                                echo '<li class="carousel-image-item" data-id="' . esc_attr($image_id) . '">';
                                echo '<img src="' . esc_url($image_url) . '" alt="">';
                                echo '<button type="button" class="remove-image">×</button>';
                                echo '</li>';
                            }
                        }
                    }
                    ?>
                </ul>
                <button type="button" class="button add-carousel-image"><?php esc_html_e('Add Image', 'flux-theatre'); ?></button>
            </div>
            <input type="hidden" <?php $this->link(); ?> value="<?php echo esc_attr(implode(',', $images)); ?>">
        </label>
        <?php
    }
} 