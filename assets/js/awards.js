/**
 * Awards functionality
 */

(function($) {
    'use strict';
    
    var awardsData = null;
    var currentFilter = 'all';
    
    $(document).ready(function() {
        // Don't show award notifications on the registration page
        // Users have already gotten to that point and shouldn't be distracted
        var isRegistrationPage = $('.ielts-registration-form').length > 0 || $('form[name="ielts_registration_form"]').length > 0;
        
        // Load awards
        loadAwards(isRegistrationPage);
        
        // Tab switching
        $('.awards-tab').on('click', function() {
            $('.awards-tab').removeClass('active');
            $(this).addClass('active');
            currentFilter = $(this).data('tab');
            renderAwards();
        });
    });
    
    function loadAwards(skipNotifications) {
        $.ajax({
            url: ieltsAwardsConfig.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ielts_cm_get_user_awards',
                nonce: ieltsAwardsConfig.nonce
            },
            success: function(response) {
                if (response.success) {
                    awardsData = response.data;
                    renderAwards();
                    
                    // Show notifications for new awards, unless we're on the registration page
                    if (!skipNotifications && awardsData.new && awardsData.new.length > 0) {
                        showNewAwardNotifications(awardsData.new);
                    }
                } else {
                    $('#ielts-awards-wall').html('<p>Error loading awards.</p>');
                }
            },
            error: function() {
                $('#ielts-awards-wall').html('<p>Error loading awards.</p>');
            }
        });
    }
    
    function renderAwards() {
        if (!awardsData) return;
        
        var container = $('#ielts-awards-wall');
        container.empty();
        
        var allAwards = awardsData.all;
        var earnedAwards = awardsData.earned;
        var earnedIds = earnedAwards.map(function(a) { return a.award_id; });
        
        // Filter awards
        var filteredAwards = allAwards;
        if (currentFilter !== 'all') {
            filteredAwards = allAwards.filter(function(award) {
                return award.type === currentFilter;
            });
        }
        
        // Render each award
        filteredAwards.forEach(function(award) {
            var isEarned = earnedIds.indexOf(award.id) !== -1;
            var earnedDate = null;
            
            if (isEarned) {
                var earnedAward = earnedAwards.find(function(a) { return a.award_id === award.id; });
                if (earnedAward) {
                    earnedDate = earnedAward.earned_date;
                }
            }
            
            var awardHtml = createAwardElement(award, isEarned, earnedDate);
            container.append(awardHtml);
        });
    }
    
    function createAwardElement(award, isEarned, earnedDate) {
        var statusClass = isEarned ? 'earned' : 'locked';
        var icon = createAwardIcon(award.type);
        var dateHtml = '';
        
        if (isEarned && earnedDate) {
            var date = new Date(earnedDate);
            dateHtml = '<div class="award-date">Earned: ' + formatDate(date) + '</div>';
        }
        
        return $('<div>')
            .addClass('award-item')
            .addClass(statusClass)
            .attr('data-award-id', award.id)
            .html(
                icon +
                '<div class="award-name">' + escapeHtml(award.name) + '</div>' +
                '<div class="award-description">' + escapeHtml(award.description) + '</div>' +
                dateHtml
            );
    }
    
    function createAwardIcon(type) {
        return '<div class="award-icon ' + type + '"></div>';
    }
    
    function showNewAwardNotifications(newAwardIds) {
        // Show notifications one by one with delay
        var delay = 0;
        newAwardIds.forEach(function(awardId, index) {
            setTimeout(function() {
                showAwardNotification(awardId);
            }, delay);
            delay += 4000; // 4 seconds between notifications
        });
    }
    
    function showAwardNotification(awardId) {
        if (!awardsData) return;
        
        var award = awardsData.all.find(function(a) { return a.id === awardId; });
        if (!award) return;
        
        var notification = $('#ielts-award-notification');
        var icon = createAwardIcon(award.type);
        
        notification.find('.award-notification-icon').html(icon);
        notification.find('.award-notification-name').text(award.name);
        notification.find('.award-notification-description').text(award.description);
        
        notification.removeClass('slide-out').show();
        
        // Auto hide after 3 seconds
        setTimeout(function() {
            notification.addClass('slide-out');
            setTimeout(function() {
                notification.hide();
            }, 500);
        }, 3000);
    }
    
    function formatDate(date) {
        var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return months[date.getMonth()] + ' ' + date.getDate() + ', ' + date.getFullYear();
    }
    
    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Expose function to show award notifications from award objects (not just IDs)
    // This allows other scripts to trigger notifications after quiz completion
    window.IELTSAwards = window.IELTSAwards || {};
    window.IELTSAwards.showAwardNotifications = function(awards) {
        if (!awards || !Array.isArray(awards) || awards.length === 0) {
            return;
        }
        
        var delay = 0;
        awards.forEach(function(award) {
            setTimeout(function() {
                showAwardNotificationFromObject(award);
            }, delay);
            delay += 4000; // 4 seconds between notifications
        });
    };
    
    function showAwardNotificationFromObject(award) {
        if (!award || !award.id) return;
        
        var notification = $('#ielts-award-notification');
        var icon = createAwardIcon(award.type);
        
        notification.find('.award-notification-icon').html(icon);
        notification.find('.award-notification-name').text(award.name);
        notification.find('.award-notification-description').text(award.description);
        
        notification.removeClass('slide-out').show();
        
        // Auto hide after 3 seconds
        setTimeout(function() {
            notification.addClass('slide-out');
            setTimeout(function() {
                notification.hide();
            }, 500);
        }, 3000);
    }
    
})(jQuery);
