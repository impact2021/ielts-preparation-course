/* global ieltsSpeakingExercise, jQuery */
jQuery(document).ready(function ($) {
    'use strict';

    if (!$('#ielts-speaking-exercise-app').length) return;

    // Stop speech immediately if the page is left, reloaded, or hidden
    window.addEventListener('beforeunload', function () {
        if (window.speechSynthesis) window.speechSynthesis.cancel();
    });
    document.addEventListener('visibilitychange', function () {
        if (document.hidden && window.speechSynthesis) window.speechSynthesis.cancel();
    });

    if (typeof ieltsSpeakingExercise === 'undefined') {
        $('.ielts-gender-btn').on('click', function () {
            $(this).addClass('selected');
            $('#ielts-gender-choice').hide();
            $('#ielts-mic-check').show();
        });
        return;
    }

    var cfg   = ieltsSpeakingExercise;
    var synth = window.speechSynthesis;
    var voice = null;
    var GENDER = 'female'; // set by student choice

    var P1_QUESTIONS = cfg.p1Questions || [];
    var P2_CUECARD   = cfg.p2Cuecard  || '';
    var P3_QUESTIONS = cfg.p3Questions || [];

    var currentPart    = 0;
    var mediaRecorder  = null;
    var audioChunks    = [];
    var recording      = false;
    var stream         = null;
    var countdown      = null;
    var progressTimers = [];
    var pendingTx      = 0;
    var transcripts    = {};
    var assessDone     = false;
    var p3AdaptiveQ    = [];
    var p3QIdx         = 0;

    // Silence detection — one shared context/analyser for entire session
    var audioCtx       = null;
    var sharedAnalyser = null;
    var sharedBuf      = null;
    var silenceTimer   = null;
    var SILENCE_SECS   = 5;
    var SILENCE_THRESH = 10; // overwritten by calibration

    var MAX_P1    = 30;
    var MAX_P2    = 120;
    var MAX_P3    = 60;
    var PREP_TIME = 60;

    document.documentElement.style.setProperty('--ielts-speaking-color', cfg.progressColor || '#E56C0A');

    var $genderChoice   = $('#ielts-gender-choice');
    var $micCheck       = $('#ielts-mic-check');
    var $interview      = $('#ielts-speaking-interview');
    var $results        = $('#ielts-speaking-results');
    var $finishBtn      = $('#ielts-finish-btn');
    var $skipPrepBtn    = $('#ielts-skip-prep-btn');
    var $countdownWrap  = $('#ielts-countdown-wrap');
    var $countdownBar   = $('#ielts-countdown-bar');
    var $countdownNum   = $('#ielts-countdown');
    var $partLabel      = $('#ielts-part-label');
    var $p2Cuecard      = $('#ielts-p2-cuecard');
    var $p2NotesWrap    = $('#ielts-p2-layout');
    var $progressEl     = $('#ielts-speaking-progress');
    var $status         = $('#ielts-speak-status');
    var $currentQ       = $('#ielts-current-question');

    // ─── Voice ────────────────────────────────────────────────────────
    function loadVoice() {
        var voices = synth.getVoices();
        if (!voices.length) return;

        // Explicitly avoid known robotic voices
        var avoid = ['Microsoft David', 'Microsoft Zira', 'Microsoft Mark', 'Alex', 'Fred', 'Trinoids', 'Zarvox', 'Cellos', 'Bells', 'Boing', 'Bubbles', 'Deranged', 'Good News', 'Hysterical', 'Junior', 'Kathy', 'Organ', 'Princess', 'Ralph', 'Whisper'];

        // Preferred by name — neural/natural voices first
        var femaleNames = ['Google UK English Female', 'Microsoft Libby Online (Natural) - English (United Kingdom)', 'Microsoft Sonia Online (Natural) - English (United Kingdom)', 'Microsoft Hazel Desktop - English (Great Britain)', 'Microsoft Susan', 'Samantha', 'Karen', 'Victoria', 'Moira', 'Fiona'];
        var maleNames   = ['Google UK English Male', 'Microsoft Ryan Online (Natural) - English (United Kingdom)', 'Microsoft George Desktop - English (Great Britain)', 'Microsoft Stefan', 'Daniel', 'Gordon', 'Lee'];
        var preferred   = GENDER === 'male' ? maleNames : femaleNames;

        // 1. Try preferred list in order
        for (var i = 0; i < preferred.length; i++) {
            for (var j = 0; j < voices.length; j++) {
                if (voices[j].name === preferred[i]) { voice = voices[j]; return; }
            }
        }

        // 2. Any en-GB voice not in avoid list, prefer ones with 'online' or 'natural' in name
        var enGB = voices.filter(function (v) {
            return v.lang === 'en-GB' && avoid.indexOf(v.name) === -1;
        });
        enGB.sort(function (a, b) {
            var aGood = /online|natural/i.test(a.name) ? 0 : 1;
            var bGood = /online|natural/i.test(b.name) ? 0 : 1;
            return aGood - bGood;
        });
        if (enGB.length) { voice = enGB[0]; return; }

        // 3. Any en-US voice not in avoid list
        var enUS = voices.filter(function (v) {
            return v.lang.indexOf('en') === 0 && avoid.indexOf(v.name) === -1;
        });
        if (enUS.length) { voice = enUS[0]; return; }

        // 4. Absolute fallback
        if (voices.length) voice = voices[0];
    }

    var speechGen = 0; // incremented on every speak() call; callbacks check they're still current

    function speak(text, onEnd) {
        if (!synth) { if (onEnd) onEnd(); return; }
        speechGen++;
        var myGen = speechGen;
        synth.cancel();
        var utt    = new SpeechSynthesisUtterance(text);
        utt.rate   = 0.92;
        utt.pitch  = GENDER === 'male' ? 0.85 : 1.05;
        utt.volume = 1.0;
        if (voice) utt.voice = voice;
        utt.onend   = function () { if (speechGen === myGen && onEnd) onEnd(); };
        utt.onerror = function (e) { if (e.error !== 'interrupted' && speechGen === myGen && onEnd) onEnd(); };
        synth.speak(utt);
    }

    function cancelSpeak() {
        speechGen++; // invalidate any pending callbacks
        if (synth) synth.cancel();
    }

    // ─── Question display (fade in/out) ───────────────────────────────
    function showQuestion(text, className) {
        $currentQ.fadeOut(250, function () {
            $currentQ.removeClass().addClass('ielts-current-question');
            if (className) $currentQ.addClass(className);
            $currentQ.text(text).fadeIn(300);
        });
    }

    function clearQuestion() {
        $currentQ.fadeOut(200, function () { $currentQ.text(''); });
    }

    // ─── Progress dots ────────────────────────────────────────────────
    function buildProgress(total, current) {
        $progressEl.empty();
        for (var i = 0; i < total; i++) {
            var cls = 'ielts-speaking-prog-dot' + (i < current ? ' done' : i === current ? ' active' : '');
            $progressEl.append('<div class="' + cls + '"></div>');
        }
    }

    // ─── Gender choice ────────────────────────────────────────────────
    $('.ielts-gender-btn').on('click', function () {
        GENDER = $(this).data('gender');
        $('.ielts-gender-btn').removeClass('selected');
        $(this).addClass('selected');
        $genderChoice.hide();
        $micCheck.show();
        if (synth.onvoiceschanged !== undefined) synth.onvoiceschanged = loadVoice;
        loadVoice();
        initMicCheck();
    });

    // ─── Mic check ────────────────────────────────────────────────────
    function initMicCheck() {
        var $start   = $('#ielts-mic-start');
        var $play    = $('#ielts-mic-play');
        var $confirm = $('#ielts-mic-confirm');
        var $micSt   = $('#ielts-mic-status');
        var chunks = [], recorder = null, micStream = null, blob = null, audio = null;

        $start.off('click').on('click', function () {
            $micSt.text('Recording for 5 seconds...');
            $start.prop('disabled', true);
            chunks = [];
            navigator.mediaDevices.getUserMedia({ audio: true }).then(function (s) {
                micStream = s;
                recorder  = new MediaRecorder(s);
                recorder.ondataavailable = function (e) { if (e.data.size > 0) chunks.push(e.data); };
                recorder.onstop = function () {
                    blob = new Blob(chunks, { type: 'audio/webm' });
                    // Don't stop micStream tracks — keep stream alive for calibration
                    $micSt.text('Done. Play it back to confirm your mic is working.');
                    $play.prop('disabled', false);
                    $confirm.prop('disabled', false);
                };
                recorder.start();
                setTimeout(function () { recorder.stop(); }, 5000);
            }).catch(function (err) {
                $micSt.text(err.name === 'NotAllowedError' ? 'Mic access denied — please allow microphone access and reload.' : 'Mic error: ' + err.message);
                $start.prop('disabled', false);
            });
        });

        $play.off('click').on('click', function () {
            if (!blob) return;
            if (audio) audio.pause();
            audio = new Audio(URL.createObjectURL(blob));
            audio.play();
            $micSt.text('Playing back...');
            audio.onended = function () { $micSt.text('Sounds good? Click Confirm to start the test.'); };
        });

        $confirm.off('click').on('click', function () {
            var clickCtx = new (window.AudioContext || window.webkitAudioContext)();
            var existingStream = (micStream && micStream.active) ? micStream : null;

            function doCalibration(s) {
                stream = s;
                $micCheck.hide();
                // Resume AudioContext — required on some Chrome versions even in click handlers
                // Connect stream only after context is guaranteed running
                if (clickCtx.state === 'suspended') {
                    clickCtx.resume().then(function () {
                        startCalibration(s, clickCtx);
                    });
                } else {
                    startCalibration(s, clickCtx);
                }
            }

            if (existingStream) {
                doCalibration(existingStream);
            } else {
                navigator.mediaDevices.getUserMedia({ audio: true }).then(doCalibration).catch(function () {
                    clickCtx.close();
                    $micCheck.hide();
                    $interview.show();
                    startTest();
                });
            }
        });
    }

    // ─── Noise calibration ────────────────────────────────────────────
    function startCalibration(s, clickCtx) {
        var $cal      = $('#ielts-noise-cal');
        var $slider   = $('#ielts-noise-slider');
        var $hint     = $('#ielts-cal-hint');
        var $fillRed  = $('#ielts-level-fill-red');
        var $fillGreen= $('#ielts-level-fill-green');
        var $thresh   = $('#ielts-level-threshold');
        var $confirm  = $('#ielts-cal-confirm');
        var calCtx    = clickCtx;
        var calTimer  = null, bgTimer = null;
        var METER_MAX = 100;
        var peakSeen  = 0;

        $cal.show();

        function threshPct() { return Math.min(100, (SILENCE_THRESH / METER_MAX) * 100); }

        function updateThresholdLine() {
            var tPct = threshPct();
            $thresh.css('bottom', tPct + '%');
            $fillGreen.css('bottom', tPct + '%');
        }

        function updateMeter(level) {
            if (level > peakSeen) {
                peakSeen = level;
                METER_MAX = Math.max(METER_MAX, peakSeen * 1.3);
                updateThresholdLine();
            }
            var levelPct = Math.min(100, (level / METER_MAX) * 100);
            var tPct     = threshPct();
            if (levelPct <= tPct) {
                $fillRed.css('height', levelPct + '%');
                $fillGreen.css({ height: '0%', bottom: tPct + '%' });
            } else {
                $fillRed.css('height', tPct + '%');
                $fillGreen.css({ height: (levelPct - tPct) + '%', bottom: tPct + '%' });
            }
        }

        try {
            audioCtx = calCtx; // store globally — reused for silence detection throughout test
            sharedAnalyser = calCtx.createAnalyser();
            sharedAnalyser.fftSize = 256;
            calCtx.createMediaStreamSource(s).connect(sharedAnalyser);
            sharedBuf = new Uint8Array(sharedAnalyser.frequencyBinCount);

            function rawLevel() {
                sharedAnalyser.getByteFrequencyData(sharedBuf);
                var sum = 0;
                for (var i = 0; i < sharedBuf.length; i++) sum += sharedBuf[i];
                return sum / sharedBuf.length;
            }

            // Phase 1: 2s silent — measure background, update bar live
            var bgSamples = [];
            bgTimer = setInterval(function () {
                var lvl = rawLevel();
                bgSamples.push(lvl);
                updateMeter(lvl);
                if (bgSamples.length >= 20) {
                    clearInterval(bgTimer);
                    var bgAvg = bgSamples.reduce(function (a, b) { return a + b; }, 0) / bgSamples.length;
                    SILENCE_THRESH = Math.max(bgAvg * 3, bgAvg + 0.5);
                    updateThresholdLine();
                    $hint.html('Now <strong>speak normally</strong> — the bar should turn <strong style="color:#16a34a;">green</strong>. Drag the slider to adjust if needed.');
                }
            }, 100);

            // Slider: 0-100 maps to 0-METER_MAX as a percentage
            $slider.on('input', function () {
                var pct = parseFloat($(this).val()) / 100;
                SILENCE_THRESH = pct * METER_MAX;
                updateThresholdLine();
            });

            updateThresholdLine();

            calTimer = setInterval(function () {
                updateMeter(rawLevel());
            }, 50);

        } catch(e) { $hint.text('Microphone level metering unavailable: ' + e.message); }

        $confirm.off('click').on('click', function () {
            if (bgTimer)  clearInterval(bgTimer);
            if (calTimer) clearInterval(calTimer);
            // Do NOT close calCtx — it's the shared audioCtx used throughout the test
            liveMeterMax = Math.max(peakSeen * 1.3, 10);
            $cal.hide();
            $interview.show();
            // Run meter continuously for entire test, not just during recording
            setInterval(function () {
                if (sharedAnalyser) {
                    sharedAnalyser.getByteFrequencyData(sharedBuf);
                    var sum = 0;
                    for (var i = 0; i < sharedBuf.length; i++) sum += sharedBuf[i];
                    updateLiveMeter(sum / sharedBuf.length);
                }
            }, 80);
            startTest();
        });
    }

    // ─── Live meter during test ───────────────────────────────────────
    var liveMeterMax = 100;
    function updateLiveMeter(level) {
        if (level > liveMeterMax) liveMeterMax = level * 1.3;
        var pct  = Math.min(100, (level / liveMeterMax) * 100);
        var tPct = Math.min(100, (SILENCE_THRESH / liveMeterMax) * 100);
        var $r    = $('#ielts-live-fill-red');
        var $g    = $('#ielts-live-fill-green');
        var $line = $('#ielts-live-threshold');
        if (!$r.length) return;
        $line.css('bottom', tPct + '%');
        if (pct <= tPct) {
            $r.css('height', pct + '%');
            $g.css({ height: '0%', bottom: tPct + '%' });
        } else {
            $r.css('height', tPct + '%');
            $g.css({ height: (pct - tPct) + '%', bottom: tPct + '%' });
        }
    }

    // ─── Test flow ────────────────────────────────────────────────────
    function startTest() {
        startPart1();
    }

    // ─── PART 1 ───────────────────────────────────────────────────────
    function startPart1() {
        currentPart = 1;
        $partLabel.text('Part 1 — Interview').show();
        buildProgress(P1_QUESTIONS.length, 0);
        speak("In this first part, I'd like to ask you some questions about yourself.", function () {
            setTimeout(function () { askP1(0); }, 400);
        });
    }

    function askP1(idx) {
        if (idx >= P1_QUESTIONS.length) { endPart1(); return; }
        buildProgress(P1_QUESTIONS.length, idx);
        var q = P1_QUESTIONS[idx];
        showQuestion(q, 'ielts-q-part1');
        $finishBtn.prop('disabled', true).show();
        $skipPrepBtn.hide();
        speak(q, function () {
            setTimeout(function () { startRecording('p1_' + idx, MAX_P1, function () { askP1(idx + 1); }); }, 800);
        });
    }

    function endPart1() {
        clearQuestion();
        speak('Thank you. Now we\'ll move on to Part 2.', function () {
            setTimeout(startPart2, 500);
        });
    }

    // ─── PART 2 ───────────────────────────────────────────────────────
    function startPart2() {
        currentPart = 2;
        $partLabel.text('Part 2 — Individual Long Turn');
        $progressEl.empty();
        $finishBtn.hide();
        $skipPrepBtn.show().prop('disabled', false).off('click').on('click', function () {
            cancelSpeak();
            clearCountdown();
            $status.text('');
            beginP2Speaking();
        });
        var examinerText = "Here is your topic. You have one minute to prepare. You can make notes if you wish. Then I'd like you to talk about it for up to two minutes.";
        showQuestion(examinerText, 'ielts-q-part2');
        $p2NotesWrap.show();
        $status.text('Preparation time — 1 minute. Make notes below.');
        speak("Here is your topic. You have one minute to prepare. You can make notes if you wish.", function () {
            startCountdown(PREP_TIME, function (secsLeft) {
                $status.text('Preparation: ' + secsLeft + 's remaining — or click Skip to start speaking now.');
            }, function () {
                beginP2Speaking();
            }, 'prep');
        });
    }

    function beginP2Speaking() {
        clearCountdown();
        $skipPrepBtn.hide();
        $finishBtn.prop('disabled', true).show();
        $status.text('');
        showQuestion('Speak about your topic for up to 2 minutes. Click "I\'ve finished" when done.', 'ielts-q-part2');
        speak('Now please begin speaking.', function () {
            setTimeout(function () { startRecording('p2', MAX_P2, function () { endPart2(); }); }, 800);
        });
    }

    function endPart2() {
        $p2NotesWrap.hide();
        clearQuestion();
        speak('Thank you. Now we\'ll move on to Part 3, which is a discussion related to the topic in Part 2.', function () {
            setTimeout(startPart3, 500);
        });
    }

    // ─── PART 3 ───────────────────────────────────────────────────────
    function startPart3() {
        currentPart = 3;
        p3QIdx = 0;
        p3AdaptiveQ = P3_QUESTIONS.slice();
        $partLabel.text('Part 3 — Two-way Discussion');
        $progressEl.empty();
        $skipPrepBtn.hide();
        var topic = (P2_CUECARD.split('\n')[0] || '').replace(/^(talk about|describe|discuss)/i, '').trim() || 'the topic we just discussed';
        speak('We\'ve been talking about ' + topic + '. I\'d now like to ask you some more general questions related to this topic.', function () {
            setTimeout(function () { askP3(); }, 400);
        });
    }

    function askP3() {
        if (p3QIdx >= 6) { endTest(); return; }
        var q = p3AdaptiveQ[p3QIdx];
        if (!q) { endTest(); return; }

        showQuestion(q, 'ielts-q-part3');
        $finishBtn.prop('disabled', false).show().off('click').on('click', function () {
            if (recording) stopRecording();
        });
        speak(q, function () {
            setTimeout(function () {
                startRecording('p3_' + p3QIdx, MAX_P3, function () {
                    if (p3QIdx >= 1 && p3QIdx < 5) {
                        generateAdaptiveQ(p3QIdx, function (nextQ) {
                            if (nextQ) {
                                if (p3QIdx + 1 >= p3AdaptiveQ.length) p3AdaptiveQ.push(nextQ);
                                else p3AdaptiveQ[p3QIdx + 1] = nextQ;
                            }
                            p3QIdx++;
                            askP3();
                        });
                    } else {
                        p3QIdx++;
                        askP3();
                    }
                });
            }, 800);
        });
    }

    function generateAdaptiveQ(idx, callback) {
        var lastAnswer = transcripts['p3_' + idx] || '';
        if (!lastAnswer || lastAnswer.length < 20) { callback(null); return; }
        var context = 'Topic: ' + P2_CUECARD.split('\n')[0].trim() + '\n'
            + 'Previous question: ' + (p3AdaptiveQ[idx] || '') + '\n'
            + 'Candidate answer: ' + lastAnswer + '\n'
            + 'Remaining seed questions: ' + p3AdaptiveQ.slice(idx + 1).filter(Boolean).join('; ');
        $.ajax({
            url: cfg.ajaxUrl, method: 'POST', timeout: 30000,
            data: { action: 'ielts_cm_speaking_next_question', nonce: cfg.nonce, context: context },
            success: function (r) { callback(r.success ? r.data.question : null); },
            error:   function ()  { callback(null); }
        });
    }

    function endTest() {
        clearQuestion();
        speak('Thank you very much. That is the end of the speaking test.', function () {
            setTimeout(waitAndAssess, 500);
        });
    }

    // ─── Recording ────────────────────────────────────────────────────
    function startRecording(key, maxSecs, onDone) {
        audioChunks = [];
        var mimeType = '';
        var types = ['audio/webm;codecs=opus','audio/webm','audio/ogg;codecs=opus','audio/ogg','audio/mp4'];
        for (var i = 0; i < types.length; i++) {
            if (MediaRecorder.isTypeSupported(types[i])) { mimeType = types[i]; break; }
        }

        function doRecord(s) {
            stream = s;
            mediaRecorder = new MediaRecorder(s, mimeType ? { mimeType: mimeType } : {});
            mediaRecorder.ondataavailable = function (e) { if (e.data && e.data.size > 0) audioChunks.push(e.data); };
            mediaRecorder.onstop = function () {
                stopSilenceDetection();
                var blob = new Blob(audioChunks, { type: mimeType || 'audio/webm' });
                transcribeInBackground(blob, mimeType || 'audio/webm', key);
                onDone();
            };
            mediaRecorder.start(500);
            recording = true;
            $finishBtn.prop('disabled', false).off('click').on('click', function () {
                if (recording) stopRecording();
            });
            $status.html('<span class="ielts-recording-indicator"><span class="ielts-rec-pulse"></span> Recording</span>');
            startCountdown(maxSecs, null, function () {
                if (recording) stopRecording();
            }, 'speaking');
            startSilenceDetection(s, function () {
                if (recording) { $status.text(''); stopRecording(); }
            });
        }

        if (stream && stream.active) { doRecord(stream); }
        else {
            navigator.mediaDevices.getUserMedia({ audio: true }).then(doRecord).catch(function (err) {
                $status.text('Mic error: ' + err.message);
                onDone();
            });
        }
    }

    function stopRecording() {
        if (!recording || !mediaRecorder) return;
        recording = false;
        clearCountdown();
        stopSilenceDetection();
        $finishBtn.prop('disabled', true).hide();
        $status.text('');
        mediaRecorder.stop();
    }

    // ─── Silence detection ────────────────────────────────────────────
    function startSilenceDetection(s, onSilence) {
        stopSilenceDetection();
        if (!sharedAnalyser) {
            console.warn('No shared analyser — silence detection unavailable');
            return;
        }
        var silentFor = 0;
        var warnShown = false;

        silenceTimer = setInterval(function () {
            if (!recording) { stopSilenceDetection(); return; }
            sharedAnalyser.getByteFrequencyData(sharedBuf);
            var sum = 0;
            for (var i = 0; i < sharedBuf.length; i++) sum += sharedBuf[i];
            var level = sum / sharedBuf.length;

            updateLiveMeter(level);

            if (level >= SILENCE_THRESH) {
                silentFor = 0;
                if (warnShown) { hideSilenceWarning(); warnShown = false; }
            } else {
                silentFor++;
                if (silentFor >= SILENCE_SECS && !warnShown) {
                    warnShown = true;
                    showSilenceWarning(function () {
                        if (recording) { $status.text(''); stopRecording(); }
                    });
                }
            }
        }, 1000);
    }

    function stopSilenceDetection() {
        if (silenceTimer) { clearInterval(silenceTimer); silenceTimer = null; }
        // Never close audioCtx/sharedAnalyser — they persist for the whole session
    }

    var silenceWarnTimer = null;
    function showSilenceWarning(onExpire) {
        hideSilenceWarning(); // clear any existing before showing
        var $warn = $('<div id="ielts-silence-warning" style="display:none;position:fixed;bottom:36px;left:50%;transform:translateX(-50%);background:rgba(30,30,30,0.88);color:#fff;padding:12px 28px;border-radius:30px;font-size:14px;font-weight:500;white-space:nowrap;pointer-events:none;z-index:99999;box-shadow:0 4px 20px rgba(0,0,0,0.3);letter-spacing:0.01em;"></div>');
        $warn.text('You need to be speaking or the examiner will move on.');
        $('body').append($warn);
        // Gentle pulse: fade in, hold, fade out, repeat
        function pulse() {
            $warn.fadeIn(600).delay(1200).fadeOut(600, function () {
                if ($warn.parent().length) pulse();
            });
        }
        pulse();
        var secs = 5;
        silenceWarnTimer = setInterval(function () {
            secs--;
            if (secs <= 0) {
                clearInterval(silenceWarnTimer);
                silenceWarnTimer = null;
                $warn.stop(true, true).fadeOut(400, function () {
                    $warn.remove();
                    if (onExpire) onExpire();
                });
            }
        }, 1000);
    }

    function hideSilenceWarning() {
        if (silenceWarnTimer) { clearInterval(silenceWarnTimer); silenceWarnTimer = null; }
        $('#ielts-silence-warning').stop(true, true).fadeOut(300, function () { $(this).remove(); });
    }

    // ─── Countdown ────────────────────────────────────────────────────
    function startCountdown(secs, onTick, onEnd, mode) {
        var total = secs;
        $countdownWrap.show();
        $countdownNum.text(secs);
        $countdownBar.css('width','100%').removeClass('ielts-countdown-urgent');
        $countdownBar.css('background', mode === 'prep' ? '#2563eb' : '');
        countdown = setInterval(function () {
            secs--;
            $countdownNum.text(secs);
            $countdownBar.css('width', (secs / total * 100) + '%');
            if (secs <= 5) $countdownBar.addClass('ielts-countdown-urgent');
            if (onTick) onTick(secs);
            if (secs <= 0) { clearCountdown(); if (onEnd) onEnd(); }
        }, 1000);
    }

    function clearCountdown() {
        if (countdown) { clearInterval(countdown); countdown = null; }
        $countdownWrap.hide();
        $countdownNum.text('');
        $countdownBar.css('width','0%').removeClass('ielts-countdown-urgent');
    }

    // ─── Transcription ────────────────────────────────────────────────
    function transcribeInBackground(blob, mimeType, key) {
        pendingTx++;
        var ext = mimeType.indexOf('ogg') !== -1 ? 'ogg' : mimeType.indexOf('mp4') !== -1 ? 'mp4' : 'webm';
        var fd  = new FormData();
        fd.append('audio', blob, 'recording.' + ext);
        fd.append('action', 'ielts_cm_speaking_transcribe');
        fd.append('nonce', cfg.nonce);
        $.ajax({
            url: cfg.ajaxUrl, method: 'POST', data: fd,
            processData: false, contentType: false, timeout: 60000,
            success: function (r) {
                pendingTx--;
                transcripts[key] = r.success ? r.data.transcript : '[transcription failed]';
                if (assessDone && pendingTx === 0) runAssessment();
            },
            error: function () {
                pendingTx--;
                transcripts[key] = '[transcription error]';
                if (assessDone && pendingTx === 0) runAssessment();
            }
        });
    }

    // ─── Assessment ───────────────────────────────────────────────────
    function waitAndAssess() {
        $interview.hide();
        $results.show().html(
            '<div class="ielts-speaking-assessing">' +
            '<div class="ielts-speaking-progress-bar-track"><div class="ielts-speaking-progress-bar-fill" id="ielts-speak-bar"></div></div>' +
            '<div class="ielts-speaking-progress-label" id="ielts-speak-bar-label">Finalising transcriptions...</div>' +
            '</div>'
        );
        startAssessProgress();
        assessDone = true;
        if (pendingTx === 0) runAssessment();
    }

    function runAssessment() {
        var responsesArr = [];
        P1_QUESTIONS.forEach(function (q, i) {
            responsesArr.push({ part: 1, question: q, answer: transcripts['p1_' + i] || '[no response]' });
        });
        responsesArr.push({ part: 2, question: P2_CUECARD, answer: transcripts['p2'] || '[no response]' });
        for (var i = 0; i < p3QIdx; i++) {
            responsesArr.push({ part: 3, question: p3AdaptiveQ[i] || '', answer: transcripts['p3_' + i] || '[no response]' });
        }
        $.ajax({
            url: cfg.ajaxUrl, method: 'POST', timeout: 90000,
            data: { action: 'ielts_cm_speaking_assess_full', nonce: cfg.nonce, responses: responsesArr },
            success: function (r) {
                completeAssessProgress();
                setTimeout(function () {
                    if (r.success) {
                        $results.html(r.data.html);
                        saveSpeakingScore(r.data.assessment.overall_band);
                    } else {
                        $results.html('<div style="padding:2rem;color:#dc2626;font-size:14px;">Assessment failed: ' + (r.data ? r.data.message : 'Unknown error') + '</div>');
                    }
                }, 400);
            },
            error: function () {
                completeAssessProgress();
                $results.html('<div style="padding:2rem;color:#dc2626;font-size:14px;">Network error. Please reload and try again.</div>');
            }
        });
    }

    function saveSpeakingScore(band) {
        $.ajax({
            url: cfg.ajaxUrl, method: 'POST',
            data: { action: 'ielts_cm_save_speaking_score', nonce: cfg.nonce, quiz_id: cfg.quizId, course_id: cfg.courseId, lesson_id: cfg.lessonId, band_score: band },
            success: function () {
                $('#ielts-speaking-next-link').css({ opacity: 1, 'pointer-events': 'auto' });
                $('#ielts-speaking-completion').show();
            }
        });
    }

    function startAssessProgress() {
        var steps = [
            { pct: 10, label: 'Finalising transcriptions...',       delay: 0     },
            { pct: 30, label: 'Assessing fluency and coherence...', delay: 5000  },
            { pct: 50, label: 'Assessing vocabulary range...',      delay: 12000 },
            { pct: 70, label: 'Assessing grammatical range...',     delay: 19000 },
            { pct: 88, label: 'Compiling your full feedback...',    delay: 26000 },
        ];
        steps.forEach(function (s) {
            var t = setTimeout(function () {
                $('#ielts-speak-bar').css('width', s.pct + '%');
                $('#ielts-speak-bar-label').fadeOut(150, function () { $(this).text(s.label).fadeIn(150); });
            }, s.delay);
            progressTimers.push(t);
        });
    }

    function completeAssessProgress() {
        progressTimers.forEach(function (t) { clearTimeout(t); });
        $('#ielts-speak-bar').css('width', '100%');
        $('#ielts-speak-bar-label').text('Done — loading your results...');
    }

    // ─── Init ─────────────────────────────────────────────────────────
    if (!navigator.mediaDevices || !window.MediaRecorder) {
        $('#ielts-speech-badge').text('not supported').addClass('warn');
        $status.text('Audio recording not supported. Please use Chrome, Firefox or Safari.');
        return;
    }
    if (!cfg.hasOpenAI) {
        $('#ielts-speech-badge').text('OpenAI key missing').addClass('warn');
        $status.text('OpenAI API key not configured. Please add it in Writing Settings.');
        return;
    }

    // Gender choice shown first — mic check and test begin after selection

});
