// On document ready
jQuery(document).ready(function($) {

    // Variables for modal controls
    var $modal = $('#reportModal');
    var $countdownDisplay = $('#countdownDisplay');
    var $confirmReportButton = $('#confirmReport');
    var $closeModalButton = $('#closeModal');
    var countdownInterval;

    // Function to show modal and start countdown
    function showModalWithCountdown(countdownLength) {
        clearInterval(countdownInterval);
        $modal.show();
        $countdownDisplay.text(countdownLength); // Initialize the display with the countdown length
        $confirmReportButton.prop('disabled', true); // Ensure the button starts as disabled
        countdownInterval = setInterval(function() {
            countdownLength--;
            $countdownDisplay.text(countdownLength);

            if (countdownLength <= 0) {
                clearInterval(countdownInterval);
                $confirmReportButton.prop('disabled', false);
            }
        }, 1000);
    }

    // Close modal button event
    $closeModalButton.on('click', function() {
        $modal.hide();
        clearInterval(countdownInterval); // Clear the countdown interval
        $countdownDisplay.text(''); // Reset the countdown display, or set to the default countdown value if you prefer
        $confirmReportButton.prop('disabled', true); // Reset the button state
        $('.report-reason').val(''); // Reset the select value
    });

    function timeSinceLastReport() {
        const lastReportTime = localStorage.getItem('lastReportTime');
        if (!lastReportTime) return Infinity; // If there's no stored time, just return Infinity
        const elapsedTime = Date.now() - lastReportTime;
        return elapsedTime / 1000; // Return elapsed time in seconds
    }

    function reportPost($thisSelect, reason, category) {

        $.ajax({
            url: mn_report_post.ajax_url,
            type: 'POST',
            data: {
                action: 'report_post',
                nonce: mn_report_post.nonce,
                post_id: parseInt($thisSelect.data('post-id'), 10),
                reason: reason,
                category: category
            },
            success: function(response) {
                if (response.success) {
                    // Store the current timestamp as the last report time
                    localStorage.setItem('lastReportTime', Date.now());
                    $thisSelect.replaceWith('<span style="color:red;">Uspešno prijavljeno za: ' + reason +
                        '</span><br><span>' + mn_report_post.message + '</span>');
                } else {
                    alert('Error while reporting.');
                }
            }
        });
    }

    // Confirm report button event
    $confirmReportButton.on('click', function() {
        var $thisSelect = $('.report-reason:visible');
        var reason = $thisSelect.val();
        var category = $thisSelect.find('option:selected').data('category');
        $modal.hide();
        reportPost($thisSelect, reason, category);
    });

    // Attach an event listener to the click event of elements with the class "report-post-btn"
    $('.report-post-btn').on('click', function(e) {
        e.preventDefault();
        var $thisButton = $(this);
        $thisButton.siblings('.report-reason').show();
        $thisButton.hide();
    });

    $('.report-reason').on('change', function() {
        var $thisSelect = $(this);
        var reason = $thisSelect.val();
        var category = $thisSelect.find('option:selected').data('category');
        var general = Number(mn_report_post.general_report_count);
        var falseR = Number(mn_report_post.false_report_count);
        var threshold = Number(mn_report_post.false_report_threshold); 
        var floodProtection = Number(mn_report_post.flood_protection);

        if (floodProtection !== 0 && timeSinceLastReport() < floodProtection) {
            alert('Prosimo počakajte nekaj trenutkov preden prijavite nov odgovor.');
            $thisSelect.val(''); // Reset the select value
            return; // Don't proceed with the report
        }

        function getCountdownTime(general, falseR, threshold) {
            const falseIndex = general / falseR;
            if (falseR >= threshold && falseIndex <= 2) {
                return (falseR / falseIndex)*2;
            }
            if (falseR >= 2 * threshold && falseIndex <= 3) {
                return (falseR / falseIndex)*2;
            }
            if (falseR >= 3 * threshold && falseIndex <= 4) {
                return (falseR / falseIndex)*2;
            }
            return 0; // Default case if no condition matches
        }

        const MAX_COUNTDOWN = 120;
        var countdownLength = getCountdownTime(general, falseR, threshold);
        if (countdownLength > MAX_COUNTDOWN) {
            countdownLength = MAX_COUNTDOWN;
        }

        if (reason) {
            if (countdownLength > 0) {
                countdownLength = Math.ceil(countdownLength);
                showModalWithCountdown(countdownLength);
            } else {
                reportPost($thisSelect, reason, category);
            }
        }
    });

    // Attach an event listener for clearing reports (if the user is a moderator or keymaster)
    $('.clear-report-btn').on('click', function(e) {
        e.preventDefault();

        var $thisButton = $(this); // Store the clicked button
        var $reportCountSpan = $thisButton.prev(); // Assuming the report count is directly before the clear button

        $.ajax({
            url: mn_report_post.ajax_url,
            type: 'POST',
            data: {
                action: 'clear_report', // You'll need to implement this action in your PHP
                nonce: mn_report_post.nonce,
                post_id: parseInt($thisButton.data('post-id'), 10)
            },
            success: function(response) {
                if (response.success) {
                    // Remove the report count and clear button
                    $reportCountSpan.remove();
                    $thisButton.remove();
                } else {
                    alert('Error while clearing reports.');
                }
            }
        });
    });
});
