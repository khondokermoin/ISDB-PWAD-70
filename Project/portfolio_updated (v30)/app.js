    // -----------------------------
// -----------------------------
// Config
// -----------------------------
const CONTACT_EMAIL = "khondokermoin2k23@gmail.com";

// -----------------------------
// Typewriter Effect (Stable + No Layout Shift)
// -----------------------------
const heading = document.getElementById("typewriter");
const words = [
  "Software Engineer",
  "Network Associate",
  "Android Developer",
  "Problem Solver"
];

// respects OS reduced-motion setting
const prefersReducedMotion =
  window.matchMedia &&
  window.matchMedia("(prefers-reduced-motion: reduce)").matches;

// state
let wordIndex = 0;
let charIndex = 0;
let deleting = false;
let typeTimer = null;

// keep a stable width based on the longest word (prevents layout shift)
function setTypewriterWidth() {
  if (!heading || !words.length) return;
  const longest = words.reduce((a, b) => (b.length > a.length ? b : a), "");
  // +1ch for caret breathing room
  heading.style.width = `${longest.length + 1}ch`;
}

// main loop
function tick() {
  if (!heading) return;

  if (prefersReducedMotion) {
    heading.textContent = words[0] || "";
    return;
  }

  const current = words[wordIndex] || "";
  const delta = deleting ? 50 : 100;

  if (!deleting) {
    charIndex++;
    heading.textContent = current.slice(0, charIndex);

    if (charIndex >= current.length) {
      deleting = true;
      typeTimer = setTimeout(tick, 1500);
      return;
    }
  } else {
    charIndex--;
    heading.textContent = current.slice(0, Math.max(0, charIndex));

    if (charIndex <= 0) {
      deleting = false;
      wordIndex = (wordIndex + 1) % words.length;
      typeTimer = setTimeout(tick, 500);
      return;
    }
  }

  typeTimer = setTimeout(tick, delta);
}

// init (app.js is loaded with `defer`, so DOM is ready)
setTypewriterWidth();
window.addEventListener("resize", setTypewriterWidth, { passive: true });
tick();

// -----------------------------
// Global image fallback (keeps HTML clean)
// -----------------------------
function handleBrokenImage(img) {
  const kind = img.getAttribute("data-img-kind") || "";
  if (kind === "project") {
    if (!img.dataset.fallbackApplied) {
      img.dataset.fallbackApplied = "1";
      img.src = "assets/projects/placeholder.svg";
    }
    return;
  }

  if (kind === "certificate") {
    const msg = img.getAttribute("data-fallback-text") || "Image not found";
    const parent = img.parentElement;
    if (!parent) return;
    img.remove();
    parent.classList.add("flex", "items-center", "justify-center");
    parent.innerHTML = `<span class="text-slate-400 text-sm font-mono">${msg}</span>`;
    return;
  }

  // generic
  if (!img.dataset.fallbackApplied) {
    img.dataset.fallbackApplied = "1";
    img.src = "assets/projects/placeholder.svg";
  }
}

document.addEventListener(
  "error",
  (e) => {
    const t = e.target;
    if (t instanceof HTMLImageElement) handleBrokenImage(t);
  },
  true
);

// -----------------------------
// CV dropdown menu
// -----------------------------

    // -----------------------------
    const cvBtn = document.getElementById('cvBtn');
    const cvMenu = document.getElementById('cvMenu');
    const cvRoot = document.getElementById('cvDropdown');

    function closeCvMenu() {
      if (!cvMenu || !cvBtn) return;
      cvMenu.classList.add('hidden');
      cvBtn.setAttribute('aria-expanded', 'false');
    }

    function toggleCvMenu() {
      if (!cvMenu || !cvBtn) return;
      const isOpen = !cvMenu.classList.contains('hidden');
      if (isOpen) closeCvMenu();
      else {
        cvMenu.classList.remove('hidden');
        cvBtn.setAttribute('aria-expanded', 'true');
      }
    }

    cvBtn?.addEventListener('click', (e) => {
      e.stopPropagation();
      toggleCvMenu();
    });

    document.addEventListener('click', (e) => {
      if (!cvRoot) return;
      if (!cvRoot.contains(e.target)) closeCvMenu();
    });

    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') closeCvMenu();
    });

    // -----------------------------
    // Certificates by skill (rendered from data)
    // -----------------------------
    const certificates = [
      {
        title: 'CCNA (Cisco Certified Network Associate)',
        issuer: 'Daffodil International Professional Training Institute',
        category: 'network',
        date: '2024',
        file: 'assets/certificates/ccna.jpg',
      },
      {
        title: 'Android Application Development (Kotlin)',
        issuer: 'Daffodil International Professional Training Institute',
        category: 'mobile',
        date: '2024',
        file: 'assets/certificates/android-kotlin.jpg',
      },
      {
        title: 'Diploma in Software Engineering (Java)',
        issuer: 'Course / Institute',
        category: 'mobile',
        date: 'Jun 2022 – May 2023',
        file: 'assets/certificates/java.jpg',
      },
    ];

    const certGrid = document.getElementById('certGrid');

    function certCard(cert) {
      const safeTitle = String(cert.title || "").replace(/</g, '&lt;').replace(/>/g, '&gt;');
      const safeIssuer = String(cert.issuer || "").replace(/</g, '&lt;').replace(/>/g, '&gt;');
      const safeDate = String(cert.date || "").replace(/</g, '&lt;').replace(/>/g, '&gt;');
      const cat = String(cert.category || "").toUpperCase();
      const file = String(cert.file || "").trim();

      return `
        <article class="glass-card rounded-2xl overflow-hidden">
          <div class="aspect-[16/10] bg-slate-900/40 border-b border-white/5">
            <img src="${file}" alt="${safeTitle} certificate" loading="lazy" class="w-full h-full object-cover" data-img-kind="certificate" data-fallback-text="Certificate file not found" />
          </div>
          <div class="p-6">
            <div class="flex items-start justify-between gap-4">
              <h3 class="font-bold text-white">${safeTitle}</h3>
              <span class="text-xs font-mono text-slate-400">${cat}</span>
            </div>
            <p class="text-slate-400 text-sm mt-2">${safeIssuer}</p>
            <p class="text-slate-500 text-xs font-mono mt-3">${safeDate}</p>

            <div class="mt-5">
              ${file ? `
                <a href="${file}" target="_blank" rel="noopener noreferrer"
                   class="inline-flex items-center gap-2 text-cyan-400 hover:underline text-sm font-mono">
                  View file <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
                </a>` : ``}
            </div>
          </div>
        </article>
      `;
    }

    function renderCertificates(filterCategory = 'all') {
      if (!certGrid) return;
      const items = filterCategory === 'all'
        ? certificates
        : certificates.filter(c => c.category === filterCategory);

      certGrid.innerHTML = items.map(certCard).join('');
    }

    function setActiveTab(category) {
      document.querySelectorAll('.cert-tab').forEach(btn => {
        const active = btn.dataset.category === category;
        btn.setAttribute('aria-selected', active ? 'true' : 'false');
        btn.classList.toggle('bg-slate-800', active);
        btn.classList.toggle('border-cyan-500/50', active);
      });
    }

    document.querySelectorAll('.cert-tab').forEach(btn => {
      btn.addEventListener('click', () => {
        const category = btn.dataset.category;
        setActiveTab(category);
        renderCertificates(category);
      });
    });

    // initial render
    setActiveTab('all');
    renderCertificates('all');
  

    // -----------------------------
    // Projects (Pro++++ upgrade): data + search/filter/sort + case-study modal
    // -----------------------------
    const projects = [
  {
    id: "erp-pos-integration",
    title: "ERP & POS Inventory Synchronization",
    subtitle: "Real-time stock & order sync between Laravel ERP and WordPress POS",
    category: "web",
    year: 2025,
    featured: true,
    impactScore: 9,
    badges: ["Laravel", "WordPress", "REST API", "MySQL"],
    stack: ["Laravel", "PHP", "WordPress", "REST APIs", "MySQL", "JWT Authentication"],
    bullets: [
      "Designed a bidirectional API system to synchronize inventory and orders between an ERP backend and a WordPress-based POS.",
      "Handled data consistency challenges such as race conditions, duplicate orders, and partial sync failures.",
      "Implemented secure API authentication and rate limiting to prevent data corruption and abuse."
    ],
    impact: [
      { label: "Stock Accuracy", value: "99%+" },
      { label: "Manual Updates", value: "Eliminated" },
      { label: "Sync Latency", value: "< 2s" }
    ],
    github: "https://github.com/",
    demo: "#",
    image: "assets/projects/erp-pos.png"
  },
  {
    id: "invoice-generator",
    title: "Invoice Generator & Business Management Tool",
    subtitle: "Lightweight invoicing system (offline-first) with PDF export",
    category: "mobile",
    year: 2025,
    featured: true,
    impactScore: 8,
    badges: ["Android", "Kotlin", "SQLite", "PDF"],
    stack: ["Kotlin", "Android", "Room (SQLite)", "PDF Generation", "MVVM Architecture"],
    bullets: [
      "Built a clean MVVM-based Android app for creating, storing, and exporting professional invoices.",
      "Designed UI flows for non-technical business owners, focusing on speed and clarity.",
      "Implemented local persistence and PDF export for offline usage."
    ],
    impact: [
      { label: "Offline Support", value: "100%" },
      { label: "Invoice Creation Time", value: "< 30s" },
      { label: "Target Users", value: "Small Businesses" }
    ],
    github: "https://github.com/",
    demo: "#",
    image: "assets/projects/invoice-app.png"
  },
  {
    id: "luxury-marketing-assets",
    title: "Luxury Product Marketing Assets",
    subtitle: "High-end promotional visuals for premium products",
    category: "creative",
    year: 2024,
    featured: false,
    impactScore: 7,
    badges: ["Video", "Branding", "Marketing"],
    stack: ["Visual Storytelling", "Motion Design", "Brand Positioning", "Creative Direction"],
    bullets: [
      "Created visually compelling promotional videos and digital assets for luxury products.",
      "Focused on brand tone, emotional appeal, and premium aesthetics rather than raw specifications.",
      "Collaborated with business owners to align visuals with target market expectations."
    ],
    impact: [
      { label: "Brand Perception", value: "Improved" },
      { label: "Audience Engagement", value: "High" }
    ],
    github: "",
    demo: "#",
    image: "assets/projects/luxury-media.png"
  }
];

    const projectGrid = document.getElementById("projectGrid");
    const projectSearch = document.getElementById("projectSearch");
    const projectFilter = document.getElementById("projectFilter");
    const projectSort = document.getElementById("projectSort");

    function badgePill(text) {
      const safe = String(text).replace(/</g, "&lt;").replace(/>/g, "&gt;");
      return `<span class="px-2.5 py-1 rounded-full text-xs font-mono border border-slate-700 bg-slate-900/40">${safe}</span>`;
    }

    function projectCard(p) {
      const safeTitle = p.title.replace(/</g, "&lt;").replace(/>/g, "&gt;");
      const safeSub = p.subtitle.replace(/</g, "&lt;").replace(/>/g, "&gt;");
      const badgeHtml = (p.badges || []).slice(0, 4).map(badgePill).join("");
      const img = p.image || "assets/projects/placeholder.svg";

      const github = (p.github || "").trim();
      const demo = (p.demo || "").trim();
      const hasGithub = !isPlaceholderLink(github);
      const hasDemo = !isPlaceholderLink(demo);

      const githubBtn = hasGithub ? `
              <a class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-700 text-sm"
                 href="${github}" target="_blank" rel="noopener noreferrer">
                <i class="fa-brands fa-github mr-2"></i>Code
              </a>` : ``;

      const demoBtn = hasDemo ? `
              <a class="px-4 py-2 rounded-lg border border-slate-700 hover:border-white text-sm"
                 href="${demo}" target="_blank" rel="noopener noreferrer">
                Demo
              </a>` : ``;

      return `
        <article class="glass-card rounded-2xl overflow-hidden">
          <div class="aspect-[16/10] bg-slate-900/40 border-b border-white/5">
            <img src="${img}" alt="${safeTitle} screenshot" loading="lazy" class="w-full h-full object-cover" data-img-kind="project" />
          </div>
          <div class="p-6">
            <div class="flex items-start justify-between gap-4">
              <h3 class="text-lg font-bold">${safeTitle}</h3>
              <span class="text-xs font-mono text-slate-400">${String(p.year || "").replace(/</g,"&lt;")}</span>
            </div>
            <p class="text-slate-400 text-sm mt-2">${safeSub}</p>
            <div class="flex flex-wrap gap-2 mt-4">${badgeHtml}</div>

            <div class="mt-5 flex flex-wrap gap-3">
              <button class="px-4 py-2 rounded-lg bg-cyan-500 text-black font-bold hover:bg-cyan-400 transition text-sm"
                      data-open-project="${p.id}">
                Case Study
              </button>
${githubBtn}
              ${demoBtn}
            </div>
          </div>
        </article>
      `;
    }

    function normalize(str) {
      return String(str || "").toLowerCase().trim();
    }

    function getVisibleProjects() {
      const q = normalize(projectSearch?.value);
      const cat = projectFilter?.value || "all";
      const sort = projectSort?.value || "featured";

      let items = [...projects];

      if (cat !== "all") items = items.filter(p => p.category === cat);

      if (q) {
        items = items.filter(p => {
          const hay = [
            p.title, p.subtitle, p.category, p.year,
            ...(p.badges || []), ...(p.stack || []), ...(p.bullets || [])
          ].join(" ");
          return normalize(hay).includes(q);
        });
      }

      if (sort === "newest") items.sort((a, b) => (b.year || 0) - (a.year || 0));
      else if (sort === "impact") items.sort((a, b) => (b.impactScore || 0) - (a.impactScore || 0));
      else { // featured
        items.sort((a, b) => (b.featured === true) - (a.featured === true) || (b.year || 0) - (a.year || 0));
      }

      return items;
    }

    function renderProjects() {
      if (!projectGrid) return;
      const items = getVisibleProjects();
      projectGrid.innerHTML = items.map(projectCard).join("") || `
        <div class="glass-card p-8 rounded-2xl sm:col-span-2 lg:col-span-3">
          <p class="text-slate-300">No projects matched your search/filter.</p>
          <p class="text-slate-500 text-sm mt-2">Try removing filters or changing the search keywords.</p>
        </div>
      `;
    }

    // Modal logic
    const modal = document.getElementById("projectModal");
    const modalTitle = document.getElementById("modalTitle");
    const modalSubtitle = document.getElementById("modalSubtitle");
    const modalBadges = document.getElementById("modalBadges");
    const modalImage = document.getElementById("modalImage");
    const modalBullets = document.getElementById("modalBullets");
    const modalStack = document.getElementById("modalStack");
    const modalImpact = document.getElementById("modalImpact");
    const modalGithub = document.getElementById("modalGithub");
    const modalDemo = document.getElementById("modalDemo");
    const modalPrimary = document.getElementById("modalPrimary");
    const modalClose = document.getElementById("modalClose");
    const modalBackdrop = document.getElementById("modalBackdrop");
const mainEl = document.getElementById("main") || document.querySelector("main");
let modalOpen = false;

function getFocusable(container) {
  if (!container) return [];
  const selectors = [
    'a[href]:not([tabindex="-1"])',
    'button:not([disabled]):not([tabindex="-1"])',
    'input:not([disabled]):not([tabindex="-1"])',
    'select:not([disabled]):not([tabindex="-1"])',
    'textarea:not([disabled]):not([tabindex="-1"])',
    '[tabindex]:not([tabindex="-1"])'
  ].join(",");
  return Array.from(container.querySelectorAll(selectors)).filter((el) => {
    if (!(el instanceof HTMLElement)) return false;
    const style = window.getComputedStyle(el);
    return style.visibility !== "hidden" && style.display !== "none";
  });
}

function setBackgroundInert(isInert) {
  // Best effort: `inert` where supported, plus aria-hidden fallback.
  if (mainEl) {
    try { mainEl.inert = !!isInert; } catch (_) {}
    mainEl.setAttribute("aria-hidden", isInert ? "true" : "false");
  }
}

// Treat obvious placeholders as invalid links.
// (Example: leaving GitHub as "https://github.com/" makes users think the repo exists.)
function isPlaceholderLink(href) {
  const url = String(href || "").trim();
  if (!url || url === "#") return true;
  // GitHub root (with/without trailing slash)
  if (/^https?:\/\/(www\.)?github\.com\/?$/i.test(url)) return true;
  return false;
}


    function safeLink(el, href) {
      if (!el) return;
      const url = (href || "").trim();
      const ok = !isPlaceholderLink(url);
      el.href = ok ? url : "#";
      el.classList.toggle("hidden", !ok);
      el.setAttribute("aria-hidden", String(!ok));
      if (!ok) el.setAttribute("tabindex", "-1");
      else el.removeAttribute("tabindex");
    }


    let lastFocus = null;

    function openProject(id) {
      const p = projects.find(x => x.id === id);
      if (!p) return;
      if (!modal || !modalTitle || !modalSubtitle || !modalBadges || !modalImage || !modalBullets || !modalStack || !modalImpact || !modalClose) return;

      modalTitle.textContent = p.title;
      modalSubtitle.textContent = p.subtitle;
      modalBadges.innerHTML = (p.badges || []).map(badgePill).join("");
      modalImage.src = p.image || "assets/projects/placeholder.svg";
      modalImage.onerror = () => (modalImage.src = "assets/projects/placeholder.svg");

      modalBullets.innerHTML = (p.bullets || []).map(b => `<li class="flex gap-3"><span class="text-cyan-400 mt-1">▹</span><span>${String(b).replace(/</g,"&lt;").replace(/>/g,"&gt;")}</span></li>`).join("");
      modalStack.innerHTML = (p.stack || []).map(s => `<span class="px-2.5 py-1 rounded-full text-xs font-mono border border-slate-700 bg-slate-900/40">${String(s).replace(/</g,"&lt;").replace(/>/g,"&gt;")}</span>`).join("");
      modalImpact.innerHTML = (p.impact || []).map(i => `
        <div class="rounded-xl border border-slate-700 bg-slate-900/40 p-4">
          <div class="text-slate-400 text-xs font-mono">${String(i.label).replace(/</g,"&lt;")}</div>
          <div class="text-white text-lg font-bold mt-1">${String(i.value).replace(/</g,"&lt;")}</div>
        </div>
      `).join("");

      safeLink(modalGithub, p.github);
      safeLink(modalDemo, p.demo);
const primaryUrl =
  !isPlaceholderLink(p.demo) ? p.demo : !isPlaceholderLink(p.github) ? p.github : "";

if (modalPrimary) {
  modalPrimary.classList.toggle("hidden", !primaryUrl);
  modalPrimary.onclick = () => {
    if (!primaryUrl) return;
    window.open(primaryUrl, "_blank", "noopener,noreferrer");
  };
}


      lastFocus = document.activeElement;
      modal.classList.remove("hidden");
modal.setAttribute("aria-hidden", "false");
modalOpen = true;
setBackgroundInert(true);

lockBodyScroll();

// Focus: keep the user inside the dialog
setTimeout(() => {
  modalClose?.focus();
}, 0);
    }

    function closeProject() {
      if (!modal) return;
      modal.classList.add("hidden");
      modal.setAttribute("aria-hidden", "true");
      modalOpen = false;
      setBackgroundInert(false);
      unlockBodyScroll();
      if (lastFocus && lastFocus instanceof HTMLElement) { lastFocus.focus(); }
      lastFocus = null;
    }

    document.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-open-project]");
      if (btn) openProject(btn.getAttribute("data-open-project"));
    });

    modalClose?.addEventListener("click", closeProject);
    // Clicking the dark backdrop should close the modal.
    modalBackdrop?.addEventListener("click", closeProject);
    // Fallback: if the user clicks the outer container itself.
    modal?.addEventListener("click", (e) => {
      if (e.target === modal) closeProject();
    });

    document.addEventListener("keydown", (e) => {
  if (!modal || !modalOpen) return;

  if (e.key === "Escape") {
    closeProject();
    return;
  }

  if (e.key === "Tab") {
    const focusables = getFocusable(modal);
    if (!focusables.length) return;

    const first = focusables[0];
    const last = focusables[focusables.length - 1];
    const active = document.activeElement;

    // If focus is outside the modal (e.g., body), bring it back inside.
    if (!(active instanceof Node) || !modal.contains(active)) {
      e.preventDefault();
      (e.shiftKey ? last : first).focus();
      return;
    }

    if (e.shiftKey) {
      // shift+tab: loop to last
      if (active === first) {
        e.preventDefault();
        last.focus();
      }
    } else {
      // tab: loop to first
      if (active === last) {
        e.preventDefault();
        first.focus();
      }
    }
  }
});


    projectSearch?.addEventListener("input", renderProjects);
    projectFilter?.addEventListener("change", renderProjects);
    projectSort?.addEventListener("change", renderProjects);

    // Initial render
    renderProjects();

    // -----------------------------
    // Contact form: validation + email client fallback + optional Formspree
    // -----------------------------
    const copyEmailBtn = document.getElementById("copyEmail");
    const copyToast = document.getElementById("copyToast");

    copyEmailBtn?.addEventListener("click", async () => {
  try {
    await navigator.clipboard.writeText(CONTACT_EMAIL);
  } catch (_) {
    // Clipboard may be blocked on some browsers/contexts — show a manual-copy prompt.
    window.prompt("Copy email address:", CONTACT_EMAIL);
  }
  copyToast?.classList.remove("hidden");
  setTimeout(() => copyToast?.classList.add("hidden"), 1600);
});


    const contactForm = document.getElementById("contactForm");
    const contactStatus = document.getElementById("contactStatus");

    function showErr(id, show) {
      const el = document.querySelector(`[data-err-for="${id}"]`);
      if (!el) return;
      el.classList.toggle("hidden", !show);
    }

    function isValidEmail(v) {
      return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(v || "").trim());
    }

    function setStatus(msg, ok = true) {
      if (!contactStatus) return;
      contactStatus.textContent = msg;
      contactStatus.classList.remove("hidden");
      contactStatus.classList.toggle("text-cyan-400", ok);
      contactStatus.classList.toggle("text-rose-300", !ok);
    }

    function openMailClient({ name, email, subject, message }) {
      const to = CONTACT_EMAIL;
      const body = `Name: ${name}\nEmail: ${email}\n\n${message}`;
      const url = `mailto:${to}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
      window.location.href = url;
    }

    contactForm?.addEventListener("submit", async (e) => {
      e.preventDefault();

      // Honeypot (basic bot protection): if filled, silently accept and reset.
      const honey = document.getElementById("website")?.value.trim();
      if (honey) {
        contactForm?.reset();
        setStatus("Thanks! Your message is ready to send.", true);
        return;
      }

      const name = document.getElementById("name")?.value.trim();
      const email = document.getElementById("email")?.value.trim();
      const subject = document.getElementById("subject")?.value.trim();
      const message = document.getElementById("message")?.value.trim();

      const okName = !!name;
      const okEmail = isValidEmail(email);
      const okSubject = !!subject;
      const okMessage = !!message;

      showErr("name", !okName);
      showErr("email", !okEmail);
      showErr("subject", !okSubject);
      showErr("message", !okMessage);

      if (!(okName && okEmail && okSubject && okMessage)) {
        setStatus("Please fix the highlighted fields.", false);
        return;
      }

      const action = (contactForm.getAttribute("action") || "").trim();
      const hasFormspree = action.startsWith("https://formspree.io/") && !action.includes("yourFormId");

      // Local file / no endpoint: open the user's email client.
      if (!hasFormspree || window.location.protocol === "file:") {
        setStatus("Opening your email app…", true);
        openMailClient({ name, email, subject, message });
        return;
      }

      try {
        setStatus("Sending…");
        const res = await fetch(action, {
          method: "POST",
          headers: { "Content-Type": "application/json", "Accept": "application/json" },
          body: JSON.stringify({ name, email, subject, message })
        });
        if (!res.ok) throw new Error("Request failed");
        setStatus("Message sent! I'll reply soon.", true);
        contactForm.reset();
        showErr("name", false); showErr("email", false); showErr("subject", false); showErr("message", false);
      } catch (err) {
        // Network issues? Fall back to the mail client.
        setStatus("Couldn't send via Formspree — opening your email app instead…", false);
        openMailClient({ name, email, subject, message });
      }
    });

    // vCard download (simple)
    const vcardBtn = document.getElementById("downloadVcard");
    vcardBtn?.addEventListener("click", () => {
      const vcf = [
        "BEGIN:VCARD",
        "VERSION:3.0",
        "FN:Khondoker Moin Hossain",
        "TITLE:Software Engineer",
        `EMAIL;TYPE=INTERNET:${CONTACT_EMAIL}`,
        "URL:https://github.com/khondokermoin",
        "END:VCARD"
      ].join("\n");
      const blob = new Blob([vcf], { type: "text/vcard" });
      const a = document.createElement("a");
      a.href = URL.createObjectURL(blob);
      a.download = "Khondoker_Moin_Hossain.vcf";
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(() => URL.revokeObjectURL(a.href), 1000);
    });


    // -----------------------------
    // Simple EN/BN toggle (Dhaka-friendly)
    // -----------------------------
    const i18nDict = {
      en: {
        hero_sub: `Software Engineer & Network Associate based in Dhaka.
          Focused on <span class="text-cyan-400">Android (Kotlin)</span>, <span class="text-cyan-400">Web Development</span>, and <span class="text-cyan-400">Cisco Networking</span>.`,
        services_sub: `Practical solutions for businesses — from inventory sync to Android tools.`,
        notes_sub: `Short write-ups to show how I think — concepts, tradeoffs, and clean explanations.`
      },
      bn: {
        hero_sub: `ঢাকাভিত্তিক Software Engineer & Network Associate।
          ফোকাস: <span class="text-cyan-400">Android (Kotlin)</span>, <span class="text-cyan-400">Web Development</span>, এবং <span class="text-cyan-400">Cisco Networking</span>।`,
        services_sub: `ব্যবসার জন্য বাস্তবসম্মত সমাধান — ইনভেন্টরি সিঙ্ক থেকে Android টুলস পর্যন্ত।`,
        notes_sub: `আমি কীভাবে চিন্তা করি তা দেখাতে ছোট ছোট লেখা — কনসেপ্ট, ট্রেড-অফ, এবং পরিষ্কার ব্যাখ্যা।`
      }
    };

    const langToggle = document.getElementById("langToggle");
    let lang = localStorage.getItem("lang") || "en";

    function applyLang(next) {
      lang = next;
      localStorage.setItem("lang", lang);
      document.documentElement.lang = lang === "bn" ? "bn" : "en";
      document.querySelectorAll("[data-i18n]").forEach((el) => {
        const key = el.getAttribute("data-i18n");
        const v = i18nDict?.[lang]?.[key];
        if (v) el.innerHTML = v;
      });
    }

    langToggle?.addEventListener("click", () => applyLang(lang === "en" ? "bn" : "en"));
    applyLang(lang);

    // -----------------------------
    // Mobile nav + active link (NEW)
    // -----------------------------
    const navToggle = document.getElementById("navToggle");
    const mobileMenu = document.getElementById("mobileMenu");
    mobileMenu?.setAttribute("aria-hidden", mobileMenu.classList.contains("hidden") ? "true" : "false");

    function closeMobileMenu() {
      if (!mobileMenu || !navToggle) return;
      mobileMenu.classList.add("hidden");
      mobileMenu.setAttribute("aria-hidden", "true");
      navToggle.setAttribute("aria-expanded", "false");
    }

    navToggle?.addEventListener("click", () => {
      if (!mobileMenu) return;
      const isHidden = mobileMenu.classList.contains("hidden");
      mobileMenu.classList.toggle("hidden", !isHidden);
      mobileMenu.setAttribute("aria-hidden", String(!isHidden ? true : false));
      navToggle.setAttribute("aria-expanded", String(isHidden));
    });

    // Close menu when clicking a link
    mobileMenu?.querySelectorAll('a[href^="#"]').forEach((a) => {
      a.addEventListener("click", () => closeMobileMenu());
    });

    // Close on Escape / outside click
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape") closeMobileMenu();
    });
    document.addEventListener("click", (e) => {
      if (!mobileMenu || mobileMenu.classList.contains("hidden")) return;
      const t = e.target;
      if (!(t instanceof Element)) return;
      const insideMenu = mobileMenu.contains(t);
      const insideToggle = navToggle?.contains(t) ?? false;
      if (!insideMenu && !insideToggle) closeMobileMenu();
    });
    window.addEventListener("resize", () => {
      // when switching to desktop, ensure menu is closed
      if (window.innerWidth >= 768) closeMobileMenu();
    });

    // Active section highlight
    const navLinks = Array.from(document.querySelectorAll('a.nav-link[href^="#"]'));

    const setActiveNav = (id) => {
      if (!id) return;
      navLinks.forEach((a) => a.classList.remove("active"));
      document
        .querySelectorAll(`a.nav-link[href="#${CSS.escape(id)}"]`)
        .forEach((a) => a.classList.add("active"));
    };

    // Observe only sections that are actually linked in the nav.
    const navSectionIds = navLinks
      .map((a) => a.getAttribute("href")?.slice(1))
      .filter(Boolean);

    const sectionEls = navSectionIds
      .map((id) => document.getElementById(id))
      .filter((el) => el && (el.tagName === "SECTION" || el.id === "home"));

    // We only observe real <section> elements. If "home" is on <body>, IntersectionObserver
    // would keep it intersecting forever; instead we use scroll position to activate it.
    const sections = sectionEls.filter((el) => el.tagName === "SECTION");
    const hasHomeLink = navLinks.some((a) => a.getAttribute("href") === "#home");

    const syncHomeActive = () => {
      if (!hasHomeLink) return;
      if (window.scrollY < 80) setActiveNav("home");
    };
    if (hasHomeLink) {
      window.addEventListener("scroll", syncHomeActive, { passive: true });
      syncHomeActive();
    }

    if ("IntersectionObserver" in window && navLinks.length && sections.length) {
      const io = new IntersectionObserver(
        (entries) => {
          // If we're at the top and there's a Home link, keep it active.
          if (hasHomeLink && window.scrollY < 80) {
            setActiveNav("home");
            return;
          }

          // pick the most visible intersecting entry
          const visible = entries
            .filter((x) => x.isIntersecting)
            .sort((a, b) => (b.intersectionRatio || 0) - (a.intersectionRatio || 0))[0];

          if (!visible) return;
          const id = visible.target.getAttribute("id");
          if (!id) return;

          setActiveNav(id);
        },
        { root: null, threshold: [0.25, 0.5, 0.75] }
      );

      sections.forEach((s) => io.observe(s));
    }

// -----------------------------
    // Back to top button (chat-widget style)
    // -----------------------------
    const backToTop = document.getElementById("backToTop");
    const setBackToTop = () => {
      if (!backToTop) return;
      const show = window.scrollY > 450;
      backToTop.classList.toggle("is-visible", show);
    };
    window.addEventListener("scroll", setBackToTop, { passive: true });
    setBackToTop();
    backToTop?.addEventListener("click", () => window.scrollTo({ top: 0, behavior: prefersReducedMotion ? "auto" : "smooth" }));
// -----------------------------
    // Dynamic footer year
    // -----------------------------
    const yearSpan = document.getElementById("year");
    if (yearSpan) yearSpan.textContent = String(new Date().getFullYear());


// -----------------------------
// Modern FX layer: preloader, custom cursor, particles, inspect block
// -----------------------------
(() => {
  const initModernPortfolioFx = () => {
    const hasFinePointer = window.matchMedia?.('(pointer: fine)')?.matches ?? false;
    const canAnimate = !prefersReducedMotion;

    const initPreloader = () => {
      const body = document.body;
      const preloader = document.getElementById('preloader');
      const percentEl = document.getElementById('preloader-percent');
      const barEl = document.getElementById('preloader-bar');

      const unlock = () => body?.classList.remove('is-loading');

      if (!preloader) {
        unlock();
        return;
      }

      const reduced =
        window.matchMedia &&
        window.matchMedia('(prefers-reduced-motion: reduce)').matches;

      let pct = 0;
      const setPct = (v) => {
        pct = Math.max(0, Math.min(100, Math.round(v)));
        if (percentEl) percentEl.textContent = `${pct}%`;
        if (barEl) barEl.style.width = `${pct}%`;
      };

      const timer = window.setInterval(() => {
        const target = 92;
        if (pct >= target) return;
        const jump = 3 + Math.random() * 9;
        setPct(Math.min(target, pct + jump));
      }, 120);

      const finish = () => {
        window.clearInterval(timer);
        setPct(100);

        if (!reduced) {
          window.setTimeout(() => {
            preloader.classList.add('is-hidden');
          }, 80);
          window.setTimeout(() => {
            preloader.remove();
          }, 700);
        } else {
          preloader.remove();
        }

        unlock();
      };

      if (document.readyState === 'complete') finish();
      else window.addEventListener('load', finish, { once: true });
    };

    const initCustomCursor = () => {
      const ring = document.querySelector('.custom-cursor-ring');
      const dot = document.querySelector('.custom-cursor-dot');
      if (!ring || !dot || !hasFinePointer) return;

      const state = { visible: false };
      const ringX = window.gsap ? gsap.quickTo(ring, 'x', { duration: 0.28, ease: 'power3.out' }) : (v) => { ring.style.left = `${v}px`; };
      const ringY = window.gsap ? gsap.quickTo(ring, 'y', { duration: 0.28, ease: 'power3.out' }) : (v) => { ring.style.top = `${v}px`; };
      const dotX = window.gsap ? gsap.quickTo(dot, 'x', { duration: 0.1, ease: 'power2.out' }) : (v) => { dot.style.left = `${v}px`; };
      const dotY = window.gsap ? gsap.quickTo(dot, 'y', { duration: 0.1, ease: 'power2.out' }) : (v) => { dot.style.top = `${v}px`; };

      const showCursor = () => {
        if (state.visible) return;
        state.visible = true;
        ring.style.opacity = '1';
        dot.style.opacity = '1';
      };

      document.addEventListener('mousemove', (event) => {
        showCursor();
        dotX(event.clientX);
        dotY(event.clientY);
        ringX(event.clientX);
        ringY(event.clientY);
      }, { passive: true });

      document.addEventListener('mouseenter', showCursor, { passive: true });
      document.addEventListener('mouseleave', () => {
        state.visible = false;
        ring.style.opacity = '0';
        dot.style.opacity = '0';
      });

      const interactiveSelector = 'a, button, input, textarea, select, summary, [role="button"], .magnetic-wrap';
      document.addEventListener('mouseover', (event) => {
        const target = event.target instanceof Element ? event.target.closest(interactiveSelector) : null;
        if (!target) return;
        ring.classList.add('is-hover');
        dot.classList.add('is-hover');
      });

      document.addEventListener('mouseout', (event) => {
        const target = event.target instanceof Element ? event.target.closest(interactiveSelector) : null;
        if (!target) return;
        ring.classList.remove('is-hover');
        dot.classList.remove('is-hover');
      });
    };

    const initParticles = () => {
      const container = document.getElementById('canvas-container');
      if (!container || !window.THREE) return;

      const scene = new THREE.Scene();
      const camera = new THREE.PerspectiveCamera(70, window.innerWidth / window.innerHeight, 0.1, 1500);
      const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true, powerPreference: 'high-performance' });
      renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.8));
      renderer.setSize(window.innerWidth, window.innerHeight);
      container.appendChild(renderer.domElement);

      const particleCount = window.innerWidth < 768 ? 900 : 1800;
      const spread = window.innerWidth < 768 ? 620 : 820;
      const vertices = new Float32Array(particleCount * 3);
      for (let i = 0; i < vertices.length; i += 3) {
        vertices[i] = THREE.MathUtils.randFloatSpread(spread * 2);
        vertices[i + 1] = THREE.MathUtils.randFloatSpread(spread * 2);
        vertices[i + 2] = THREE.MathUtils.randFloatSpread(spread * 2);
      }

      const geometry = new THREE.BufferGeometry();
      geometry.setAttribute('position', new THREE.BufferAttribute(vertices, 3));

      const material = new THREE.PointsMaterial({
        color: 0x5eead4,
        size: window.innerWidth < 768 ? 1.5 : 1.8,
        transparent: true,
        opacity: 0.42,
        sizeAttenuation: true,
        depthWrite: false
      });

      const particles = new THREE.Points(geometry, material);
      particles.rotation.x = 0.18;
      scene.add(particles);
      camera.position.z = 420;

      let rafId = 0;
      let isAnimating = false;
      let pointerX = 0;
      let pointerY = 0;
      let targetRotationX = particles.rotation.x;
      let targetRotationY = particles.rotation.y;

      if (hasFinePointer) {
        window.addEventListener('mousemove', (event) => {
          pointerX = (event.clientX / window.innerWidth) * 2 - 1;
          pointerY = (event.clientY / window.innerHeight) * 2 - 1;
          targetRotationY = pointerX * 0.18;
          targetRotationX = 0.18 + pointerY * 0.08;
        }, { passive: true });
      }

      const animate = () => {
        isAnimating = true;
        rafId = window.requestAnimationFrame(animate);
        particles.rotation.y += 0.00045;
        particles.rotation.x += (targetRotationX - particles.rotation.x) * 0.02;
        particles.rotation.y += (targetRotationY - particles.rotation.y) * 0.015;
        renderer.render(scene, camera);
      };
      animate();

      const onResize = () => {
        camera.aspect = window.innerWidth / window.innerHeight;
        camera.updateProjectionMatrix();
        renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.8));
        renderer.setSize(window.innerWidth, window.innerHeight);
      };
      window.addEventListener('resize', onResize, { passive: true });

      document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
          window.cancelAnimationFrame(rafId);
          isAnimating = false;
        } else if (!isAnimating) {
          animate();
        }
      });
    };

    const initInspectBlock = () => {
      const isBlockedShortcut = (event) => {
        const key = String(event.key || '').toLowerCase();
        const ctrlOrMeta = event.ctrlKey || event.metaKey;
        return event.key === 'F12'
          || (ctrlOrMeta && event.shiftKey && ['i', 'j', 'c'].includes(key))
          || (ctrlOrMeta && key === 'u');
      };

      document.addEventListener('contextmenu', (event) => {
        event.preventDefault();
      }, { capture: true });

      document.addEventListener('keydown', (event) => {
        if (!isBlockedShortcut(event)) return;
        event.preventDefault();
        event.stopPropagation();
      }, { capture: true });
    };

    initInspectBlock();
    initCustomCursor();
    initParticles();

    if (document.readyState === 'complete') {
      initPreloader();
    } else {
      window.addEventListener('load', initPreloader, { once: true });
    }
  };

  initModernPortfolioFx();
})();
