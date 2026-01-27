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
    container.find('.skills-radar-wrapper, .skills-radar-legend, .skills-radar-scores').hide();
    
    // Load initial data
    loadSkillsData();
    
    function percentageToBand(percentage) {
        // Convert percentage to IELTS band score (rounded to whole numbers only for radar chart)
        if (percentage >= 95) return 9;
        if (percentage >= 90) return 8;
        if (percentage >= 80) return 7;
        if (percentage >= 70) return 6;
        if (percentage >= 60) return 5;
        if (percentage >= 50) return 4;
        if (percentage >= 40) return 3;
        if (percentage >= 30) return 2;
        if (percentage >= 10) return 1;
        return 0;
    }
    
    function percentageToBandHalf(percentage) {
        // Convert percentage to IELTS band score (with half bands for display under skills)
        if (percentage >= 95) return 9.0;
        if (percentage >= 90) return 8.5;
        if (percentage >= 85) return 8.0;
        if (percentage >= 80) return 7.5;
        if (percentage >= 70) return 7.0;
        if (percentage >= 65) return 6.5;
        if (percentage >= 60) return 6.0;
        if (percentage >= 55) return 5.5;
        if (percentage >= 50) return 5.0;
        if (percentage >= 45) return 4.5;
        if (percentage >= 40) return 4.0;
        if (percentage >= 35) return 3.5;
        if (percentage >= 30) return 3.0;
        if (percentage >= 25) return 2.5;
        if (percentage >= 20) return 2.0;
        if (percentage >= 15) return 1.5;
        if (percentage >= 10) return 1.0;
        return 0.5;
    }
    
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
                    
                    // Hide loading, show chart and scores
                    container.find('.skills-radar-loading').hide();
                    container.find('.skills-radar-wrapper, .skills-radar-legend, .skills-radar-scores').show();
                    
                    // Update skill band values
                    container.find('[data-skill="reading"]').text(skillScores.reading > 0 ? percentageToBandHalf(skillScores.reading).toFixed(1) : '—');
                    container.find('[data-skill="writing"]').text(skillScores.writing > 0 ? percentageToBandHalf(skillScores.writing).toFixed(1) : '—');
                    container.find('[data-skill="listening"]').text(skillScores.listening > 0 ? percentageToBandHalf(skillScores.listening).toFixed(1) : '—');
                    container.find('[data-skill="speaking"]').text(skillScores.speaking > 0 ? percentageToBandHalf(skillScores.speaking).toFixed(1) : '—');
                    container.find('[data-skill="vocabulary"]').text(skillScores.vocabulary > 0 ? percentageToBandHalf(skillScores.vocabulary).toFixed(1) : '—');
                    container.find('[data-skill="grammar"]').text(skillScores.grammar > 0 ? percentageToBandHalf(skillScores.grammar).toFixed(1) : '—');
                    
                    // Create radar chart
                    renderSkillsRadar(skillScores);
                }
            },
            error: function() {
                container.find('.skills-radar-loading').html('<?php _e('Error loading skills data', 'ielts-course-manager'); ?>');
            }
        });
    }
    
    function renderSkillsRadar(skillScores) {
        var ctx = document.getElementById('skills-radar-chart');
        if (!ctx) return;
        
        // Convert percentage scores to band scores (whole numbers for radar)
        var readingBand = percentageToBand(skillScores.reading || 0);
        var writingBand = percentageToBand(skillScores.writing || 0);
        var listeningBand = percentageToBand(skillScores.listening || 0);
        var speakingBand = percentageToBand(skillScores.speaking || 0);
        var vocabularyBand = percentageToBand(skillScores.vocabulary || 0);
        var grammarBand = percentageToBand(skillScores.grammar || 0);
        
        var datasets = [{
            label: '<?php _e('Your Band Scores', 'ielts-course-manager'); ?>',
            data: [readingBand, writingBand, listeningBand, speakingBand, vocabularyBand, grammarBand],
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
                        max: 9,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                return value;
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
                                weight: 600
                            },
                            color: '#333'
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
                                return context.dataset.label + ': Band ' + context.parsed.r;
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
