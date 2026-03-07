// script.js (ES2021) — Vanilla JS only

const $ = (sel) => document.querySelector(sel);

const canvas = $("#c");
const ctx = canvas.getContext("2d", { alpha: false });

const ui = {
  file: $("#file"),
  mode: $("#mode"),
  step: $("#step"),
  size: $("#size"),
  invert: $("#invert"),
  shuffle: $("#shuffle"),
  download: $("#download"),
};

const state = {
  W: 1000,
  H: 600,
  img: new Image(),
  imgLoaded: false,

  // sampling grid
  grid: [],
  gridW: 0,
  gridH: 0,
  scale: { x0: 0, y0: 0, cell: 7 },

  // particles
  particles: [],
  limit: 14_000, // ES2021 numeric separator

  // mouse/pointer
  pointer: { x: -9999, y: -9999, down: false },
};

const ASCII_CHARS = " .,:;i1tfLCG08@";

// Offscreen sampling canvas (fast)
const sampleCanvas =
  typeof OffscreenCanvas !== "undefined"
    ? new OffscreenCanvas(1, 1)
    : document.createElement("canvas");

const sampleCtx = sampleCanvas.getContext("2d", { willReadFrequently: true });

const clamp = (n, a, b) => Math.max(a, Math.min(b, n));

const fitCanvas = () => {
  const dpr = window.devicePixelRatio ?? 1;
  const cssW = Math.min(1050, Math.floor(window.innerWidth * 0.96));
  const cssH = Math.max(520, Math.floor(window.innerHeight * 0.68));

  state.W = Math.floor(cssW * dpr);
  state.H = Math.floor(cssH * dpr);

  canvas.width = state.W;
  canvas.height = state.H;
};

const clearBG = () => {
  ctx.fillStyle = "#070a0f";
  ctx.fillRect(0, 0, state.W, state.H);
};

const pickChar = (brightness, invert = false) => {
  const t = clamp(brightness / 255, 0, 1);
  const v = invert ? t : 1 - t;
  const idx = Math.floor(v * (ASCII_CHARS.length - 1));
  return ASCII_CHARS[idx];
};

const getCell = () => {
  const dpr = window.devicePixelRatio ?? 1;
  const step = Number(ui.step.value);
  return step * dpr;
};

const getDotRadius = () => {
  const dpr = window.devicePixelRatio ?? 1;
  return Number(ui.size.value) * dpr * 1.2;
};

const getParticleBase = () => {
  const dpr = window.devicePixelRatio ?? 1;
  return Number(ui.size.value) * dpr;
};

// -------- Image loading (modern) --------
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
      Upload an image → particles / dots / ASCII
    </text>
  </svg>`);

const loadImageFromFile = async (file) => {
  // createImageBitmap is fast decode in modern browsers
  if ("createImageBitmap" in window) {
    const bitmap = await createImageBitmap(file);
    return bitmap;
  }

  // fallback
  const url = URL.createObjectURL(file);
  const img = new Image();
  await new Promise((res, rej) => {
    img.onload = () => res();
    img.onerror = rej;
    img.src = url;
  });
  URL.revokeObjectURL(url);
  return img;
};

// -------- Build grid + particles --------
const rebuildFromSource = (source) => {
  const cell = getCell();
  const dpr = window.devicePixelRatio ?? 1;

  // sample size based on canvas and step (grid resolution)
  const maxSampleW = Math.floor(state.W / dpr / Number(ui.step.value));
  const maxSampleH = Math.floor(state.H / dpr / Number(ui.step.value));

  const srcW = source.width ?? source.naturalWidth ?? 1;
  const srcH = source.height ?? source.naturalHeight ?? 1;
  const aspect = srcW / srcH;

  let sw = Math.max(20, maxSampleW);
  let sh = Math.floor(sw / aspect);
  if (sh > maxSampleH) {
    sh = Math.max(20, maxSampleH);
    sw = Math.floor(sh * aspect);
  }

  sampleCanvas.width = sw;
  sampleCanvas.height = sh;

  sampleCtx.clearRect(0, 0, sw, sh);
  sampleCtx.drawImage(source, 0, 0, sw, sh);

  const { data } = sampleCtx.getImageData(0, 0, sw, sh);

  state.gridW = sw;
  state.gridH = sh;
  state.grid = new Array(sw * sh);

  const drawW = sw * cell;
  const drawH = sh * cell;
  const x0 = Math.floor((state.W - drawW) / 2);
  const y0 = Math.floor((state.H - drawH) / 2);

  state.scale = { x0, y0, cell };

  // grid fill
  let idx = 0;
  for (let y = 0; y < sh; y++) {
    for (let x = 0; x < sw; x++) {
      const o = idx * 4;
      const r = data[o];
      const g = data[o + 1];
      const b = data[o + 2];
      const a = data[o + 3];
      const br = (r + g + b) / 3;
      state.grid[idx] = { x, y, r, g, b, a, br };
      idx++;
    }
  }

  // particles
  const base = getParticleBase();
  state.particles = [];

  for (let i = 0; i < state.grid.length; i++) {
    const p = state.grid[i];
    if (p.a < 20) continue;

    const tx = x0 + p.x * cell;
    const ty = y0 + p.y * cell;

    const size = base + (1 - p.br / 255) * (base * 0.9);

    state.particles.push({
      x: Math.random() * state.W,
      y: Math.random() * state.H,
      vx: 0,
      vy: 0,
      tx,
      ty,
      r: p.r,
      g: p.g,
      b: p.b,
      size,
    });

    if (state.particles.length >= state.limit) break;
  }
};

const rebuild = () => {
  if (!state.imgLoaded) return;
  rebuildFromSource(state.img);
};

// -------- Render modes --------
const drawParticles = () => {
  clearBG();

  const dpr = window.devicePixelRatio ?? 1;
  const repelR = (state.pointer.down ? 180 : 120) * dpr;
  const repelR2 = repelR * repelR;

  for (const p of state.particles) {
    // spring to target
    const dx = p.tx - p.x;
    const dy = p.ty - p.y;

    p.vx = (p.vx + dx * 0.035) * 0.82;
    p.vy = (p.vy + dy * 0.035) * 0.82;

    // repel
    const mx = p.x - state.pointer.x;
    const my = p.y - state.pointer.y;
    const d2 = mx * mx + my * my;

    if (d2 < repelR2) {
      const d = Math.sqrt(d2) || 1;
      const force = (1 - d / repelR) * (state.pointer.down ? 10 : 5);
      p.vx += (mx / d) * force;
      p.vy += (my / d) * force;
    }

    p.x += p.vx;
    p.y += p.vy;

    ctx.fillStyle = `rgb(${p.r},${p.g},${p.b})`;
    ctx.beginPath();
    ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
    ctx.fill();
  }
};

const drawDots = () => {
  clearBG();

  const { x0, y0, cell } = state.scale;
  const radius = getDotRadius();

  ctx.globalCompositeOperation = "lighter";

  for (const p of state.grid) {
    if (p?.a < 20) continue; // optional chaining (?.)

    const x = x0 + p.x * cell;
    const y = y0 + p.y * cell;

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
};

const drawASCII = () => {
  clearBG();

  const { x0, y0, cell } = state.scale;
  const invert = ui.invert.checked ?? false; // nullish coalescing (??)

  const fontSize = Math.max(6, Math.floor(cell * 0.95));
  ctx.font = `${fontSize}px ui-monospace, SFMono-Regular, Menlo, Consolas, monospace`;
  ctx.textBaseline = "top";

  for (const p of state.grid) {
    if (p?.a < 20) continue;

    const x = x0 + p.x * cell;
    const y = y0 + p.y * cell;

    const ch = pickChar(p.br, invert);
    ctx.fillStyle = `rgb(${p.r},${p.g},${p.b})`;
    ctx.fillText(ch, x, y);
  }
};

// -------- Loop --------
const tick = () => {
  if (!state.imgLoaded) {
    clearBG();
    ctx.fillStyle = "#e8eef6";
    const dpr = window.devicePixelRatio ?? 1;
    ctx.font = `${16 * dpr}px sans-serif`;
    ctx.fillText("Loading image...", 20 * dpr, 30 * dpr);
    requestAnimationFrame(tick);
    return;
  }

  const m = ui.mode.value;
  if (m === "particles") drawParticles();
  else if (m === "dots") drawDots();
  else drawASCII();

  requestAnimationFrame(tick);
};

// -------- Events (Pointer = mouse + touch) --------
canvas.addEventListener("pointermove", (e) => {
  const r = canvas.getBoundingClientRect();
  const dpr = window.devicePixelRatio ?? 1;
  state.pointer.x = (e.clientX - r.left) * dpr;
  state.pointer.y = (e.clientY - r.top) * dpr;
});

canvas.addEventListener("pointerleave", () => {
  state.pointer.x = -9999;
  state.pointer.y = -9999;
});

canvas.addEventListener("pointerdown", () => {
  // ES2021 logical assignment example
  state.pointer.down ||= true;
});

canvas.addEventListener("pointerup", () => {
  state.pointer.down &&= false; // logical assignment
});

ui.file.addEventListener("change", async () => {
  const f = ui.file.files?.[0];
  if (!f) return;

  state.imgLoaded = false;

  try {
    const src = await loadImageFromFile(f);

    // If src is ImageBitmap, draw it into state.img using a temp canvas
    if ("close" in src) {
      const tmp =
        typeof OffscreenCanvas !== "undefined"
          ? new OffscreenCanvas(src.width, src.height)
          : Object.assign(document.createElement("canvas"), {
              width: src.width,
              height: src.height,
            });

      const tctx = tmp.getContext("2d");
      tctx.drawImage(src, 0, 0);
      state.img = new Image();
      state.img.onload = () => {
        state.imgLoaded = true;
        rebuild();
      };
      state.img.src = tmp.toDataURL("image/png");
      src.close?.();
      return;
    }

    // fallback image element
    state.img = src;
    state.imgLoaded = true;
    rebuild();
  } catch (err) {
    console.error(err);
    alert("Image load failed!");
  }
});

["input", "change"].forEach((ev) => {
  ui.step.addEventListener(ev, rebuild);
  ui.size.addEventListener(ev, rebuild);
  ui.mode.addEventListener(ev, rebuild);
  ui.invert.addEventListener(ev, () => {}); // only affects ASCII draw realtime
});

ui.shuffle.addEventListener("click", () => {
  for (const p of state.particles) {
    p.x = Math.random() * state.W;
    p.y = Math.random() * state.H;
    p.vx = (Math.random() - 0.5) * 14;
    p.vy = (Math.random() - 0.5) * 14;
  }
});

ui.download.addEventListener("click", async () => {
  // Better download: toBlob (async)
  const blob = await new Promise((res) => canvas.toBlob(res, "image/png"));
  if (!blob) return;
  const url = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.download = "image-art.png";
  a.href = url;
  a.click();
  URL.revokeObjectURL(url);
});

// -------- Init --------
fitCanvas();
window.addEventListener("resize", () => {
  fitCanvas();
  rebuild();
});

// default image
state.img.onload = () => {
  state.imgLoaded = true;
  rebuild();
};
state.img.src = DEFAULT_SRC;

// start animation
tick();
