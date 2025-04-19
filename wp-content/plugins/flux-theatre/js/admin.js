jQuery(document).ready(function($) {
    // Production Dates Calendar
    if ($('#performance-dates-calendar').length) {
        const calendar = $('#performance-dates-calendar');
        const yearSelect = $('#performance_dates_year');
        const selectedDatesContainer = $('#performance-dates-selected-dates');
        const datesInput = $('#performance_dates_input');
        let existingDates = datesInput.val() ? datesInput.val().split(',') : [];
        
        // Initialize calendar with current year
        calendar.datepicker({
            dateFormat: 'yy-mm-dd',
            numberOfMonths: [3,4],
            showButtonPanel: true,
            onSelect: function(dateText, inst) {
                // The dateText is already in YYYY-MM-DD format
                addSelectedDate(dateText);
                updateSelectedDates();
            },
            beforeShowDay: function(date) {
                const dateString = $.datepicker.formatDate('yy-mm-dd', date);
                return [true, existingDates.includes(dateString) ? 'selected-date' : ''];
            }
        });
        
        // Handle year change
        yearSelect.on('change', function() {
            const selectedYear = $(this).val();
            const currentDate = calendar.datepicker('getDate');
            if (currentDate) {
                currentDate.setFullYear(selectedYear);
                calendar.datepicker('setDate', currentDate);
            } else {
                calendar.datepicker('setDate', new Date(selectedYear, 0, 1));
            }
        });
        
        // Function to add a selected date
        function addSelectedDate(date) {
            if (!existingDates.includes(date)) {
                existingDates.push(date);
                updateSelectedDates();
            }
        }
        
        // Function to update selected dates display
        function updateSelectedDates() {
            // Sort dates chronologically
            existingDates.sort((a, b) => new Date(a) - new Date(b));
            
            // Update hidden input
            datesInput.val(existingDates.join(','));
            
            // Update selected dates display
            selectedDatesContainer.empty();
            existingDates.forEach(date => {
                const dateObj = new Date(date + 'T12:00:00'); // Set to noon to avoid timezone issues
                const dateString = dateObj.toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
                
                selectedDatesContainer.append(`
                    <span class="performance-dates-selected-date" data-date="${date}">
                        ${dateString}
                        <button type="button" class="performance-dates-remove-date">&times;</button>
                    </span>
                `);
            });
            
            // Update calendar highlighting
            calendar.datepicker('setDate', existingDates[0] || null);
        }
        
        // Handle remove date button clicks
        selectedDatesContainer.on('click', '.performance-dates-remove-date', function(e) {
            e.preventDefault();
            const dateSpan = $(this).parent();
            const date = dateSpan.data('date');
            const index = existingDates.indexOf(date);
            if (index !== -1) {
                existingDates.splice(index, 1);
                updateSelectedDates();
            }
        });
        
        // Initialize with existing dates
        if (existingDates.length > 0) {
            updateSelectedDates();
        }
    }

    // Venue creation
    $(document).on('click', '#add-new-venue', function() {
        $('#new-venue-form').show();
        $(this).hide();
    });

    $(document).on('click', '#cancel-new-venue', function() {
        $('#new-venue-form').hide();
        $('#add-new-venue').show();
        $('#new_venue_name').val('');
        $('#new_venue_address').val('');
    });

    $(document).on('click', '#save-new-venue', function() {
        const name = $('#new_venue_name').val();
        const address = $('#new_venue_address').val();

        if (!name) {
            alert('Please enter a venue name');
            return;
        }

        $.ajax({
            url: fluxTheatre.ajaxUrl,
            type: 'POST',
            data: {
                action: 'create_venue',
                nonce: fluxTheatre.nonce,
                name: name,
                address: address
            },
            success: function(response) {
                if (response.success) {
                    // Add new venue to select
                    const option = $('<option>')
                        .val(response.data.id)
                        .text(response.data.title)
                        .prop('selected', true);
                    
                    $('#production_venue').append(option);
                    
                    // Reset form
                    $('#new-venue-form').hide();
                    $('#add-new-venue').show();
                    $('#new_venue_name').val('');
                    $('#new_venue_address').val('');
                } else {
                    alert('Failed to create venue: ' + response.data);
                }
            },
            error: function() {
                alert('Failed to create venue. Please try again.');
            }
        });
    });

    // Featured Image functionality
    var frame;
    var $featuredImageContainer = $('.featured-image-container');
    var $featuredImagePreview = $('.featured-image-preview');
    var $thumbnailId = $('#_thumbnail_id');

    // Set featured image
    $('.set-featured-image').on('click', function(e) {
        e.preventDefault();

        // If the media frame already exists, reopen it.
        if (frame) {
            frame.open();
            return;
        }

        // Create the media frame.
        frame = wp.media({
            title: $(this).data('title'),
            button: {
                text: 'Set featured image'
            },
            multiple: false
        });

        // When an image is selected, run a callback.
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            
            // Update the hidden input
            $thumbnailId.val(attachment.id);
            
            // Update the preview
            $featuredImagePreview.html(
                $('<img>').attr({
                    src: attachment.url,
                    alt: attachment.alt || ''
                })
            );
            
            // Show the remove link
            $('.remove-featured-image').show();
        });

        // Finally, open the modal
        frame.open();
    });

    // Remove featured image
    $('.remove-featured-image').on('click', function(e) {
        e.preventDefault();
        
        // Clear the hidden input
        $thumbnailId.val('');
        
        // Update the preview
        $featuredImagePreview.html(
            '<div class="no-image-placeholder">' +
                '<span class="dashicons dashicons-format-image"></span>' +
                '<p>No featured image set</p>' +
            '</div>'
        );
        
        // Hide the remove link
        $(this).hide();
    });

    // Debug logging
    console.log('Flux Theatre: Date picker initialized');
}); 