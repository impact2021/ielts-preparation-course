<?php
/**
 * Skills Radar Chart Template
 */

if (!defined('ABSPATH')) {
    exit;
}

$user_id = get_current_user_id();
$show_target = isset($show_target) ? $show_target : true;
$height = isset($height) ? $height : 400;
?>

<div class="ielts-skills-radar-container">
    <div class="skills-radar-header">
        <h3 class="skills-radar-title"><?php _e('Your IELTS Skills Profile', 'ielts-course-manager'); ?></h3>
    </div>
    
    <div class="skills-radar-wrapper">
        <canvas id="skills-radar-chart" height="<?php echo esc_attr($height); ?>"></canvas>
    </div>
    
    <div class="skills-radar-legend">
        <span class="legend-item">
            <span class="legend-line" style="background: #2196F3;"></span>
            <?php _e('Your Proficiency', 'ielts-course-manager'); ?>
        </span>
        <?php if ($show_target): ?>
        <span class="legend-item">
            <span class="legend-line legend-dashed" style="background: #FFC107;"></span>
            <?php _e('Band 7 Target', 'ielts-course-manager'); ?>
        </span>
        <?php endif; ?>
    </div>
    
    <div class="skills-radar-loading"><?php _e('Loading skills data...', 'ielts-course-manager'); ?></div>
</div>

<style>
.ielts-skills-radar-container {
    background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
    border-radius: 15px;
    padding: 30px;
    text-align: center;
    max-width: 600px;
    margin: 0 auto;
}

.skills-radar-header {
    margin-bottom: 20px;
}

.skills-radar-title {
    margin: 0;
    font-size: 20px;
    color: #333;
}

.skills-radar-wrapper {
    position: relative;
    margin: 0 auto 20px;
}

.skills-radar-legend {
    display: flex;
    justify-content: center;
    gap: 20px;
    flex-wrap: wrap;
    font-size: 14px;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 8px;
}

.legend-line {
    width: 30px;
    height: 3px;
    display: inline-block;
}

.legend-line.legend-dashed {
    background-image: repeating-linear-gradient(
        to right,
        currentColor,
        currentColor 5px,
        transparent 5px,
        transparent 10px
    );
}

.skills-radar-loading {
    text-align: center;
    padding: 40px 20px;
    color: #666;
    font-size: 16px;
}

@media (max-width: 500px) {
    .ielts-skills-radar-container {
        padding: 20px;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
jQuery(document).ready(function($) {
    var container = $('.ielts-skills-radar-container');
    var showTarget = <?php echo $show_target ? 'true' : 'false'; ?>;
    
    // Show loading
    container.find('.skills-radar-loading').show();
    container.find('.skills-radar-wrapper, .skills-radar-legend').hide();
    
    $.ajax({
        url: ieltsCM.ajaxUrl,
        method: 'POST',
        data: {
            action: 'ielts_cm_get_skills_radar_data',
            nonce: ieltsCM.nonce
        },
        success: function(response) {
            if (response.success) {
                var skillScores = response.data.skill_scores;
                
                // Hide loading, show chart
                container.find('.skills-radar-loading').hide();
                container.find('.skills-radar-wrapper, .skills-radar-legend').show();
                
                // Create radar chart
                renderSkillsRadar(skillScores, showTarget);
            }
        },
        error: function() {
            container.find('.skills-radar-loading').html('<?php _e('Error loading skills data', 'ielts-course-manager'); ?>');
        }
    });
    
    function renderSkillsRadar(skillScores, showTarget) {
        var ctx = document.getElementById('skills-radar-chart');
        if (!ctx) return;
        
        var datasets = [{
            label: '<?php _e('Your Proficiency', 'ielts-course-manager'); ?>',
            data: [
                skillScores.reading || 0,
                skillScores.writing || 0,
                skillScores.listening || 0,
                skillScores.speaking || 0,
                skillScores.vocabulary || 0,
                skillScores.grammar || 0
            ],
            backgroundColor: 'rgba(33, 150, 243, 0.2)',
            borderColor: 'rgba(33, 150, 243, 1)',
            borderWidth: 3,
            pointBackgroundColor: 'rgba(33, 150, 243, 1)',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: 'rgba(33, 150, 243, 1)',
            pointRadius: 5,
            pointHoverRadius: 7
        }];
        
        if (showTarget) {
            datasets.push({
                label: '<?php _e('Band 7 Target', 'ielts-course-manager'); ?>',
                data: [80, 80, 80, 80, 80, 80],
                backgroundColor: 'rgba(255, 193, 7, 0.05)',
                borderColor: 'rgba(255, 193, 7, 0.8)',
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 0,
                pointHoverRadius: 0
            });
        }
        
        new Chart(ctx, {
            type: 'radar',
            data: {
                labels: [
                    '<?php _e('Reading', 'ielts-course-manager'); ?>',
                    '<?php _e('Writing', 'ielts-course-manager'); ?>',
                    '<?php _e('Listening', 'ielts-course-manager'); ?>',
                    '<?php _e('Speaking', 'ielts-course-manager'); ?>',
                    '<?php _e('Vocabulary', 'ielts-course-manager'); ?>',
                    '<?php _e('Grammar', 'ielts-course-manager'); ?>'
                ],
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    r: {
                        min: 0,
                        max: 100,
                        ticks: {
                            stepSize: 20,
                            callback: function(value) {
                                return value + '%';
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        angleLines: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        pointLabels: {
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.dataset.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                label += Math.round(context.parsed.r) + '%';
                                return label;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
