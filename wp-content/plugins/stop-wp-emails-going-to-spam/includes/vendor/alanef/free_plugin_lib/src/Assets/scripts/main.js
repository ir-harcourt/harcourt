document.addEventListener('DOMContentLoaded', function () {
    // Toggle details functionality
    const detailsLink = document.getElementById('detailsLink');
    if (detailsLink) {
        detailsLink.addEventListener('click', toggleDetails);
    }

    // Opt-in button functionality
    const optinButton = document.querySelector('.btn-optin');
    if (optinButton) {
        optinButton.addEventListener('click', handleOptIn);
    }

    function handleOptIn(e) {
        e.preventDefault();
        
        const emailInput = document.getElementById('fpl_email');
        const email = emailInput.value;
        
        // Disable button and show loading state
        optinButton.disabled = true;
        optinButton.innerHTML = 'Processing...';

        // Make AJAX request
        jQuery.ajax({
            url: ffplData.ajaxurl,
            type: 'POST',
            data: {
                action: 'ffpl_handle_optin',
                email: email,
                nonce: ffplData.nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = ffplData.settings_page;
                }
                console.log('AJAX Response:', response);
            },
            error: function(e) {
                console.error('AJAX Error:', e);
                optinButton.disabled = false;
                optinButton.innerHTML = 'Allow & Continue';
                alert('An error occurred. Please try again.');
            }
        });
    }

    function toggleDetails(event) {
        event.preventDefault();
        const detailsContent = document.getElementById('detailsContent');

        // Make sure the element's current display state is correctly used
        if (detailsContent.style.display === 'none' || detailsContent.style.display === '') {
            detailsContent.style.display = 'block';
        } else {
            detailsContent.style.display = 'none';
        }
    }
});
