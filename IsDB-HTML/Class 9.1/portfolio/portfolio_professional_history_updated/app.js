    // -----------------------------
// Typewriter Effect (stable + non-blocking)
// -----------------------------
const words = ["Software Engineer.", "Network Associate.", "Android Developer.", "Problem Solver."];
const heading = document.getElementById("typewriter");
const prefersReducedMotion = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
let wordIndex = 0;
let charIndex = 0;
let deleting = false;

function tick() {
  if (!heading) return;
  if (prefersReducedMotion) {
    heading.textContent = words[0];
    return;
  }

  const current = words[wordIndex];

  if (!deleting) {
    // typing
    heading.textContent = current.slice(0, charIndex + 1);
    charIndex++;
    if (charIndex >= current.length) {
      deleting = true;
      setTimeout(tick, 1400);
    } else {
      setTimeout(tick, 90);
    }
    return;
  }

  // deleting
  heading.textContent = current.slice(0, Math.max(0, charIndex - 1));
  charIndex--;
  if (charIndex <= 0) {
    deleting = false;
    wordIndex = (wordIndex + 1) % words.length;
    setTimeout(tick, 250);
  } else {
    setTimeout(tick, 45);
  }
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', tick);
} else {
  tick();
}

// -----------------------------
// CV dropdown menu
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
            <img src="${file}" alt="${safeTitle} certificate" loading="lazy" class="w-full h-full object-cover"
                 onerror="this.style.display='none'; this.parentElement.classList.add('flex','items-center','justify-center'); this.parentElement.innerHTML='<span class=\'text-slate-500 text-sm font-mono\'>Certificate file not found</span>';" />
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
      const hasGithub = github && github !== "#" && !github.endsWith("github.com/");
      const hasDemo = demo && demo !== "#";

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
            <img src="${img}" alt="${safeTitle} screenshot" loading="lazy"
                 class="w-full h-full object-cover"
                 onerror="this.src='assets/projects/placeholder.svg'" />
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

    function safeLink(el, href) {
      if (!el) return;
      const url = (href || "").trim();
      const ok = url && url !== "#";
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
      if (!modal || !modalTitle || !modalSubtitle || !modalBadges || !modalImage || !modalBullets || !modalStack || !modalImpact || !modalGithub || !modalDemo || !modalPrimary) return;

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

      const primaryUrl = (p.demo && p.demo !== "#") ? p.demo : (p.github && p.github !== "#") ? p.github : "";
      modalPrimary.classList.toggle("hidden", !primaryUrl);
      modalPrimary.onclick = () => {
        if (!primaryUrl) return;
        window.open(primaryUrl, "_blank", "noopener,noreferrer");
      };

      lastFocus = document.activeElement;
      modal.classList.remove("hidden");
      modal.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
      setTimeout(() => modalClose?.focus(), 0);
    }

    function closeProject() {
      if (!modal) return;
      modal.classList.add("hidden");
      modal.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
      if (lastFocus && lastFocus instanceof HTMLElement) { lastFocus.focus(); }
      lastFocus = null;
    }

    document.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-open-project]");
      if (btn) openProject(btn.getAttribute("data-open-project"));
    });

    modalClose?.addEventListener("click", closeProject);
        modal?.addEventListener("click", (e) => {
      if (e.target === modal) closeProject();
    });

    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && modal && !modal.classList.contains("hidden")) closeProject();
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
        await navigator.clipboard.writeText("khondokermoin2k23@gmail.com");
      } catch (_) {
        const tmp = document.createElement("textarea");
        tmp.value = "khondokermoin2k23@gmail.com";
        document.body.appendChild(tmp);
        tmp.select();
        document.execCommand("copy");
        tmp.remove();
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
      const to = "khondokermoin2k23@gmail.com";
      const body = `Name: ${name}\nEmail: ${email}\n\n${message}`;
      const url = `mailto:${to}?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
      window.location.href = url;
    }

    contactForm?.addEventListener("submit", async (e) => {
      e.preventDefault();

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
        "EMAIL;TYPE=INTERNET:khondokermoin2k23@gmail.com",
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

    // Observe only sections that are actually linked in the nav.
    const navSectionIds = navLinks
      .map((a) => a.getAttribute("href")?.slice(1))
      .filter(Boolean);

    const sections = navSectionIds
      .map((id) => document.getElementById(id))
      .filter((el) => el && el.tagName === "SECTION");

    if ("IntersectionObserver" in window && navLinks.length && sections.length) {
      const linkById = new Map(navLinks.map((a) => [a.getAttribute("href")?.slice(1), a]));
      const io = new IntersectionObserver(
        (entries) => {
          // pick the most visible intersecting entry
          const visible = entries
            .filter((x) => x.isIntersecting)
            .sort((a, b) => (b.intersectionRatio || 0) - (a.intersectionRatio || 0))[0];

          if (!visible) return;
          const id = visible.target.getAttribute("id");
          if (!id) return;

          navLinks.forEach((a) => a.classList.remove("active"));
          // highlight both desktop + mobile links with same href
          document.querySelectorAll(`a.nav-link[href="#${id}"]`).forEach((a) => a.classList.add("active"));
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
