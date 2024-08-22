document.addEventListener('DOMContentLoaded', function() {
    const termLinks = document.querySelectorAll('.taxonomy-term-link');

    termLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault(); // Prevent the default link behavior

            const termId = this.getAttribute('data-term-id');
            const taxonomy = this.getAttribute('data-taxonomy');

            const projectsContainer = document.querySelector('.projects-container');

            // Add fade-out class to start fading out
            projectsContainer.classList.add('fade-out');

            // Use a timeout to wait for the fade-out transition to complete
            setTimeout(() => {
                // Prepare the data for the AJAX request
                const data = new FormData();
                data.append('action', 'filter_projects_by_taxonomy');
                data.append('term_id', termId);
                data.append('taxonomy', taxonomy);
                data.append('nonce', ajax_obj.nonce);

                // Make the AJAX request
                fetch(ajax_obj.ajax_url, {
                    method: 'POST',
                    body: data,
                })
                .then(function(response) {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(function(responseText) {
                    // Update the content
                    projectsContainer.innerHTML = responseText;

                    // Remove fade-out class and add fade-in class to start fading in
                    projectsContainer.classList.remove('fade-out');
                    projectsContainer.classList.add('fade-in');
                })
                .catch(function(error) {
                    console.log('AJAX Error:', error);
                    projectsContainer.innerHTML = '<p>There was an error loading the projects. Please try again.</p>';

                    // Remove fade-out class and add fade-in class
                    projectsContainer.classList.remove('fade-out');
                    projectsContainer.classList.add('fade-in');
                });
            }, 300); // Timeout should match the CSS transition duration (300ms)
        });
    });
});
