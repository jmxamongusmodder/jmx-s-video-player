<?php
$default_video = "videos/default.mp4";
$file = isset($_GET['file']) ? $_GET['file'] : null;
$filepath = "videos/" . basename($file);
if (!$file || !file_exists($filepath)) {
    $filepath = $default_video;
    $filename = basename($default_video);
} else {
    $filename = basename($filepath);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title>VP - <?php echo htmlspecialchars($filename); ?></title>
<style>
  body {
    background: #121212;
    color: white;
    margin: 0;
    height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  }
  .player {
    width: 80%;
    max-width: 900px;
    background: #1e1e1e;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 0 20px rgba(0,0,0,0.7);
    display: flex;
    flex-direction: column;
    position: relative;
    user-select: none;
  }
  video {
    width: 100%;
    background: black;
    cursor: pointer;
    display: block;
  }
  /* Controls bar */
  .controls {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 10px;
    padding: 10px;
    background: #222;
    user-select: none;
    transition: opacity 0.3s ease;
  }
  .controls.hide {
    opacity: 0;
    pointer-events: none;
  }
  .button, .speed {
    cursor: pointer;
    background: #333;
    padding: 6px 12px;
    border: none;
    border-radius: 5px;
    color: white;
    transition: background-color 0.2s;
    display: flex;
    align-items: center;
  }
  .button:hover, .speed:hover {
    background: #555;
  }
  .progress {
    flex-grow: 1;
    height: 6px;
    background: #444;
    cursor: pointer;
    position: relative;
    border-radius: 3px;
  }
  .progress-filled {
    background: #00bfff;
    height: 100%;
    width: 0%;
    border-radius: 3px;
    transition: width 0.1s linear;
  }
  .volume-slider {
    width: 100px;
  }
  .info {
    padding: 0 10px 10px 10px;
    font-size: 14px;
    color: #aaa;
  }
  /* Right-click menu */
  .context-menu {
    position: absolute;
    display: none;
    background: #222;
    border: 1px solid #444;
    border-radius: 5px;
    z-index: 1000;
  }
  .context-menu ul {
    list-style: none;
    margin: 0;
    padding: 5px 0;
  }
  .context-menu li {
    padding: 6px 20px;
    cursor: pointer;
    color: white;
    white-space: nowrap;
  }
  .context-menu li:hover {
    background: #333;
  }
  /* Fullscreen mode: controls always visible on bottom */
  :fullscreen .player {
    width: 100vw;
    height: 100vh;
    border-radius: 0;
    box-shadow: none;
  }
  :fullscreen video {
    height: 100vh;
    width: 100vw;
    object-fit: contain;
  }
  :fullscreen .controls {
    position: absolute;
    bottom: 0;
    width: 100%;
    background: rgba(30,30,30,0.85);
  }
  :fullscreen .info {
    position: absolute;
    top: 10px;
    left: 10px;
    color: #ccc;
    background: rgba(0,0,0,0.3);
    padding: 4px 8px;
    border-radius: 4px;
  }
  /* Tooltip on progress */
  #progressTooltip {
    position: absolute;
    bottom: 100%;
    margin-bottom: 5px;
    padding: 2px 6px;
    background: #222;
    color: white;
    font-size: 12px;
    border-radius: 3px;
    display: none;
    pointer-events: none;
    white-space: nowrap;
  }
  /* Volume icon styling */
  #muteButton {
    background: none;
    border: none;
    color: white;
    cursor: pointer;
    font-size: 18px;
    padding: 0 8px;
    user-select: none;
    transition: color 0.2s ease;
  }
  #muteButton:hover {
    color: #00bfff;
  }
</style>
</head>
<body>

<div class="player" id="player">
  <video id="video" src="<?php echo htmlspecialchars($filepath); ?>" preload="metadata"></video>

  <div class="controls" id="controls">
    <button id="playPause" class="button" title="Play/Pause (click video or space)">
      ▶️
    </button>
    <div class="progress" id="progress" title="Seek">
      <div class="progress-filled" id="progressFilled"></div>
      <div id="progressTooltip"></div>
    </div>
    <button id="muteButton" title="Mute/Unmute">🔊</button>
    <input type="range" id="volume" class="volume-slider" min="0" max="1" step="0.01" title="Volume">
    <select id="speed" class="speed" title="Playback Speed">
      <option value="0.5">0.5x</option>
      <option value="0.75">0.75x</option>
      <option value="1" selected>1x</option>
      <option value="1.25">1.25x</option>
      <option value="1.5">1.5x</option>
      <option value="2">2x</option>
    </select>
    <span id="speedDisplay" title="Current Speed">1x</span>
    <button id="fullscreen" class="button" title="Fullscreen (double-click video)">⛶</button>
    <span id="timeDisplay" title="Current time / Total duration">0:00 / 0:00</span>
  </div>
  <div class="info" id="info">Now Playing: <?php echo htmlspecialchars($filename); ?></div>
</div>

<!-- Right Click Menu -->
<div class="context-menu" id="contextMenu">
  <ul>
    <li id="ctx-playpause">Play/Pause</li>
    <li id="ctx-loop">Toggle Loop</li>
    <li id="ctx-setA">Set Loop Start (A)</li>
    <li id="ctx-setB">Set Loop End (B)</li>
    <li id="ctx-clearloop">Clear A-B Loop</li>
    <li id="ctx-skipback">⏪ Skip Back 10s</li>
    <li id="ctx-skipforward">⏩ Skip Forward 10s</li>
    <li id="ctx-nextframe">Next Frame</li>
    <li id="ctx-reset">Reset Playback</li>
    <li id="ctx-download">Download Video</li>
    <li id="ctx-info">Video Info</li>
  </ul>
</div>

<script>
const video = document.getElementById('video');
const playPause = document.getElementById('playPause');
const volume = document.getElementById('volume');
const speed = document.getElementById('speed');
const speedDisplay = document.getElementById('speedDisplay');
const fullscreen = document.getElementById('fullscreen');
const progress = document.getElementById('progress');
const progressFilled = document.getElementById('progressFilled');
const progressTooltip = document.getElementById('progressTooltip');
const info = document.getElementById('info');
const contextMenu = document.getElementById('contextMenu');
const player = document.getElementById('player');
const muteButton = document.getElementById('muteButton');
const timeDisplay = document.getElementById('timeDisplay');

let loopStart = null;
let loopEnd = null;
let loopEnabled = false;
let spaceHeld = false;
let controlsTimeout;

// Initialize volume slider to video volume
volume.value = video.volume;
updateMuteIcon();

// Controls
playPause.addEventListener('click', togglePlay);
volume.addEventListener('input', () => {
  video.volume = volume.value;
  if(video.volume === 0) video.muted = true;
  else video.muted = false;
  updateMuteIcon();
});
speed.addEventListener('change', () => {
  if(!spaceHeld) {
    video.playbackRate = speed.value;
    speedDisplay.textContent = speed.value + "x";
  }
});
fullscreen.addEventListener('click', toggleFullscreen);
muteButton.addEventListener('click', () => {
  video.muted = !video.muted;
  if(video.muted) volume.value = 0;
  else volume.value = video.volume || 0.5;
  updateMuteIcon();
});

function updateMuteIcon() {
  muteButton.textContent = video.muted || video.volume === 0 ? '🔇' : '🔊';
}

// Update progress bar, A-B looping, time display
video.addEventListener('timeupdate', () => {
  if (!video.duration) return;
  const percent = (video.currentTime / video.duration) * 100;
  progressFilled.style.width = percent + '%';

  if(loopEnabled && loopStart !== null && loopEnd !== null && video.currentTime >= loopEnd) {
    video.currentTime = loopStart;
    video.play();
  }
  timeDisplay.textContent = `${formatTime(video.currentTime)} / ${formatTime(video.duration)}`;
});

// Progress bar click & tooltip
progress.addEventListener('click', e => {
  const rect = progress.getBoundingClientRect();
  const percent = (e.clientX - rect.left) / rect.width;
  video.currentTime = percent * video.duration;
});

progress.addEventListener('mousemove', e => {
  const rect = progress.getBoundingClientRect();
  let percent = (e.clientX - rect.left) / rect.width;
  percent = Math.min(Math.max(percent, 0), 1);
  let timeAt = percent * video.duration;
  progressTooltip.style.display = 'block';
  progressTooltip.style.left = `${(percent * 100)}%`;
  progressTooltip.textContent = formatTime(timeAt);
});

progress.addEventListener('mouseleave', () => {
  progressTooltip.style.display = 'none';
});

// Play/Pause toggle function
function togglePlay() {
  if(video.paused) {
    video.play();
    playPause.textContent = '⏸️';
  } else {
    video.pause();
    playPause.textContent = '▶️';
  }
}

// Fullscreen toggle function
function toggleFullscreen() {
  if(document.fullscreenElement) {
    document.exitFullscreen();
  } else {
    player.requestFullscreen();
  }
}

// Click video toggles play/pause
video.addEventListener('click', togglePlay);

// Double click video toggles fullscreen
video.addEventListener('dblclick', toggleFullscreen);

// Right-click menu handling
document.addEventListener('contextmenu', e => {
  if(e.target === video || player.contains(e.target)) {
    e.preventDefault();
    contextMenu.style.top = `${e.clientY}px`;
    contextMenu.style.left = `${e.clientX}px`;
    contextMenu.style.display = 'block';
  }
});
document.addEventListener('click', () => {
  contextMenu.style.display = 'none';
});

// Context menu actions
document.getElementById('ctx-playpause').onclick = () => { togglePlay(); contextMenu.style.display='none'; };
document.getElementById('ctx-loop').onclick = () => {
  video.loop = !video.loop;
  alert('Loop ' + (video.loop ? 'enabled' : 'disabled'));
  contextMenu.style.display='none';
};
document.getElementById('ctx-setA').onclick = () => {
  loopStart = video.currentTime;
  loopEnabled = true;
  alert(`Loop start (A) set at ${formatTime(loopStart)}`);
  contextMenu.style.display='none';
};
document.getElementById('ctx-setB').onclick = () => {
  loopEnd = video.currentTime;
  loopEnabled = true;
  alert(`Loop end (B) set at ${formatTime(loopEnd)}`);
  contextMenu.style.display='none';
};
document.getElementById('ctx-clearloop').onclick = () => {
  loopStart = null;
  loopEnd = null;
  loopEnabled = false;
  alert('A-B loop cleared');
  contextMenu.style.display='none';
};
document.getElementById('ctx-skipback').onclick = () => {
  video.currentTime = Math.max(0, video.currentTime - 10);
  contextMenu.style.display='none';
};
document.getElementById('ctx-skipforward').onclick = () => {
  video.currentTime = Math.min(video.duration, video.currentTime + 10);
  contextMenu.style.display='none';
};
document.getElementById('ctx-nextframe').onclick = () => {
  video.pause();
  video.currentTime = Math.min(video.duration, video.currentTime + 1/30);
  contextMenu.style.display='none';
};
document.getElementById('ctx-reset').onclick = () => {
  video.currentTime = 0;
  video.pause();
  playPause.textContent = '▶️';
  contextMenu.style.display='none';
};
document.getElementById('ctx-download').onclick = () => {
  const a = document.createElement('a');
  a.href = video.src;
  a.download = "<?php echo htmlspecialchars($filename); ?>";
  a.click();
  contextMenu.style.display='none';
};
document.getElementById('ctx-info').onclick = () => {
  alert(
    `File: <?php echo htmlspecialchars($filename); ?>\n` +
    `Duration: ${formatTime(video.duration)}\n` +
    `Resolution: ${video.videoWidth}x${video.videoHeight}`
  );
  contextMenu.style.display='none';
};

// Format time mm:ss
function formatTime(s) {
  if(isNaN(s)) return "0:00";
  let m = Math.floor(s / 60);
  let sec = Math.floor(s % 60);
  return `${m}:${sec.toString().padStart(2,'0')}`;
}

// Keyboard controls
document.addEventListener('keydown', e => {
  if(e.target.tagName === 'INPUT' || e.target.tagName === 'SELECT') return; // Ignore inputs

  switch(e.code) {
    case 'Space':
      if (!spaceHeld) {
        togglePlay();
        spaceHeld = true;
        // When held, speed = 2x
        video.playbackRate = 2;
        speedDisplay.textContent = '2x';
      }
      e.preventDefault();
      break;
    case 'ArrowRight':
    case 'Period': // >
      video.currentTime = Math.min(video.duration, video.currentTime + 5);
      e.preventDefault();
      break;
    case 'ArrowLeft':
    case 'Comma': // <
      video.currentTime = Math.max(0, video.currentTime - 5);
      e.preventDefault();
      break;
    case 'KeyF':
      toggleFullscreen();
      e.preventDefault();
      break;
    case 'KeyL':
      // Toggle loop on/off
      video.loop = !video.loop;
      alert('Loop ' + (video.loop ? 'enabled' : 'disabled'));
      e.preventDefault();
      break;
  }
});

document.addEventListener('keyup', e => {
  if(e.code === 'Space' && spaceHeld) {
    // Restore normal speed on space release if playing
    if(!video.paused) video.playbackRate = speed.value;
    speedDisplay.textContent = speed.value + "x";
    spaceHeld = false;
    e.preventDefault();
  }
});

// Sync playback speed select with actual speed (in case of space hold)
video.addEventListener('ratechange', () => {
  if(!spaceHeld) speed.value = video.playbackRate;
});

// Sync play/pause button if user clicks video directly
video.addEventListener('play', () => { playPause.textContent = '⏸️'; });
video.addEventListener('pause', () => { playPause.textContent = '▶️'; });

// Auto-hide controls after 3s idle
function showControls() {
  controls.classList.remove('hide');
  clearTimeout(controlsTimeout);
  controlsTimeout = setTimeout(() => {
    if(!video.paused) controls.classList.add('hide');
  }, 3000);
}
const controls = document.getElementById('controls');
player.addEventListener('mousemove', showControls);
player.addEventListener('mouseleave', () => {
  if(!video.paused) controls.classList.add('hide');
});
showControls();

</script>

</body>
</html>
