const API_BASE = "/api";

async function api(path, { method = "GET", body, headers = {} } = {}) {
  const opts = { method, headers: { ...headers } };
  if (body !== undefined) {
    opts.headers["Content-Type"] = "application/json";
    opts.body = JSON.stringify(body);
  }

  const res = await fetch(API_BASE + path, opts);
  const ct = res.headers.get("content-type") || "";
  const data = ct.includes("application/json") ? await res.json() : await res.text();

  if (!res.ok) {
    let msg = (data && data.error) ? data.error : (typeof data === "string" ? data : "Request failed");
    if (typeof msg === "string" && msg.includes("<!DOCTYPE html")) msg = "Request blocked by server (HTML error).";
    throw new Error(msg);
  }
  return data;
}

function qs(sel) { return document.querySelector(sel); }
function qsa(sel) { return [...document.querySelectorAll(sel)]; }

// Old formatter (kept for admin totals etc.)
function fmtMins(mins) {
  mins = Number(mins || 0);
  const h = Math.floor(mins / 60);
  const m = Math.round(mins % 60);
  return `${h}h ${m}m`;
}

// NEW: show minutes + seconds (from minutes float/int)
function fmtDurationFromMinutes(mins) {
  mins = Number(mins ?? 0);
  const totalSeconds = Math.max(0, Math.round(mins * 60));
  const m = Math.floor(totalSeconds / 60);
  const s = totalSeconds % 60;
  return `${m}m ${String(s).padStart(2, "0")}s`;
}

// NEW: "YYYY-MM-DD HH:MM:SS" -> "h:mm:ss AM/PM"
function fmtTime12hFromDateTime(dtStr) {
  if (!dtStr) return "—";
  const str = String(dtStr);
  const time = str.includes(" ") ? str.split(" ")[1] : str;
  const parts = time.split(":");
  if (parts.length < 2) return dtStr;

  const hh = Number(parts[0]);
  const mm = Number(parts[1] || 0);
  const ss = Number(parts[2] || 0);

  const isPM = hh >= 12;
  let h = hh % 12;
  if (h === 0) h = 12;

  return `${h}:${String(mm).padStart(2, "0")}:${String(ss).padStart(2, "0")} ${isPM ? "PM" : "AM"}`;
}

async function meOrRedirect(role) {
  try {
    const r = await api("/auth/me");
    const u = r.user;
    if (role && u.role !== role) throw new Error("Forbidden");
    return u;
  } catch (e) {
    location.href = "/public/index.html";
  }
}

async function logout() {
  await api("/auth/logout", { method: "POST" });
  location.href = "/public/index.html";
}

// Local date helpers (avoid UTC toISOString() date drift)
function isoLocalDate(d = new Date()){
  const tz = d.getTimezoneOffset() * 60000;
  return new Date(d.getTime() - tz).toISOString().slice(0,10);
}
function isoLocalMonth(d = new Date()){
  return isoLocalDate(d).slice(0,7);
}
