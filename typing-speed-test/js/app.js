let passages = {};
let currentPassage = '';
let currentDifficulty = 'easy';
let currentMode = 'timed'; //timed ou passage

let testState = 'idle'; // idle ou running ou done
let charIndex = 0;
let errors = 0;
let totalTyped = 0;
let timerInterval = null;
let timeLeft = 60;
let timeElapsed = 0;
let personalBest = null;

//start
const passageEl = document.getElementById('passage');
const passageOverlay = document.getElementById('passageOverlay');
const startBtn = document.getElementById('startBtn');
const hiddenInput = document.getElementById('hiddenInput');
const restartArea = document.getElementById('restartArea');
const restartBtn = document.getElementById('restartBtn');

//stats
const wpmDisplay = document.getElementById('wpmDisplay');
const accuracyDisplay = document.getElementById('accuracyDisplay');
const timeDisplay = document.getElementById('timeDisplay');

//screen
const testScreen = document.getElementById('testScreen');
const resultsScreen = document.getElementById('resultsScreen');
const resultsIcon = document.getElementById('resultsIcon');
const resultsTitle = document.getElementById('resultsTitle');
const resultsSubtitle = document.getElementById('resultsSubtitle');
const resultsWpm = document.getElementById('resultsWpm');
const resultsAccuracy = document.getElementById('resultsAccuracy');
const resultsChars = document.getElementById('resultsChars');
//repeat button
const goAgainBtn = document.getElementById('goAgainBtn');
const goAgainLabel = document.getElementById('goAgainLabel');

//value
const pbValue = document.getElementById('pbValue');
const pbUnit = document.getElementById('pbUnit');

//desktop pill buttons
const difficultyBtns = document.querySelectorAll('[data-difficulty]');
const modeBtns = document.querySelectorAll('[data-mode]');

//initialization
async function init() {
    loadPersonalBest();
    await loadPassages();
    setupEventListeners();
    setupNewTest();
}

async function loadPassages() {
    try{
        const response = await fetch('data.json');
        passages = await response.json();
    }
    catch (e) {
        //text
        passages = {
            easy: [{id:'e1', text:'The sun rose over the quiet town. Birds sang in the trees as people woke up and started their day. It was going to be a warm sunny morning.'}],
            medium: [{id:'m1', text: "Learning a new skill takes patience and consistent practice. Whether you're studying a language, picking up an instrument, or mastering a sport, the key is to show up every day. Small improvements compound over time, and before you know it, you'll have made remarkable progress."}],
            hard: [{id:'h1', text: "The philosopher\'s argument hinged on a seemingly paradoxical assertion: that absolute freedom, pursued without constraint, inevitably undermines itself. \"Consider,\" she wrote, \"how the libertarian ideal—when taken to its logical extreme—produces conditions in which the powerful dominate the weak, thereby eliminating freedom for the majority.\" Her critics dismissed this as sophistry; her supporters hailed it as profound."}]
        }
    }
}

function loadPersonalBest() {
    const stored = localStorage.getItem('typingPB');
    if (stored !== null){
        personalBest = parseInt (stored, 10);
        pbValue.textContent = personalBest;
        pbUnit.textContent = 'WPM';
    }
    else {
        pbValue.textContent = '--';
        pbUnit.textContent = '';
    }
}

function getDuration() {
    const durations = {easy: 60, medium: 90, hard: 120};
    return durations[currentDifficulty] || 60;
}

//new test
function setupNewTest() {
    passageEl.classList.add('blurred');
    clearInterval(timerInterval);
    testState = 'idle';
    charIndex = 0;
    errors = 0;
    totalTyped = 0;
    timeLeft = getDuration();
    timeElapsed = 0;

    //random passage
    const pool = passages[currentDifficulty] || passages['easy'];
    const picked = pool[Math.floor(Math.random() * pool.length)]; //math.floor : supprime les decimals
    currentPassage = picked.text;

    renderPassage();

    //reset stats
    wpmDisplay.textContent = '0';
    accuracyDisplay.textContent = '100%';
    if (currentMode === 'timed'){
        const secs = getDuration();
        const mins = Math.floor(secs / 60);
        timeDisplay.textContent = `${mins}:${(secs % 60).toString().padStart(2, '0')}`;
    } else {
        timeDisplay.textContent = '0:00';
    }

    //show overlay, hide restart
    passageOverlay.classList.remove('hidden');
    restartArea.style.display = 'none';

    //show test screen
    testScreen.style.display = '';
    resultsScreen.style.display = 'none';

    hiddenInput.value = '';
}

function renderPassage (){
    passageEl.innerHTML = '';
    let i = 0;
    while (i < currentPassage.length) {
        if (currentPassage[i] === ' '){
            //only space
            const span = document.createElement('span');
            span.classList.add('char');
            span.textContent = '\u00A0';
            span.dataset.char = ' ';
            if (i === 0) span.classList.add('cursor');
            passageEl.appendChild(span);
            i++;
        } else {
            //all in one
            const wordEl = document.createElement('span');
            wordEl.classList.add('word');
            while (i < currentPassage.length && currentPassage[i] !== ' '){
                const span = document.createElement('span');
                span.classList.add('char');
                span.textContent = currentPassage[i];
                span.dataset.char = currentPassage[i];
                if (i === 0) span.classList.add('cursor');
                wordEl.appendChild(span);
                i++;
            }
            passageEl.appendChild(wordEl);
        }
    }
}

// start
function startTest() {
    if (testState !== 'idle') return;
    passageEl.classList.remove('blurred');
    testState = 'running';

    passageOverlay.classList.add('hidden');
    restartArea.style.display = 'flex';

    hiddenInput.focus();
    startTimer();
}

function startTimer () {
    clearInterval(timerInterval);
    timerInterval = setInterval (() => {
        if ( currentMode === 'timed') {
            timeLeft--;
            const mins = Math.floor (timeLeft / 60); 
            const secs = timeLeft % 60;
            timeDisplay.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
            if (timeLeft <= 0) {
                endTest();
            }
        } else {
            timeElapsed++;
            const mins = Math.floor (timeElapsed / 60);
            const secs = timeElapsed % 60;
            timeDisplay.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
        }
        updateLiveStats();
    }, 1000); 
}

// typing
function handleKeyInput(e) {
    if (testState !== 'running') return;

    const chars = passageEl.querySelectorAll('.char');
    //return key
    if (e.key === 'Backspace') {
        if (charIndex > 0) {
            charIndex--;
            const prev = chars[charIndex];
            //move to prev
            if (chars[charIndex + 1]) chars[charIndex + 1].classList.remove('cursor');
            prev.classList.remove('correct', 'error');
            prev.classList.add('cursor');
        }
        return;
    }
    //ignore keys like shift (non-printable keys)
    if (e.key.length !== 1) return;

    const expected = currentPassage[charIndex];
    const typed = e.key;

    const charEl = chars[charIndex];
    charEl.classList.remove('cursor');

    totalTyped++;
    if (typed === expected){
        charEl.classList.add('correct');
    } else {
        charEl.classList.add('error');
        errors++;
    }

    charIndex++;

    //move cursor
    if (charIndex < chars.length) {
        chars[charIndex].classList.add('cursor');
    }

    updateLiveStats();

    //passage complete and end
    if (charIndex >= currentPassage.length) {
        endTest();
    }
}

//update
function updateLiveStats() {
    let elapsed ;
    if ( currentMode === 'timed') {
        elapsed = getDuration() - timeLeft
    } else {
        elapsed = timeElapsed;
    }
    const minutes = elapsed / 60
    const correct = totalTyped - errors;
    
    //formula word per minute : WPM = (caractères corrects ÷ 5) ÷ temps en minutes 
    // cause 1 mot = 5 caractères
    let wpm;
    if (minutes > 0 ) {
        wpm = Math.round((correct / 5) / minutes);
    } else {
        wpm = 0;
    }
    let accuracy;
    if (totalTyped > 0) {
        accuracy = Math.round((correct / totalTyped) * 100);
    } else {
        accuracy = 100;
    }

    //affichage
    wpmDisplay.textContent = wpm;
    accuracyDisplay.textContent = accuracy + '%';
}

//end
function endTest() {
    clearInterval(timerInterval);
    testState = 'done';

    let elapsed;
    if (currentMode === 'timed') {
        elapsed = getDuration() - timeLeft;
    } else {
        elapsed = timeElapsed;
    }

    const minutes = Math.max(elapsed / 60, 1 / 60);
    const correct = totalTyped - errors;
    const wpm = Math.round((correct / 5) / minutes);

    let accuracy;
    if (totalTyped > 0) {
        accuracy = Math.round((correct / totalTyped) * 100);
    } else {
        accuracy = 100;
    }

    showResults (wpm,accuracy,correct, errors);
}

//results
function showResults(wpm, accuracy, correct, errs) {
    testScreen.style.display = 'none';
    resultsScreen.style.display = 'flex';

    resultsWpm.textContent = wpm;
    resultsAccuracy.textContent = accuracy + '%';
    resultsChars.innerHTML = `<span class="chars-correct">${correct}</span>/<span class="chars-errors">${errs}</span>`;

    const isFirstTest = personalBest === null;
    const isNewBest = !isFirstTest && wpm > personalBest;

    if (isFirstTest) {
        personalBest = wpm;
        localStorage.setItem('typingPB', wpm);
        pbValue.textContent = wpm;
        pbUnit.textContent = 'WPM';

        resultsIcon.className = 'results-icon icon-check';
        resultsIcon.innerHTML = '✅';
        resultsTitle.textContent = 'Baseline Established!';
        resultSubtitle.textContent = 'You\'ve set the bar. Now the real challenge begins-time to beat it.';
        goAgainLabel.textContent = 'Beat This Score\?';
    } else if (isNewBest) {
        //new personal best
        personalBest = wpm;
        localStorage.setItem('typingPB', wpm);
        pbValue.textContent = wpm;
        pbUnit.textContent = 'WPM';

        resultsIcon.className = 'results-icon';
        resultsIcon.innerHTML = '🎉'
        resultsTitle.textContent = 'High Score Smashed';
        resultsSubtitle.textContent = 'you\'re getting faster. That was lit🔥!!'
        goAgainLabel.textContent = 'Bet you can\'t beat this Score';

        launchConfetti();
    } else {
        resultsIcon.className = 'results-icon icon-check';
        resultsIcon.innerHTML = '✅';
        resultsTitle.textContent = 'Test Complete!';
        resultsSubtitle.textContent = 'Solid run but not enough, just saying...';
        goAgainLabel.textContent = 'Go Again';
    }
}

//confetti
function launchConfetti() {
    const canvas = document.getElementById('confettiCanvas');
    const ctx = canvas.getContext('2d');
    canvas.width = canvas.offsetWidth;
    canvas.height = canvas.offsetHeight;

    const colors = ['#e63946', '#2196f3', '#4caf50', '#ffca28', '#f06292'];
    const pieces = Array.from({ length: 120 }, () => ({
        x: Math.random() * canvas.width,
        y: canvas.height + Math.random() * 200,
        w: 8 + Math.random() * 8,
        h: 4 + Math.random() * 4,
        color: colors[Math.floor(Math.random() * colors.length)],
        speed: 2 + Math.random() * 3,
        angle: Math.random() * Math.PI * 2,
        spin: (Math.random() - 0.5) * 0.15,
        drift: (Math.random() - 0.5) * 1.5,
    }));
    let frame = 0;
    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        pieces.forEach(p => {
        p.y -= p.speed;
        p.x += p.drift;
        p.angle += p.spin;
        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate(p.angle);
        ctx.fillStyle = p.color;
        ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
        ctx.restore();
        });
        frame++;
        if (frame < 180) requestAnimationFrame(draw);
        else ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    draw();
}

//Event listeners
function setupEventListeners() {
    //start button
    startBtn.addEventListener('click', startTest);

    //click on passage starts the test
    passageEl.addEventListener('click', () => {
        if (testState === 'idle') startTest();
        else hiddenInput.focus();
    });

    //keydown anywhere (other way to start the test)
    document.addEventListener ('keydown', (e) => {
        if (testState === 'idle'){
            if (e.key.length === 1 && !e.ctrlKey && !e.metaKey) {
                startTest();
                setTimeout(() => handleKeyInput(e), 10);
            }
        }
    });

    hiddenInput.addEventListener('keydown', handleKeyInput);

    //restart
    restartBtn.addEventListener('click', () => {
        setupNewTest();
    });

    //go again
    goAgainBtn.addEventListener('click', () => {
        setupNewTest();
    });

    difficultyBtns.forEach(btn =>{
        btn.addEventListener('click', () => {
            if (testState === 'running') return;
            currentDifficulty = btn.dataset.difficulty;
            difficultyBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            setupNewTest();
        });
    });

    modeBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            currentMode = btn.dataset.mode;
            modeBtns.forEach(b =>b.classList.remove('active'));
            btn.classList.add('active');
            setupNewTest();
        });
    });
}

init();