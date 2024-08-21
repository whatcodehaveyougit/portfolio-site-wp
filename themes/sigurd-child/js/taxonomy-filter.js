jQuery(document).ready(function($) {
    $('.taxonomy-term-link').on('click', function(e) {
        e.preventDefault(); // Prevent the default link behavior

        var term_id = $(this).data('term-id');
        var taxonomy = $(this).data('taxonomy');

        $.ajax({
            url: ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'filter_projects_by_taxonomy',
                term_id: term_id,
                taxonomy: taxonomy,
                nonce: ajax_obj.nonce
            },
            beforeSend: function() {
                $('.projects-container').html('<p>Loading...</p>'); // Show a loading message or spinner
            },
            success: function(response) {
                $('.projects-container').html(response); // Update the content with the filtered posts
            },
            error: function(xhr, status, error) {
                console.log('AJAX Error:', error);
                $('.projects-container').html('<p>There was an error loading the projects. Please try again.</p>');
            }
        });
    });
});
