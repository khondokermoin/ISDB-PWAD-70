// ========= Canvas setup =========
const canvas = document.getElementById("c");
const ctx = canvas.getContext("2d", { alpha: false });

const fileInput = document.getElementById("file");
const modeEl = document.getElementById("mode");
const stepEl = document.getElementById("step");
const sizeEl = document.getElementById("size");
const invertEl = document.getElementById("invert");
const shuffleBtn = document.getElementById("shuffle");
const downloadBtn = document.getElementById("download");

// Offscreen canvas for image sampling
const off = document.createElement("canvas");
const offCtx = off.getContext("2d", { willReadFrequently: true });

let W = 1000,
  H = 600;
function fitCanvas() {
  // render resolution (actual pixels)
  const cssW = Math.min(1050, Math.floor(window.innerWidth * 0.96));
  const cssH = Math.max(520, Math.floor(window.innerHeight * 0.68));
  W = Math.floor(cssW * devicePixelRatio);
  H = Math.floor(cssH * devicePixelRatio);

  canvas.width = W;
  canvas.height = H;
}
fitCanvas();
window.addEventListener("resize", () => {
  fitCanvas();
  if (imgLoaded) rebuildFromImage();
});

// ========= Interaction (mouse repulsion) =========
const mouse = { x: -9999, y: -9999, down: false };
canvas.addEventListener("mousemove", (e) => {
  const r = canvas.getBoundingClientRect();
  mouse.x = (e.clientX - r.left) * devicePixelRatio;
  mouse.y = (e.clientY - r.top) * devicePixelRatio;
});
canvas.addEventListener("mouseleave", () => {
  mouse.x = -9999;
  mouse.y = -9999;
});
canvas.addEventListener("mousedown", () => (mouse.down = true));
canvas.addEventListener("mouseup", () => (mouse.down = false));

// ========= Image + sampling =========
let img = new Image();
img.crossOrigin = "anonymous";
let imgLoaded = false;

const DEFAULT_SRC =
  "data:image/svg+xml;charset=utf-8," +
  encodeURIComponent(`
  <svg xmlns="http://www.w3.org/2000/svg" width="1200" height="800">
    <defs>
      <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
        <stop offset="0" stop-color="#7aa7ff"/><stop offset="1" stop-color="#ff6bd6"/>
      </linearGradient>
    </defs>
    <rect width="1200" height="800" fill="#0b0f14"/>
    <circle cx="420" cy="360" r="240" fill="url(#g)" opacity=".9"/>
    <circle cx="760" cy="420" r="220" fill="#2be7ff" opacity=".55"/>
    <text x="80" y="740" fill="#e8eef6" opacity=".9" font-size="64" font-family="monospace">
      Upload an image → get particles / dots / ASCII
    </text>
  </svg>`);

img.onload = () => {
  imgLoaded = true;
  rebuildFromImage();
};
img.src = DEFAULT_SRC;

fileInput.addEventListener("change", () => {
  const f = fileInput.files?.[0];
  if (!f) return;
  const url = URL.createObjectURL(f);
  imgLoaded = false;
  img = new Image();
  img.onload = () => {
    imgLoaded = true;
    rebuildFromImage();
    URL.revokeObjectURL(url);
  };
  img.src = url;
});

// ========= Particles =========
let particles = [];
let grid = []; // for ASCII/dots render
let gridW = 0,
  gridH = 0;
let scaleInfo = { x0: 0, y0: 0, w: 0, h: 0, cell: 7 };

function clamp(n, a, b) {
  return Math.max(a, Math.min(b, n));
}

function rebuildFromImage() {
  const step = Number(stepEl.value);
  const cell = step * devicePixelRatio;

  // Draw image into offscreen canvas with aspect-fit into a smaller buffer for sampling
  // We sample on offscreen at roughly same resolution as we want to render grid.
  const maxSampleW = Math.floor(W / devicePixelRatio / step);
  const maxSampleH = Math.floor(H / devicePixelRatio / step);

  // Maintain image aspect ratio in sampling grid
  const aspect = img.width / img.height;
  let sw = maxSampleW;
  let sh = Math.floor(sw / aspect);
  if (sh > maxSampleH) {
    sh = maxSampleH;
    sw = Math.floor(sh * aspect);
  }

  sw = Math.max(20, sw);
  sh = Math.max(20, sh);

  off.width = sw;
  off.height = sh;

  offCtx.clearRect(0, 0, sw, sh);
  offCtx.drawImage(img, 0, 0, sw, sh);

  const data = offCtx.getImageData(0, 0, sw, sh).data;

  gridW = sw;
  gridH = sh;
  grid = new Array(sw * sh);

  // Center placement on main canvas
  const drawW = sw * cell;
  const drawH = sh * cell;
  const x0 = Math.floor((W - drawW) / 2);
  const y0 = Math.floor((H - drawH) / 2);
  scaleInfo = { x0, y0, w: drawW, h: drawH, cell };

  // Build grid (color + brightness)
  let idx = 0;
  for (let y = 0; y < sh; y++) {
    for (let x = 0; x < sw; x++) {
      const o = idx * 4;
      const r = data[o],
        g = data[o + 1],
        b = data[o + 2],
        a = data[o + 3];
      const br = (r + g + b) / 3;
      grid[idx] = { x, y, r, g, b, a, br };
      idx++;
    }
  }

  // Build particles (skip mostly transparent pixels if image has alpha)
  const sizeBase = Number(sizeEl.value) * devicePixelRatio;
  particles = [];
  const targetCountLimit = 14000; // performance safety

  for (let i = 0; i < grid.length; i++) {
    const p = grid[i];
    if (p.a < 20) continue; // skip transparent
    const tx = x0 + p.x * cell;
    const ty = y0 + p.y * cell;

    // brightness -> size (darker -> bigger a bit)
    const s = sizeBase + (1 - p.br / 255) * (sizeBase * 0.9);

    particles.push({
      x: Math.random() * W,
      y: Math.random() * H,
      vx: 0,
      vy: 0,
      tx,
      ty,
      r: p.r,
      g: p.g,
      b: p.b,
      size: s,
    });

    if (particles.length > targetCountLimit) break;
  }
}

stepEl.addEventListener("input", () => imgLoaded && rebuildFromImage());
sizeEl.addEventListener("input", () => imgLoaded && rebuildFromImage());
modeEl.addEventListener("change", () => imgLoaded && rebuildFromImage());

shuffleBtn.addEventListener("click", () => {
  // Scatter particles (pict2pix-like)
  for (const p of particles) {
    p.x = Math.random() * W;
    p.y = Math.random() * H;
    p.vx = (Math.random() - 0.5) * 14;
    p.vy = (Math.random() - 0.5) * 14;
  }
});

// Download current canvas
downloadBtn.addEventListener("click", () => {
  const a = document.createElement("a");
  a.download = "image-art.png";
  a.href = canvas.toDataURL("image/png");
  a.click();
});

// ========= Render modes =========
const ASCII_CHARS = " .,:;i1tfLCG08@";
function pickChar(br, invert) {
  // br 0 (dark) -> high density char
  const t = clamp(br / 255, 0, 1);
  const v = invert ? t : 1 - t;
  const idx = Math.floor(v * (ASCII_CHARS.length - 1));
  return ASCII_CHARS[idx];
}

function clearBG() {
  // Nice dark bg
  ctx.fillStyle = "#070a0f";
  ctx.fillRect(0, 0, W, H);
}

// Particles: spring to target + mouse repel
function drawParticles() {
  clearBG();

  const repelR = (mouse.down ? 180 : 120) * devicePixelRatio;
  const repelR2 = repelR * repelR;

  for (const p of particles) {
    // spring toward target
    const dx = p.tx - p.x;
    const dy = p.ty - p.y;

    p.vx = (p.vx + dx * 0.035) * 0.82;
    p.vy = (p.vy + dy * 0.035) * 0.82;

    // mouse repel
    const mx = p.x - mouse.x;
    const my = p.y - mouse.y;
    const d2 = mx * mx + my * my;

    if (d2 < repelR2) {
      const d = Math.sqrt(d2) || 1;
      const force = (1 - d / repelR) * (mouse.down ? 10 : 5);
      p.vx += (mx / d) * force;
      p.vy += (my / d) * force;
    }

    p.x += p.vx;
    p.y += p.vy;

    // draw
    ctx.fillStyle = `rgb(${p.r},${p.g},${p.b})`;
    ctx.beginPath();
    ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
    ctx.fill();
  }
}

// Dots/LED matrix: colored circles at grid positions
function drawDots() {
  clearBG();
  const { x0, y0, cell } = scaleInfo;
  const radius = Number(sizeEl.value) * devicePixelRatio * 1.2;

  // small glow
  ctx.globalCompositeOperation = "lighter";

  for (let i = 0; i < grid.length; i++) {
    const p = grid[i];
    if (p.a < 20) continue;

    const x = x0 + p.x * cell;
    const y = y0 + p.y * cell;

    // brightness adjust: dim very bright pixels slightly (nice look)
    const k = 0.65 + (1 - p.br / 255) * 0.55;
    const r = Math.floor(p.r * k);
    const g = Math.floor(p.g * k);
    const b = Math.floor(p.b * k);

    ctx.fillStyle = `rgb(${r},${g},${b})`;
    ctx.beginPath();
    ctx.arc(x, y, radius, 0, Math.PI * 2);
    ctx.fill();
  }

  ctx.globalCompositeOperation = "source-over";
}

// Color ASCII: draw colored characters
function drawASCII() {
  clearBG();
  const { x0, y0, cell } = scaleInfo;

  const fontSize = Math.max(6, Math.floor(cell * 0.95));
  ctx.font = `${fontSize}px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace`;
  ctx.textBaseline = "top";

  const invert = invertEl.checked;

  for (let i = 0; i < grid.length; i++) {
    const p = grid[i];
    if (p.a < 20) continue;

    const x = x0 + p.x * cell;
    const y = y0 + p.y * cell;

    const ch = pickChar(p.br, invert);
    ctx.fillStyle = `rgb(${p.r},${p.g},${p.b})`;
    ctx.fillText(ch, x, y);
  }
}

// ========= Animation loop =========
function tick() {
  const m = modeEl.value;
  if (!imgLoaded) {
    clearBG();
    ctx.fillStyle = "#e8eef6";
    ctx.font = `${16 * devicePixelRatio}px sans-serif`;
    ctx.fillText(
      "Loading image...",
      20 * devicePixelRatio,
      30 * devicePixelRatio
    );
    requestAnimationFrame(tick);
    return;
  }

  if (m === "particles") drawParticles();
  else if (m === "dots") drawDots();
  else drawASCII();

  requestAnimationFrame(tick);
}

tick();
