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
        <?php if ($show_target): ?>
        <div class="target-band-selector">
            <label for="target-band-select"><?php _e('Target Band:', 'ielts-course-manager'); ?></label>
            <select id="target-band-select" class="target-band-select">
                <option value="5.5">5.5</option>
                <option value="6.0">6.0</option>
                <option value="6.5">6.5</option>
                <option value="7.0" selected>7.0</option>
                <option value="7.5">7.5</option>
                <option value="8.0">8.0</option>
                <option value="8.5">8.5</option>
                <option value="9.0">9.0</option>
            </select>
        </div>
        <?php endif; ?>
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
            <span id="target-band-label"><?php _e('Band 7.0 Target', 'ielts-course-manager'); ?></span>
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
    margin: 0 0 10px 0;
    font-size: 20px;
    color: #333;
}

.target-band-selector {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
}

.target-band-selector label {
    font-size: 14px;
    font-weight: 600;
    color: #555;
}

.target-band-select {
    padding: 6px 12px;
    border: 2px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    background: white;
    color: #333;
    cursor: pointer;
    transition: all 0.3s ease;
}

.target-band-select:hover {
    border-color: #FFC107;
}

.target-band-select:focus {
    outline: none;
    border-color: #FFC107;
    box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.1);
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
    var radarChart = null;
    var currentTargetBand = 7.0;
    
    // Show loading
    container.find('.skills-radar-loading').show();
    container.find('.skills-radar-wrapper, .skills-radar-legend').hide();
    
    // Load initial data
    loadSkillsData();
    
    // Handle target band change
    $('#target-band-select').on('change', function() {
        var newTargetBand = parseFloat($(this).val());
        updateTargetBand(newTargetBand);
    });
    
    function loadSkillsData() {
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
                    currentTargetBand = response.data.target_band || 7.0;
                    
                    // Set the select value
                    $('#target-band-select').val(currentTargetBand);
                    
                    // Update label
                    updateTargetBandLabel(currentTargetBand);
                    
                    // Hide loading, show chart
                    container.find('.skills-radar-loading').hide();
                    container.find('.skills-radar-wrapper, .skills-radar-legend').show();
                    
                    // Create radar chart
                    renderSkillsRadar(skillScores, showTarget, currentTargetBand);
                }
            },
            error: function() {
                container.find('.skills-radar-loading').html('<?php _e('Error loading skills data', 'ielts-course-manager'); ?>');
            }
        });
    }
    
    function updateTargetBand(newTargetBand) {
        $.ajax({
            url: ieltsCM.ajaxUrl,
            method: 'POST',
            data: {
                action: 'ielts_cm_update_target_band',
                nonce: ieltsCM.nonce,
                target_band: newTargetBand
            },
            success: function(response) {
                if (response.success) {
                    currentTargetBand = newTargetBand;
                    updateTargetBandLabel(newTargetBand);
                    updateChartTargetLine(newTargetBand);
                }
            }
        });
    }
    
    function updateTargetBandLabel(targetBand) {
        $('#target-band-label').text('<?php _e('Band', 'ielts-course-manager'); ?> ' + targetBand.toFixed(1) + ' <?php _e('Target', 'ielts-course-manager'); ?>');
    }
    
    function updateChartTargetLine(targetBand) {
        if (!radarChart || !showTarget) return;
        
        // Convert band score to percentage (approximate mapping)
        // Band 5.5 ≈ 60%, Band 6.0 ≈ 65%, Band 6.5 ≈ 70%, Band 7.0 ≈ 80%, etc.
        var percentage = bandToPercentage(targetBand);
        
        // Update the target dataset
        radarChart.data.datasets[1].data = [percentage, percentage, percentage, percentage, percentage, percentage];
        radarChart.data.datasets[1].label = '<?php _e('Band', 'ielts-course-manager'); ?> ' + targetBand.toFixed(1) + ' <?php _e('Target', 'ielts-course-manager'); ?>';
        radarChart.update();
    }
    
    function bandToPercentage(band) {
        // IELTS band to percentage mapping
        // Based on approximate skill level requirements:
        // Band 5.5-6.0: Basic competence (60-65%)
        // Band 6.5-7.0: Good competence (70-80%)
        // Band 7.5-8.0: Very good competence (85-90%)
        // Band 8.5-9.0: Expert competence (95-100%)
        var mapping = {
            5.5: 60,
            6.0: 65,
            6.5: 70,
            7.0: 80,
            7.5: 85,
            8.0: 90,
            8.5: 95,
            9.0: 100
        };
        return mapping[band] || 80;
    }
    
    function renderSkillsRadar(skillScores, showTarget, targetBand) {
        var ctx = document.getElementById('skills-radar-chart');
        if (!ctx) return;
        
        var targetPercentage = bandToPercentage(targetBand);
        
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
                label: '<?php _e('Band', 'ielts-course-manager'); ?> ' + targetBand.toFixed(1) + ' <?php _e('Target', 'ielts-course-manager'); ?>',
                data: [targetPercentage, targetPercentage, targetPercentage, targetPercentage, targetPercentage, targetPercentage],
                backgroundColor: 'rgba(255, 193, 7, 0.05)',
                borderColor: 'rgba(255, 193, 7, 0.8)',
                borderWidth: 2,
                borderDash: [5, 5],
                pointRadius: 0,
                pointHoverRadius: 0
            });
        }
        
        radarChart = new Chart(ctx, {
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
