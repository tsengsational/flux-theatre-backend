(function($) {
    'use strict';

    // Control visibility management
    var controlVisibility = {
        'image': ['flux_hero_image', 'flux_hero_image_alt'],
        'video': ['flux_hero_video_url'],
        'carousel': ['flux_hero_carousel_images', 'flux_hero_carousel_autoplay', 'flux_hero_carousel_interval']
    };

    // Update control visibility based on media type
    function updateControlVisibility(type) {
        // Hide all controls first
        Object.values(controlVisibility).flat().forEach(function(controlId) {
            wp.customize.control(controlId).container.hide();
        });

        // Show relevant controls
        if (controlVisibility[type]) {
            controlVisibility[type].forEach(function(controlId) {
                wp.customize.control(controlId).container.show();
            });
        }
    }

    // Initialize control visibility
    wp.customize('flux_hero_media_type', function(setting) {
        setting.bind(function(value) {
            updateControlVisibility(value);
        });
        updateControlVisibility(setting.get());
    });

    // Handle carousel image upload
    $('.add-carousel-image').on('click', function(e) {
        e.preventDefault();
        var $button = $(this);
        var $container = $button.closest('.carousel-images-container');
        var $list = $container.find('.carousel-images-list');
        var $input = $container.find('input[type="hidden"]');

        var frame = wp.media({
            title: 'Select Carousel Images',
            button: {
                text: 'Add to Carousel'
            },
            multiple: true
        });

        frame.on('select', function() {
            var attachments = frame.state().get('selection').toJSON();
            var currentIds = $input.val() ? $input.val().split(',') : [];

            attachments.forEach(function(attachment) {
                if (currentIds.indexOf(attachment.id.toString()) === -1) {
                    currentIds.push(attachment.id);
                    $list.append(
                        '<li class="carousel-image-item" data-id="' + attachment.id + '">' +
                        '<img src="' + attachment.sizes.thumbnail.url + '" alt="">' +
                        '<button type="button" class="remove-image">×</button>' +
                        '</li>'
                    );
                }
            });

            $input.val(currentIds.join(',')).trigger('change');
        });

        frame.open();
    });

    // Handle carousel image removal
    $(document).on('click', '.remove-image', function(e) {
        e.preventDefault();
        var $item = $(this).closest('.carousel-image-item');
        var $container = $item.closest('.carousel-images-container');
        var $input = $container.find('input[type="hidden"]');
        var currentIds = $input.val() ? $input.val().split(',') : [];
        var idToRemove = $item.data('id').toString();

        currentIds = currentIds.filter(function(id) {
            return id !== idToRemove;
        });

        $item.remove();
        $input.val(currentIds.join(',')).trigger('change');
    });

    // Debug logging for Hero Image ID
    wp.customize('flux_hero_image', function(setting) {
        setting.bind(function(value) {
            console.log('Hero Image ID changed:', value);
        });
    });

})(jQuery); 