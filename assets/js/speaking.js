/* global ieltsSpeaking, jQuery */
(function ($) {
    'use strict';

    if (!$('#ielts-speaking-app').length) return;

    var cfg = ieltsSpeaking;

    var QUESTIONS = [
        "Can you describe the area where you grew up?",
        "Do you prefer living in a house or an apartment, and why?",
        "What do you enjoy doing in your free time?",
        "Has this hobby changed since you were younger?",
        "What kind of food do you enjoy most?",
        "Do you prefer cooking at home or eating out, and why?"
    ];

    var GENDER     = cfg.examinerGender || 'female';
    var INTRO_TEXT = "Hello. I'm your examiner today. I'd like to ask you some questions about yourself and your life. Let's begin.";

    var TOTAL          = QUESTIONS.length;
    var qIndex         = 0;
    var responses      = [];
    var mediaRecorder  = null;
    var audioChunks    = [];
    var recording      = false;
    var stream         = null;
    var countdown      = null;
    var progressTimers = [];
    var synth          = window.speechSynthesis;
    var voice          = null;
    var MAX_SECONDS    = 30;

    document.documentElement.style.setProperty('--ielts-speaking-color', cfg.progressColor || '#E56C0A');

    var $messages       = $('#ielts-speaking-messages');
    var $statusEl       = $('#ielts-speak-status');
    var $progressEl     = $('#ielts-speaking-progress');
    var $micCheck       = $('#ielts-mic-check');
    var $interview      = $('#ielts-speaking-interview');
    var $results        = $('#ielts-speaking-results');
    var $finishBtn      = $('#ielts-finish-btn');
    var $countdownEl    = $('#ielts-countdown');
    var $countdownBar   = $('#ielts-countdown-bar');

    // ─── Pick a good voice ────────────────────────────────────────────
    function loadVoice() {
        var voices      = synth.getVoices();
        var femaleNames = ['Google UK English Female','Microsoft Hazel','Samantha','Karen','Victoria','Moira'];
        var maleNames   = ['Google UK English Male','Microsoft George','Daniel','Gordon','Lee'];
        var preferred   = GENDER === 'male' ? maleNames : femaleNames;
        for (var i = 0; i < preferred.length; i++) {
            for (var j = 0; j < voices.length; j++) {
                if (voices[j].name === preferred[i]) { voice = voices[j]; return; }
            }
        }
        for (var k = 0; k < voices.length; k++) {
            if (voices[k].lang === 'en-GB') { voice = voices[k]; return; }
        }
        for (var l = 0; l < voices.length; l++) {
            if (voices[l].lang.indexOf('en') === 0) { voice = voices[l]; return; }
        }
    }

    function speak(text, onEnd) {
        if (!synth) { if (onEnd) onEnd(); return; }
        synth.cancel();
        var utt = new SpeechSynthesisUtterance(text);
        utt.rate   = 0.92;
        utt.pitch  = GENDER === 'male' ? 0.85 : 1.05;
        utt.volume = 1.0;
        if (voice) utt.voice = voice;
        utt.onend   = function () { if (onEnd) onEnd(); };
        utt.onerror = function () { if (onEnd) onEnd(); };
        synth.speak(utt);
    }

    // ─── Progress dots ────────────────────────────────────────────────
    function buildProgress() {
        $progressEl.empty();
        for (var i = 0; i < TOTAL; i++) {
            var cls = 'ielts-speaking-prog-dot';
            if (i < qIndex) cls += ' done';
            else if (i === qIndex) cls += ' active';
            $progressEl.append('<div class="' + cls + '"></div>');
        }
    }

    function addMsg(role, text, typing) {
        var $wrap = $('<div class="ielts-speaking-msg ' + role + '"></div>');
        var $av   = $('<div class="ielts-speaking-msg-av">' + (role === 'examiner' ? 'E' : 'Y') + '</div>');
        var $b    = $('<div class="ielts-speaking-bubble' + (typing ? ' typing' : '') + '">' + text + '</div>');
        $wrap.append($av).append($b);
        $messages.append($wrap);
        $messages.scrollTop($messages[0].scrollHeight);
        return $b;
    }

    // ─── Mic check ────────────────────────────────────────────────────
    function initMicCheck() {
        var $startBtn   = $('#ielts-mic-start');
        var $playBtn    = $('#ielts-mic-play');
        var $confirmBtn = $('#ielts-mic-confirm');
        var $micStatus  = $('#ielts-mic-status');
        var micChunks   = [];
        var micRecorder = null;
        var micStream   = null;
        var micBlob     = null;
        var micAudio    = null;

        if (!navigator.mediaDevices || !window.MediaRecorder) {
            $micStatus.text('Audio recording is not supported in this browser.');
            $startBtn.prop('disabled', true);
            return;
        }

        $startBtn.on('click', function () {
            $micStatus.text('Recording for 5 seconds...');
            $startBtn.prop('disabled', true);
            micChunks = [];

            navigator.mediaDevices.getUserMedia({ audio: true }).then(function (s) {
                micStream   = s;
                micRecorder = new MediaRecorder(s);
                micRecorder.ondataavailable = function (e) { if (e.data.size > 0) micChunks.push(e.data); };
                micRecorder.onstop = function () {
                    micBlob = new Blob(micChunks, { type: 'audio/webm' });
                    micStream.getTracks().forEach(function (t) { t.stop(); });
                    $micStatus.text('Recording done. Play it back to check your microphone.');
                    $playBtn.prop('disabled', false);
                    $confirmBtn.prop('disabled', false);
                };
                micRecorder.start();
                setTimeout(function () { micRecorder.stop(); }, 5000);
            }).catch(function (err) {
                $micStatus.text('Could not access microphone: ' + (err.name === 'NotAllowedError' ? 'permission denied. Please allow mic access.' : err.message));
                $startBtn.prop('disabled', false);
            });
        });

        $playBtn.on('click', function () {
            if (!micBlob) return;
            if (micAudio) { micAudio.pause(); micAudio = null; }
            micAudio = new Audio(URL.createObjectURL(micBlob));
            micAudio.play();
            $micStatus.text('Playing back your recording...');
            micAudio.onended = function () { $micStatus.text('Playback complete. Happy with your mic? Click Confirm to start.'); };
        });

        $confirmBtn.on('click', function () {
            // Request mic permission for the main interview at the same time
            navigator.mediaDevices.getUserMedia({ audio: true }).then(function (s) {
                // Store the stream for reuse
                stream = s;
                $micCheck.hide();
                $interview.show();
                beginInterview();
            }).catch(function () {
                $micCheck.hide();
                $interview.show();
                beginInterview();
            });
        });
    }

    // ─── Begin interview ──────────────────────────────────────────────
    function beginInterview() {
        loadVoice();
        buildProgress();
        speak(INTRO_TEXT, function () {
            setTimeout(function () { askQuestion(0); }, 500);
        });
    }

    // ─── Ask question ─────────────────────────────────────────────────
    function askQuestion(index) {
        qIndex = index + 1;
        buildProgress();
        var q = QUESTIONS[index];
        addMsg('examiner', q);
        $finishBtn.prop('disabled', true);
        speak(q, function () {
            setTimeout(function () { startRecording(index); }, 300);
        });
    }

    // ─── Recording ───────────────────────────────────────────────────
    function startRecording(qIdx) {
        audioChunks = [];
        var mimeType = '';
        var types = ['audio/webm;codecs=opus', 'audio/webm', 'audio/ogg;codecs=opus', 'audio/ogg', 'audio/mp4'];
        for (var i = 0; i < types.length; i++) {
            if (MediaRecorder.isTypeSupported(types[i])) { mimeType = types[i]; break; }
        }

        // Reuse existing stream if available, otherwise get new one
        function doRecord(s) {
            stream = s;
            mediaRecorder = new MediaRecorder(s, mimeType ? { mimeType: mimeType } : {});
            mediaRecorder.ondataavailable = function (e) { if (e.data && e.data.size > 0) audioChunks.push(e.data); };
            mediaRecorder.onstop = function () {
                var blob = new Blob(audioChunks, { type: mimeType || 'audio/webm' });
                transcribeInBackground(blob, mimeType || 'audio/webm', qIdx);
            };
            mediaRecorder.start(500);
            recording = true;

            // Show recording state
            $finishBtn.prop('disabled', false).text("I've finished");
            $statusEl.html('<span class="ielts-recording-indicator"><span class="ielts-rec-pulse"></span> Recording</span>');
            startCountdown();
        }

        if (stream && stream.active) {
            doRecord(stream);
        } else {
            navigator.mediaDevices.getUserMedia({ audio: true }).then(doRecord).catch(function (err) {
                $statusEl.text('Microphone error: ' + err.message);
            });
        }
    }

    function stopRecording() {
        if (!recording || !mediaRecorder) return;
        recording = false;
        clearCountdown();
        $finishBtn.prop('disabled', true).text('Processing...');
        $statusEl.text('');
        mediaRecorder.stop();
    }

    // ─── Countdown ────────────────────────────────────────────────────
    function startCountdown() {
        var secs = MAX_SECONDS;
        $countdownEl.text(secs);
        $countdownBar.css('width', '100%');

        countdown = setInterval(function () {
            secs--;
            $countdownEl.text(secs);
            $countdownBar.css('width', (secs / MAX_SECONDS * 100) + '%');
            if (secs <= 5) $countdownBar.addClass('ielts-countdown-urgent');
            if (secs <= 0) {
                clearCountdown();
                stopRecordingAndAdvance();
            }
        }, 1000);
    }

    function clearCountdown() {
        if (countdown) { clearInterval(countdown); countdown = null; }
        $countdownEl.text('');
        $countdownBar.css('width', '0%').removeClass('ielts-countdown-urgent');
    }

    // ─── Finish button / timer end ────────────────────────────────────
    function stopRecordingAndAdvance() {
        var currentQIdx = qIndex - 1;
        stopRecording();

        setTimeout(function () {
            if (qIndex >= TOTAL) {
                // All questions done — wait for transcriptions then assess
                waitAndAssess();
            } else {
                askQuestion(qIndex);
            }
        }, 400);
    }

    // ─── Background transcription ─────────────────────────────────────
    var pendingTranscriptions = 0;
    var transcripts           = {};
    var assessmentRequested   = false;

    function transcribeInBackground(blob, mimeType, qIdx) {
        pendingTranscriptions++;
        var ext = mimeType.indexOf('ogg') !== -1 ? 'ogg' : mimeType.indexOf('mp4') !== -1 ? 'mp4' : 'webm';
        var formData = new FormData();
        formData.append('audio', blob, 'recording.' + ext);
        formData.append('action', 'ielts_cm_speaking_transcribe');
        formData.append('nonce', cfg.nonce);

        $.ajax({
            url: cfg.ajaxUrl, method: 'POST',
            data: formData, processData: false, contentType: false, timeout: 60000,
            success: function (response) {
                pendingTranscriptions--;
                if (response.success) {
                    transcripts[qIdx] = response.data.transcript;
                } else {
                    transcripts[qIdx] = '[Transcription failed]';
                }
                if (assessmentRequested && pendingTranscriptions === 0) runAssessment();
            },
            error: function () {
                pendingTranscriptions--;
                transcripts[qIdx] = '[Transcription error]';
                if (assessmentRequested && pendingTranscriptions === 0) runAssessment();
            }
        });
    }

    function waitAndAssess() {
        $('#ielts-speaking-interview').hide();
        $('#ielts-speaking-results').show().html(
            '<div class="ielts-speaking-assessing">' +
            '<div class="ielts-speaking-progress-bar-track"><div class="ielts-speaking-progress-bar-fill" id="ielts-speak-bar"></div></div>' +
            '<div class="ielts-speaking-progress-label" id="ielts-speak-bar-label">Transcribing your responses...</div>' +
            '</div>'
        );
        startProgress();
        assessmentRequested = true;
        if (pendingTranscriptions === 0) runAssessment();
    }

    // ─── Assessment ───────────────────────────────────────────────────
    function runAssessment() {
        // Build responses from transcripts
        var responsesArr = [];
        for (var i = 0; i < TOTAL; i++) {
            responsesArr.push({ q: QUESTIONS[i], a: transcripts[i] || '[No response]' });
        }

        $.ajax({
            url: cfg.ajaxUrl, method: 'POST', timeout: 90000,
            data: { action: 'ielts_cm_speaking_assess', nonce: cfg.nonce, responses: responsesArr },
            success: function (response) {
                completeProgress();
                setTimeout(function () {
                    $('#ielts-speaking-results').html(response.success
                        ? response.data.html
                        : '<div style="padding:2rem;color:#dc2626;font-size:14px;">' + (response.data ? response.data.message : 'Unknown error') + '</div>'
                    );
                }, 400);
            },
            error: function () {
                completeProgress();
                $('#ielts-speaking-results').html('<div style="padding:2rem;color:#dc2626;font-size:14px;">Network error. Please reload and try again.</div>');
            }
        });
    }

    // ─── Progress bar ─────────────────────────────────────────────────
    function startProgress() {
        var steps = [
            { pct: 15, label: 'Transcribing your responses...',     delay: 0     },
            { pct: 35, label: 'Assessing fluency and coherence...', delay: 4000  },
            { pct: 55, label: 'Assessing vocabulary range...',      delay: 9000  },
            { pct: 75, label: 'Assessing grammatical range...',     delay: 14000 },
            { pct: 90, label: 'Compiling your feedback...',         delay: 20000 },
        ];
        steps.forEach(function (s) {
            var t = setTimeout(function () {
                $('#ielts-speak-bar').css('width', s.pct + '%');
                $('#ielts-speak-bar-label').fadeOut(150, function () { $(this).text(s.label).fadeIn(150); });
            }, s.delay);
            progressTimers.push(t);
        });
    }

    function completeProgress() {
        progressTimers.forEach(function (t) { clearTimeout(t); });
        $('#ielts-speak-bar').css('width', '100%');
        $('#ielts-speak-bar-label').text('Done — loading your results...');
    }

    // ─── Init ─────────────────────────────────────────────────────────
    if (!navigator.mediaDevices || !window.MediaRecorder) {
        $('#ielts-speech-badge').text('not supported').addClass('warn');
        $statusEl.text('Audio recording is not supported in this browser. Please use Chrome, Firefox, or Safari.');
        return;
    }
    if (!cfg.hasOpenAI) {
        $('#ielts-speech-badge').text('OpenAI key missing').addClass('warn');
        $statusEl.text('OpenAI API key not configured. Please add it in Writing Settings.');
        return;
    }

    // Voices may load async
    if (synth.onvoiceschanged !== undefined) synth.onvoiceschanged = loadVoice;
    loadVoice();

    initMicCheck();

    // ─── Event bindings ───────────────────────────────────────────────
    $finishBtn.on('click', function () {
        if (!recording) return;
        stopRecordingAndAdvance();
    });

})(jQuery);
