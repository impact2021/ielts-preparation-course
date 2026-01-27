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
            <?php _e('Your Band Scores', 'ielts-course-manager'); ?>
        </span>
    </div>
    
    <div class="skills-radar-scores">
        <div class="skill-score-item">
            <div class="skill-label"><?php _e('Reading', 'ielts-course-manager'); ?></div>
            <div class="skill-band-value" data-skill="reading">—</div>
        </div>
        <div class="skill-score-item">
            <div class="skill-label"><?php _e('Writing', 'ielts-course-manager'); ?></div>
            <div class="skill-band-value" data-skill="writing">—</div>
        </div>
        <div class="skill-score-item">
            <div class="skill-label"><?php _e('Listening', 'ielts-course-manager'); ?></div>
            <div class="skill-band-value" data-skill="listening">—</div>
        </div>
        <div class="skill-score-item">
            <div class="skill-label"><?php _e('Speaking', 'ielts-course-manager'); ?></div>
            <div class="skill-band-value" data-skill="speaking">—</div>
        </div>
        <div class="skill-score-item">
            <div class="skill-label"><?php _e('Vocabulary', 'ielts-course-manager'); ?></div>
            <div class="skill-band-value" data-skill="vocabulary">—</div>
            <div class="skill-disclaimer"><?php _e('Not independently assessed in IELTS', 'ielts-course-manager'); ?></div>
        </div>
        <div class="skill-score-item">
            <div class="skill-label"><?php _e('Grammar', 'ielts-course-manager'); ?></div>
            <div class="skill-band-value" data-skill="grammar">—</div>
            <div class="skill-disclaimer"><?php _e('Not independently assessed in IELTS', 'ielts-course-manager'); ?></div>
        </div>
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

.skills-radar-scores {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 15px;
    margin-top: 20px;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.skill-score-item {
    text-align: center;
    padding: 10px;
}

.skill-label {
    font-size: 14px;
    font-weight: 600;
    color: #555;
    margin-bottom: 5px;
}

.skill-band-value {
    font-size: 24px;
    font-weight: bold;
    color: #2196F3;
    margin-bottom: 3px;
}

.skill-disclaimer {
    font-size: 10px;
    color: #999;
    font-style: italic;
    margin-top: 3px;
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
