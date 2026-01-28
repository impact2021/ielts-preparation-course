<?php
/**
 * Progress Rings Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$view = isset($view) ? $view : 'daily';
?>

<div class="ielts-progress-rings-container" data-view="<?php echo esc_attr($view); ?>">
    <div class="progress-rings-header">
        <h3 class="progress-rings-title">
            <?php 
            if ($view === 'daily') {
                _e("Today's Progress", 'ielts-course-manager');
            } elseif ($view === 'weekly') {
                _e("This Week's Progress", 'ielts-course-manager');
            } else {
                _e("This Month's Progress", 'ielts-course-manager');
            }
            ?>
        </h3>
    </div>
    
    <div class="progress-rings-wrapper">
        <svg class="progress-rings-svg" viewBox="0 0 200 200" width="300" height="300">
            <!-- Background circles -->
            <circle cx="100" cy="100" r="85" fill="none" stroke="#e0e0e0" stroke-width="14"/>
            <circle cx="100" cy="100" r="65" fill="none" stroke="#e0e0e0" stroke-width="14"/>
            <circle cx="100" cy="100" r="45" fill="none" stroke="#e0e0e0" stroke-width="14"/>
            
            <!-- Outer ring: Exercises (green) -->
            <circle class="exercises-ring" cx="100" cy="100" r="85" 
                    fill="none" 
                    stroke="#4CAF50" 
                    stroke-width="14"
                    stroke-linecap="round"
                    stroke-dasharray="534" 
                    stroke-dashoffset="534"
                    transform="rotate(-90 100 100)"
                    style="filter: drop-shadow(0 0 3px #4CAF50); transition: stroke-dashoffset 1s ease-in-out;"/>
            
            <!-- Middle ring: Study time (blue) -->
            <circle class="time-ring" cx="100" cy="100" r="65" 
                    fill="none" 
                    stroke="#2196F3" 
                    stroke-width="14"
                    stroke-linecap="round"
                    stroke-dasharray="408" 
                    stroke-dashoffset="408"
                    transform="rotate(-90 100 100)"
                    style="filter: drop-shadow(0 0 3px #2196F3); transition: stroke-dashoffset 1s ease-in-out;"/>
            
            <!-- Inner ring: Perfect scores (orange) -->
            <circle class="perfect-ring" cx="100" cy="100" r="45" 
                    fill="none" 
                    stroke="#FF9800" 
                    stroke-width="14"
                    stroke-linecap="round"
                    stroke-dasharray="283" 
                    stroke-dashoffset="283"
                    transform="rotate(-90 100 100)"
                    style="filter: drop-shadow(0 0 3px #FF9800); transition: stroke-dashoffset 1s ease-in-out;"/>
        </svg>
        <div class="progress-rings-center-icon">🎯</div>
    </div>
    
    <div class="progress-rings-stats">
        <div class="ring-stat">
            <span class="ring-color" style="background: #4CAF50;"></span>
            <span class="ring-label"><?php _e('Exercises', 'ielts-course-manager'); ?></span>
            <span class="ring-value exercises-value">-/-</span>
        </div>
        <div class="ring-stat">
            <span class="ring-color" style="background: #2196F3;"></span>
            <span class="ring-label"><?php _e('Study Time', 'ielts-course-manager'); ?></span>
            <span class="ring-value time-value">- min</span>
        </div>
        <div class="ring-stat">
            <span class="ring-color" style="background: #FF9800;"></span>
            <span class="ring-label"><?php _e('Perfect', 'ielts-course-manager'); ?></span>
            <span class="ring-value perfect-value">-/-</span>
        </div>
    </div>
    
    <div class="progress-rings-streak">
        <strong class="streak-value">🔥 - Day Streak!</strong>
        <div><?php _e("Don't break the chain!", 'ielts-course-manager'); ?></div>
    </div>
    
    <div class="progress-rings-loading"><?php _e('Loading...', 'ielts-course-manager'); ?></div>
</div>

<style>
.ielts-progress-rings-container {
    background: transparent;
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    max-width: 400px;
    margin: 0 auto;
}

.progress-rings-header {
    margin-bottom: 20px;
}

.progress-rings-title {
    margin: 0;
    font-size: 20px;
    color: #333;
}

.progress-rings-wrapper {
    position: relative;
    width: 300px;
    height: 300px;
    margin: 0 auto 20px;
}

.progress-rings-svg {
    width: 100%;
    height: 100%;
}

.progress-rings-center-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 48px;
    pointer-events: none;
}

.progress-rings-stats {
    display: flex;
    justify-content: space-around;
    gap: 15px;
    margin-bottom: 15px;
}

.ring-stat {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 5px;
}

.ring-color {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.ring-label {
    font-size: 12px;
    color: #666;
    font-weight: 500;
}

.ring-value {
    font-size: 16px;
    font-weight: bold;
    color: #333;
}

.progress-rings-streak {
    background: transparent;
    color: #333;
    padding: 15px;
    border-radius: 10px;
    margin-top: 15px;
}

.progress-rings-streak strong {
    font-size: 20px;
    display: block;
    margin-bottom: 5px;
}

.progress-rings-loading {
    text-align: center;
    padding: 20px;
    color: #666;
}

@media (max-width: 500px) {
    .ielts-progress-rings-container {
        padding: 20px;
    }
    
    .progress-rings-wrapper {
        width: 250px;
        height: 250px;
    }
    
    .progress-rings-center-icon {
        font-size: 36px;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Handle each progress rings container separately
    $('.ielts-progress-rings-container').each(function() {
        var container = $(this);
        var view = container.data('view');
        
        // Show loading
        container.find('.progress-rings-loading').show();
        container.find('.progress-rings-wrapper, .progress-rings-stats, .progress-rings-streak').hide();
        
        $.ajax({
            url: ieltsCM.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ielts_cm_get_progress_rings_data',
                nonce: ieltsCM.nonce,
                view: view
            },
            success: function(response) {
                if (response.success) {
                    var data = response.data;
                    
                    // Hide loading, show content
                    container.find('.progress-rings-loading').hide();
                    container.find('.progress-rings-wrapper, .progress-rings-stats, .progress-rings-streak').show();
                    
                    // Update rings based on view
                    if (view === 'daily') {
                        updateRing(container, 'exercises-ring', data.exercises_today, data.daily_exercise_goal);
                        updateRing(container, 'time-ring', data.study_time_today, data.daily_time_goal);
                        updateRing(container, 'perfect-ring', data.perfect_scores_today, data.daily_perfect_goal);
                        
                        container.find('.exercises-value').text(data.exercises_today + '/' + data.daily_exercise_goal);
                        container.find('.time-value').text(data.study_time_today + '/' + data.daily_time_goal + ' min');
                        container.find('.perfect-value').text(data.perfect_scores_today + '/' + data.daily_perfect_goal);
                        container.find('.streak-value').text('🔥 ' + data.streak_days + ' Day Streak!');
                    } else if (view === 'weekly') {
                        updateRing(container, 'exercises-ring', data.exercises_week, data.weekly_exercise_goal);
                        updateRing(container, 'time-ring', data.study_time_week, data.weekly_time_goal);
                        updateRing(container, 'perfect-ring', data.perfect_scores_week, data.weekly_perfect_goal);
                        
                        container.find('.exercises-value').text(data.exercises_week + '/' + data.weekly_exercise_goal);
                        container.find('.time-value').text(data.study_time_week + '/' + data.weekly_time_goal + ' min');
                        container.find('.perfect-value').text(data.perfect_scores_week + '/' + data.weekly_perfect_goal);
                        container.find('.progress-rings-streak').hide();
                    } else {
                        updateRing(container, 'exercises-ring', data.exercises_month, data.monthly_exercise_goal);
                        updateRing(container, 'time-ring', data.study_time_month, data.monthly_time_goal);
                        updateRing(container, 'perfect-ring', data.perfect_scores_month, data.monthly_perfect_goal);
                        
                        container.find('.exercises-value').text(data.exercises_month + '/' + data.monthly_exercise_goal);
                        container.find('.time-value').text(data.study_time_month + '/' + data.monthly_time_goal + ' min');
                        container.find('.perfect-value').text(data.perfect_scores_month + '/' + data.monthly_perfect_goal);
                        container.find('.progress-rings-streak').hide();
                    }
                }
            },
            error: function() {
                container.find('.progress-rings-loading').html('<?php _e('Error loading progress data', 'ielts-course-manager'); ?>');
            }
        });
    });
    
    function updateRing(container, ringClass, current, goal) {
        var ring = container.find('.' + ringClass)[0];
        if (!ring) return;
        
        var radius = parseFloat(ring.getAttribute('r'));
        var circumference = 2 * Math.PI * radius;
        var percentage = Math.min((current / goal) * 100, 100);
        var offset = circumference - (percentage / 100) * circumference;
        
        ring.style.strokeDashoffset = offset;
        
        if (percentage >= 100) {
            ring.style.filter = 'drop-shadow(0 0 10px currentColor)';
        }
    }
});
</script>
