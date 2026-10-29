const appBase = new URL("./", document.baseURI).pathname;
const projectBase = appBase.replace(/frontend\/?$/, "").replace(/\/$/, "");
const API = `${projectBase}/api/v1`;
const isLiveServerPreview = location.port === "5500" && location.pathname.toLowerCase().endsWith("/frontend/index.html");
const tokenKey = "techconnect.session";
const languageKey = "astra.language";
let language = localStorage.getItem(languageKey) || "en";
let token = localStorage.getItem(tokenKey) || "";
let me = null;
let catalog = [];
let zones = [];
let currentView = "home";
let authMode = "login";
let authVisible = false;
let toastTimer;
let progressRefreshTimer = null;
let progressRefreshPending = false;

const $ = (selector, root = document) => root.querySelector(selector);
function updateLanguageControl() {
  document.documentElement.lang = language;
  document.title = language === "fr" ? "ASTRA | Services fiables à domicile" : "ASTRA | Reliable home services";
  const button = $("#language-toggle");
  if (button) { button.textContent = language === "fr" ? "English" : "Français"; button.setAttribute("aria-label", language === "fr" ? "Translate page to English" : "Traduire la page en français"); }
}
function applyTranslations(root = document.body) {
  const dictionaries = window.ASTRA_I18N;
  if (!dictionaries) return;
  const dictionary = language === "fr" ? dictionaries.toFrench : dictionaries.toEnglish;
  const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT);
  let node;
  while ((node = walker.nextNode())) {
    if (node.parentElement?.closest("script,style,textarea,[contenteditable=true]")) continue;
    const text = node.nodeValue; const trimmed = text.trim();
    if (dictionary[trimmed]) node.nodeValue = text.replace(trimmed, dictionary[trimmed]);
    else if (language === "fr") {
      const greeting = trimmed.match(/^(Good morning|Good afternoon|Good evening), (.+)\.$/);
      if (greeting) node.nodeValue = text.replace(trimmed, `${greeting[1] === "Good morning" ? "Bonjour" : greeting[1] === "Good afternoon" ? "Bon après-midi" : "Bonsoir"}, ${greeting[2]}.`);
    }
  }
  root.querySelectorAll("[placeholder],[aria-label],[title]").forEach(element => {
    for (const attribute of ["placeholder", "aria-label", "title"]) { const value = element.getAttribute(attribute); if (value && dictionary[value]) element.setAttribute(attribute, dictionary[value]); }
  });
}
$("#language-toggle")?.addEventListener("click", async () => { language = language === "en" ? "fr" : "en"; localStorage.setItem(languageKey, language); updateLanguageControl(); await render(); });
updateLanguageControl();
const esc = value => String(value ?? "").replace(/[&<>"']/g, char => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[char]));
const fmt = amount => `XAF ${Number(amount || 0).toLocaleString("en-US")}`;
const priceRange = item => `XAF ${Number(item.price_min_xaf ?? item.price_xaf ?? 0).toLocaleString("en-US")}–${Number(item.price_max_xaf ?? item.price_xaf ?? 0).toLocaleString("en-US")}`;
const nice = value => String(value || "").toLowerCase().replaceAll("_", " ");
const roleLabel = role => ({ customer: "Customer", technician: "Technician", dispatcher: "Dispatch", supervisor: "Supervisor", admin: "Administration", price_manager: "Price manager", price_approver: "Price approver", payroll: "People & payroll" }[role] || role);
const statusClass = status => ["COMPLETED", "READY_TO_WORK", "CUSTOMER_TEST", "ACCEPTED"].includes(status) ? "green" : ["CANCELLED", "DISPUTE_OPEN"].includes(status) ? "red" : ["AWAITING_PAYMENT", "ESTIMATE_PENDING", "PAUSED", "REWORK_OPEN"].includes(status) ? "amber" : "";

async function api(path, options = {}) {
  if (isLiveServerPreview) throw new Error("This page is running in VS Code Live Server (port 5500), which only serves static files and cannot run the PHP API.");
  if (location.protocol === "file:") throw new Error("Open the application at http://localhost/ASTRA/ so PHP and the API can run through XAMPP.");
  const headers = { "Content-Type": "application/json", ...(options.headers || {}) };
  if (token) headers.Authorization = `Bearer ${token}`;
  const response = await fetch(API + path, { ...options, headers });
  const payload = await response.json().catch(() => ({}));
  if (!response.ok) {
    if (response.status === 401 && token) { token = ""; me = null; localStorage.removeItem(tokenKey); }
    throw new Error(payload.error?.message || "The request could not be completed.");
  }
  return payload.data;
}

function notify(message, kind = "info") {
  const box = $("#toast");
  box.textContent = message;
  box.dataset.kind = kind;
  box.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => box.classList.remove("show"), 3800);
}

async function boot() {
  try {
    const [catalogResult, zoneResult] = await Promise.all([api("/catalog"), api("/zones")]);
    catalog = catalogResult.items || [];
    zones = zoneResult.zones || [];
    if (token) {
      try { me = (await api("/me")).user; }
      catch { token = ""; me = null; localStorage.removeItem(tokenKey); }
    }
    await render();
  } catch (error) {
    const preview = isLiveServerPreview || location.protocol === "file:";
    const hint = isLiveServerPreview ? "Open the XAMPP-served app at http://localhost/ASTRA/. Do not use the Live Server preview for this PHP application." : location.protocol === "file:" ? "Open http://localhost/ASTRA/ instead of opening frontend/index.html directly." : "Confirm Apache, MySQL, and the techconnect database are available, then retry.";
    const action = preview ? `<a class="btn primary" href="http://localhost/ASTRA/">Open XAMPP application <span aria-hidden="true">→</span></a>` : `<button class="btn primary" data-action="reload">Try again</button>`;
    $("#app").innerHTML = `<section class="error-panel"><span class="eyebrow">Connection problem</span><h1>ASTRA is not available</h1><p>${esc(error.message)}</p><p class="muted">${esc(hint)}</p>${action}</section>`;
    bind();
  }
}

function navItems() {
  if (!me) return [];
  if (me.role === "customer") return [["home", "Services", "⌂"], ["requests", "My requests", "▤"], ["help", "Help & safety", "?" ]];
  if (me.role === "admin") return [["home", "Overview", "⌂"], ["management", "Management", "⚙"], ["dispatch", "Dispatch board", "⇄"], ["requests", "All jobs", "▤"], ["pricebook", "Pricebook", "▦"]];
  if (me.role === "technician") return [["home", "My jobs", "▣"], ["schedule", "Schedule", "◷"], ["help", "Safety & support", "?" ]];
  if (me.role === "payroll") return [["home", "Payroll", "▥" ]];
  if (["price_manager", "price_approver"].includes(me.role)) return [["pricebook", "Pricebook", "▦" ]];
  return [["home", "Overview", "⌂"], ["dispatch", "Dispatch board", "⇄"], ["requests", "All jobs", "▤"], ["pricebook", "Pricebook", "▦" ]];
}

async function render() {
  const nav = $("#top-nav"), account = $("#account-area"), app = $("#app");
  if (!me) {
    stopAdminProgressMonitor();
    if (authVisible) {
      nav.innerHTML = '';
      account.innerHTML = '<button class="btn ghost" data-action="show-home">Back to welcome</button>';
      app.innerHTML = renderAuth();
    } else {
      nav.innerHTML = '<a class="nav-link" href="#welcome-services">Services</a><a class="nav-link" href="#welcome-how">How it works</a><a class="nav-link" href="#welcome-coverage">Coverage</a>';
      account.innerHTML = '<button class="btn ghost" data-action="show-login">Sign in</button><button class="btn primary small-btn" data-action="show-register">Create account</button>';
      app.innerHTML = renderWelcome();
    }
    app.setAttribute("aria-busy", "false");
    applyTranslations();
    bind();
    return;
  }
  const items = navItems();
  if (!items.some(([key]) => key === currentView)) currentView = items[0]?.[0] || "home";
  nav.innerHTML = items.map(([key, label, icon]) => `<a href="#${key}" class="nav-link ${currentView === key ? "active" : ""}" data-view="${key}" ${currentView === key ? 'aria-current="page"' : ""}><span class="nav-icon" aria-hidden="true">${icon}</span>${label}</a>`).join("");
  account.innerHTML = `<span class="account-role">${esc(roleLabel(me.role))}</span><span class="account-name">${esc(me.name)}</span><button class="btn ghost small-btn" data-action="logout">Sign out</button>`;
  app.setAttribute("aria-busy", "true");
  try {
    if (me.role === "customer") {
      app.innerHTML = currentView === "requests" ? await renderCustomerRequests() : currentView === "help" ? renderHelp("customer") : await renderCustomerHome();
    } else if (me.role === "technician") {
      app.innerHTML = currentView === "schedule" ? await renderTechnicianSchedule() : currentView === "help" ? renderHelp("technician") : await renderTechnicianJobs();
    } else if (me.role === "payroll") {
      app.innerHTML = renderPayroll();
    } else if (me.role === "admin" && currentView === "management") {
      app.innerHTML = await renderAdminManagement();
    } else if (["price_manager", "price_approver"].includes(me.role) && currentView === "pricebook") {
      app.innerHTML = renderPricebook();
    } else {
      app.innerHTML = currentView === "dispatch" ? await renderDispatch() : currentView === "requests" ? await renderOperationsJobs() : currentView === "pricebook" ? renderPricebook() : await renderOperationsOverview();
    }
  } catch (error) {
    if (!me) {
      nav.innerHTML = '<a class="nav-link" href="#welcome-services">Services</a><a class="nav-link" href="#welcome-how">How it works</a><a class="nav-link" href="#welcome-coverage">Coverage</a>';
      account.innerHTML = '<button class="btn ghost" data-action="show-login">Sign in</button><button class="btn primary small-btn" data-action="show-register">Create account</button>';
      app.innerHTML = authVisible ? renderAuth() : renderWelcome();
    } else {
      app.innerHTML = `<section class="error-panel"><span class="eyebrow">Could not load this page</span><h1>Something went wrong</h1><p>${esc(error.message)}</p><button class="btn primary" data-action="reload">Retry</button></section>`;
    }
  } finally {
    app.setAttribute("aria-busy", "false");
  }
  bind();
  applyTranslations();
  if (me?.role === 'admin' && currentView === 'home' && $("#admin-progress-chart")) startAdminProgressMonitor();
  else stopAdminProgressMonitor();
}

function renderWelcome() {
  const categories = categoryList();
  const featured = categories.slice(0, 6);
  const heroImage = categories.find(category => category.image_path)?.image_path || "assets/Images/catalog/electric-1.jpg";
  return `<section class="public-hero"><div class="public-hero-copy"><span class="eyebrow light">Reliable home services · Douala & Yaoundé</span><h1>Home repairs, handled with care.</h1><p>Tell us what needs attention. We coordinate the right technician, share a clear estimate, and keep you updated through the job.</p><div class="public-hero-actions"><button class="btn light-btn" data-action="show-register">Request a service <span>→</span></button><a class="public-text-link" href="#welcome-services">Explore services ↓</a></div><div class="public-proof"><span>✓ Estimate before work begins</span><span>✓ Your approval before repair</span></div></div><div class="public-hero-media"><img src="${projectBase}/${esc(heroImage)}" alt="ASTRA home service technician" fetchpriority="high"><div class="hero-photo-caption"><span class="hero-caption-logo"><img src="${projectBase}/assets/Icons/tech-connect-logo.svg" alt=""></span><span><strong>Local help, made simple</strong><small>One clear process from request to resolution</small></span></div></div></section>
    <section class="public-section" id="welcome-services"><div class="public-section-heading"><div><span class="eyebrow">Services for your home</span><h2>Help for the jobs that matter</h2><p>Choose from ${catalog.length} common service jobs across ${categories.length} categories.</p></div><button class="text-button" data-action="show-register">Get started →</button></div><div class="welcome-category-grid">${featured.map((category,index) => { const entries=catalog.filter(item=>item.category===category.category); const from=Math.min(...entries.map(item=>Number(item.price_min_xaf??item.price_xaf))); return `<article class="welcome-category-card"><img class="welcome-category-photo" src="${projectBase}/${esc(category.image_path)}" alt="${esc(category.category)}" loading="lazy"><div class="welcome-category-copy"><img src="${projectBase}/${esc(category.icon_path)}" alt="" loading="lazy"><div><h3>${esc(category.category)}</h3><p>${category.count} service options · from ${fmt(from)}</p></div></div></article>`; }).join("")}</div><p class="catalog-note">${catalog.length} service options available. Prices shown in the signed-in service catalog are planning estimates, not confirmed quotations.</p></section>
    <section class="public-how" id="welcome-how"><div class="public-section-heading"><div><span class="eyebrow">A straightforward process</span><h2>From “something’s wrong” to sorted</h2></div></div><div class="how-grid"><article><span>01</span><h3>Tell us what happened</h3><p>Choose a service, describe the issue, and share your preferred visit time and area.</p></article><article><span>02</span><h3>Review the estimate</h3><p>A technician checks the job and sends an itemized estimate. You decide before work begins.</p></article><article><span>03</span><h3>Follow the repair</h3><p>Track the assignment and work status, then confirm when the job is complete.</p></article></div></section>
    <section class="public-assurance"><div><span class="eyebrow light">Clear expectations</span><h2>You stay in control of the repair.</h2><p>Submitting a request does not take payment. Work starts only after you review and approve the estimate.</p></div><div class="assurance-points"><span><b>01</b> No charge to request a visit</span><span><b>02</b> Parts and extra work are explained</span><span><b>03</b> Confirm completion in your account</span></div></section>
    <section class="public-section coverage-section" id="welcome-coverage"><div class="public-section-heading"><div><span class="eyebrow">Local service pilot</span><h2>Serving Douala & Yaoundé</h2><p>Choose your service area when you create a request.</p></div></div><div class="coverage-chips">${zones.map(zone=>`<span>${esc(zone.name)}</span>`).join("") || `<span>Coverage areas are being updated</span>`}</div><div class="public-bottom-cta"><div><h2>Ready to get started?</h2><p>Create an account to request and follow your home service.</p></div><button class="btn primary" data-action="show-register">Create your account <span>→</span></button></div></section>`;
}
function renderAuth() {
  const registering = authMode === "register";
  return `<section class="auth-layout"><div class="auth-visual"><div class="auth-visual-top"><span class="auth-logo-crop"><img src="${projectBase}/assets/Icons/tech-connect-logo.svg" alt="ASTRA"></span><span class="eyebrow light">Reliable help, thoughtfully delivered</span></div><div><span class="eyebrow light">Home services · Douala</span><h1>${registering ? "Your home, in capable hands." : "Technical help without the guesswork."}</h1><p>${registering ? "Create an account to request a visit, review a clear estimate, and follow each job." : "Book a vetted company technician, approve the price before work begins, and stay informed."}</p></div><div class="auth-points"><span><b>01</b> Choose a service</span><span><b>02</b> Approve your estimate</span><span><b>03</b> Confirm the completed work</span></div><div class="auth-footnote">A managed service from request to resolution.</div></div><div class="auth-card"><div class="auth-card-heading"><span class="eyebrow">Customer portal</span><h2>${registering ? "Create your account" : "Welcome back"}</h2><p>${registering ? "It takes a minute to get started." : "Sign in to manage your service requests."}</p></div><form id="auth-form" novalidate>${registering ? `<label>Full name<input name="name" autocomplete="name" placeholder="Your name" required></label><label>Phone number<input name="phone" type="tel" autocomplete="tel" placeholder="+237 …" required></label>` : ""}<label>Email address<input name="email" type="email" autocomplete="email" placeholder="you@example.com" required></label><label>Password<input name="password" type="password" minlength="12" autocomplete="${registering ? "new-password" : "current-password"}" placeholder="At least 12 characters" required></label>${registering ? `<label class="consent-row"><input name="consent" type="checkbox" required><span>I accept the account terms and privacy notice.</span></label>` : `<div class="form-note">Your sign-in is protected with an expiring session.</div>`}<button class="btn primary full-width" type="submit">${registering ? "Create customer account" : "Sign in"}<span aria-hidden="true">→</span></button></form><p class="switch-auth">${registering ? "Already have an account?" : "New to ASTRA?"}<button class="text-button" data-action="toggle-auth">${registering ? "Sign in" : "Create account"}</button></p><p class="prototype-note">Local pilot · Sample service prices are not final customer rates.</p></div></section>`;
}

async function getRequests() { return (await api("/requests")).requests || []; }
function statusTag(status) { return `<span class="status-pill ${statusClass(status)}"><i></i>${esc(nice(status))}</span>`; }
function pageHeading(kicker, title, description, action = "") {
  return `<div class="page-heading"><div><span class="eyebrow">${esc(kicker)}</span><h1>${esc(title)}</h1><p>${esc(description)}</p></div>${action}</div>`;
}
function statCard(label, value, hint, icon, tone = "") {
  return `<article class="stat-card"><span class="stat-icon ${tone}">${icon}</span><div><span class="stat-label">${esc(label)}</span><strong class="stat-value">${esc(value)}</strong><span class="stat-hint">${esc(hint)}</span></div></article>`;
}
function categoryList() {
  return [...new Map(catalog.map(item => [item.category, { category: item.category, trade: item.trade_code, count: catalog.filter(c => c.category === item.category).length, image_path: item.image_path, icon_path: item.icon_path }])).values()];
}
function shortDate(value) {
  if (!value) return "Time to be confirmed";
  const date = new Date(String(value).replace(" ", "T"));
  return Number.isNaN(date.getTime()) ? esc(value.replace("T", " ")) : new Intl.DateTimeFormat("en", { weekday: "short", day: "numeric", month: "short", hour: "numeric", minute: "2-digit" }).format(date);
}
function serviceTile(category, index) {
  const prices = catalog.filter(item => item.category === category.category).map(item => Number(item.price_min_xaf ?? item.price_xaf));
  return `<button class="service-tile" type="button" data-category="${esc(category.category)}"><span class="service-illustration color-${index % 4}"><img src="${projectBase}/${esc(category.icon_path || "assets/Icons/clarity--tools-solid.svg")}" alt="" loading="lazy"></span><span class="service-tile-copy"><strong>${esc(category.category)}</strong><small>${category.count} jobs · from ${fmt(Math.min(...prices))}</small></span><span class="tile-arrow" aria-hidden="true">↗</span></button>`;
}

async function renderCustomerHome() {
  const reqs = await getRequests();
  const cats = categoryList();
  const active = reqs.filter(r => !["COMPLETED", "CANCELLED"].includes(r.status));
  return `<section class="welcome-banner customer-banner"><div><span class="eyebrow light">Your home services</span><h1>How can we help today${me.name ? `, ${esc(me.name.split(" ")[0])}` : ""}?</h1><p>Choose a service, tell us what’s happening, and we’ll coordinate the next step.</p><a class="btn light-btn" href="#request-form" data-action="focus-request">Request a service <span>↓</span></a></div><div class="banner-mark" aria-hidden="true">T</div></section>
    <div class="stats-row">${statCard("Open requests", String(active.length), active.length ? "We’re keeping things moving" : "Ready when you need us", "▤", "teal")}${statCard("Service coverage", String(zones.length), "Active service areas", "⌖", "blue")}${statCard("Price clarity", "Up front", "Approve before work starts", "✓", "green")}</div>
    ${pageHeading("Explore services", "What needs fixing?", "Our team coordinates qualified technicians across these service categories.")}
    <div class="service-grid">${cats.map(serviceTile).join("") || `<div class="empty-card">The service catalog is unavailable. Ask your administrator to publish service items.</div>`}</div>
    <details class="catalog-browser"><summary>Browse all ${catalog.length} jobs and estimated prices</summary>
    <p class="catalog-note">Client-provided planning estimates in XAF. Actual charges depend on inspection, materials, transport, and your approved quotation.</p>
    <div class="service-catalog-grid">${catalog.map(item => `<article class="service-catalog-card"><img class="service-photo" src="${projectBase}/${esc(item.image_path)}" alt="${esc(item.category)} service" loading="lazy"><div class="service-card-heading"><img src="${projectBase}/${esc(item.icon_path)}" alt=""><span>${esc(item.category)}</span></div><h3>${esc(item.name_en)}</h3><p>${esc(item.description)}</p><div class="service-card-footer"><strong>${priceRange(item)}</strong><button class="text-button" data-service-request="${esc(item.code)}">Request this job →</button></div></article>`).join("")}</div></details>
    ${pageHeading("New request", "Tell us a little more", "We’ll confirm availability before assigning a technician.")}
    <section class="panel request-panel"><div class="panel-heading"><div><span class="eyebrow">Service details</span><h2>Request a home visit</h2></div><span class="step-count">01 <i>/</i> 03</span></div><form id="request-form"><div class="form-grid"><label>Service category<select name="category" id="request-category" required>${cats.map(c => `<option value="${esc(c.category)}" data-trade="${esc(c.trade)}">${esc(c.category)}</option>`).join("")}</select></label><label>Service area<select name="zoneCode" required>${zones.map(z => `<option value="${esc(z.code)}">${esc(z.name)} · ${esc(z.city)}</option>`).join("")}</select></label><label>Preferred date and time<input name="preferredSlot" type="datetime-local" required></label><label>Service address<input name="address" autocomplete="street-address" placeholder="Street, neighbourhood, landmark" required></label><label class="span-two">What needs attention?<textarea id="request-issue" name="issue" maxlength="2000" placeholder="Describe the problem and any details that may help our team prepare." required></textarea></label><label class="consent-row span-two"><input name="consent" type="checkbox" required><span>I agree that these details may be used to arrange the visit and that my address may be shared with the assigned technician during the active assignment.</span></label></div><div class="form-footer"><span class="small muted">No charge is taken when you submit a request.</span><button class="btn primary" type="submit">Send request <span>→</span></button></div></form></section>
    <div class="section-heading-inline"><div><span class="eyebrow">Your activity</span><h2>Recent requests</h2></div><button class="text-button" data-view="requests">View all requests →</button></div>
    <div class="request-list">${reqs.length ? reqs.slice(0, 3).map(renderCustomerRequest).join("") : `<div class="empty-card"><span class="empty-icon">▤</span><h3>Your requests will show up here</h3><p>Once you send your first service request, you can track the estimate and job status from this page.</p></div>`}</div>`;
}

async function renderCustomerRequests() {
  const reqs = await getRequests();
  return `${pageHeading("Customer portal", "My service requests", "Track each visit, review estimates, and confirm completed work.", `<button class="btn primary" data-view="home">＋ New request</button>`)}
    <section class="toolbar-panel"><div class="filter-tabs" role="group" aria-label="Filter requests"><button class="filter-tab active" data-filter="all">All <b>${reqs.length}</b></button><button class="filter-tab" data-filter="open">In progress <b>${reqs.filter(r => !["COMPLETED", "CANCELLED"].includes(r.status)).length}</b></button><button class="filter-tab" data-filter="done">Completed <b>${reqs.filter(r => ["COMPLETED", "CANCELLED"].includes(r.status)).length}</b></button></div><label class="search-box"><span>⌕</span><input id="request-search" type="search" placeholder="Search request or service" aria-label="Search requests"></label></section>
    <div class="request-list filterable-list">${reqs.length ? reqs.map(renderCustomerRequest).join("") : `<div class="empty-card"><span class="empty-icon">▤</span><h3>No requests yet</h3><p>Start by choosing a service and sending us a request.</p><button class="btn primary" data-view="home">Browse services</button></div>`}</div>`;
}

function renderCustomerRequest(r) {
  const e = r.estimate;
  const searchable = `${r.public_ref} ${r.category} ${r.issue} ${r.status}`.toLowerCase();
  return `<article class="panel request-card filter-item" data-status-group="${["COMPLETED", "CANCELLED"].includes(r.status) ? "done" : "open"}" data-search="${esc(searchable)}"><div class="request-card-top"><div class="request-ref"><span class="ref-mark">${esc(r.category.slice(0, 1).toUpperCase())}</span><div><span class="eyebrow">${esc(r.public_ref)}</span><h3>${esc(r.category)}</h3></div></div>${statusTag(r.status)}</div><div class="request-metadata"><span>⌖ ${esc(r.zone_code.replaceAll("_", " "))}</span><span>◷ ${shortDate(r.preferred_slot)}</span></div><div class="progress-track" aria-label="Request progress">${progressSteps(r.status)}</div><p class="request-description">${esc(r.issue)}</p>${e ? `<section class="estimate-box"><div class="estimate-header"><div><span class="eyebrow">Estimate · version ${esc(e.version_number)}</span><strong>${esc(nice(e.status))}</strong></div><strong class="estimate-total">${fmt(e.total_xaf)}</strong></div><div class="estimate-lines">${e.lines.map(line => `<div class="estimate-row"><span>${esc(line.description_snapshot)} <small>× ${esc(line.quantity)}</small></span><span>${fmt(line.line_total_xaf)}</span></div>`).join("")}</div><p class="small muted">${esc(e.warranty_summary)} · Pricebook ${esc(e.pricebook_version)}</p>${e.status === "SUBMITTED" ? `<div class="button-row"><button class="btn primary" data-action="accept-estimate" data-id="${e.id}">Approve estimate</button><button class="btn" data-action="decline-estimate" data-id="${e.id}">Decline</button></div>` : ""}${e.status === "ACCEPTED" && r.status === "AWAITING_PAYMENT" && !e.payment ? `<div class="notice warning"><strong>Payment provider not connected.</strong> You can create a local demo payment only.</div><button class="btn primary" data-action="pay" data-id="${e.id}">Continue to payment · demo</button>` : ""}${e.payment?.status === "PENDING" ? `<div class="notice warning"><strong>Demo payment pending.</strong> This is a simulation only; no money is collected.</div><button class="btn primary" data-action="dev-confirm" data-id="${e.payment.id}">Simulate successful payment · demo</button>` : ""}</section>` : ""}${r.status === "CUSTOMER_TEST" ? `<div class="completion-box"><div><strong>Does everything look right?</strong><p>Confirm completion or tell us what needs another look.</p></div><div class="button-row"><button class="btn primary" data-action="complete" data-id="${r.id}">Confirm complete</button><button class="btn" data-action="case" data-kind="rework" data-id="${r.id}">Request rework</button><button class="btn danger" data-action="case" data-kind="dispute" data-id="${r.id}">Report an issue</button></div></div>` : ""}<details class="history-details"><summary>View request history</summary><ul class="event-list">${(r.events || []).map(event => `<li><time>${esc((event.created_at || "").slice(0, 16).replace("T", " "))}</time><span>${esc(nice(event.event_type))}${event.reason ? ` · ${esc(event.reason)}` : ""}</span></li>`).join("")}</ul></details></article>`;
}

function progressSteps(status) {
  const steps = ["Request", "Dispatch", "Estimate", "Payment", "Work", "Test"];
  const stage = { REQUESTED: 0, DISPATCHED: 1, ACCEPTED: 1, ESTIMATE_PENDING: 2, ESTIMATE_DECLINED: 2, AWAITING_PAYMENT: 3, READY_TO_WORK: 3, IN_PROGRESS: 4, PAUSED: 4, CUSTOMER_TEST: 5, COMPLETED: 5, REWORK_OPEN: 5, DISPUTE_OPEN: 5 }[status] ?? 0;
  return steps.map((label, index) => `<span class="progress-step ${index < stage ? "done" : ""} ${index === stage ? "current" : ""}"><i>${index < stage ? "✓" : index + 1}</i><small>${label}</small></span>`).join("");
}

async function renderTechnicianJobs() {
  const reqs = await getRequests();
  const offers = reqs.filter(r => r.assignments?.some(a => a.status === "OFFERED" && a.technician_id === me.id));
  const active = reqs.filter(r => !["COMPLETED", "CANCELLED"].includes(r.status));
  return `<section class="welcome-banner tech-banner"><div><span class="eyebrow light">Technician workspace</span><h1>Good work starts with a clear plan.</h1><p>Review your assignments, share a diagnosis, and keep customers informed.</p></div><div class="banner-mark" aria-hidden="true">✳</div></section>
    <div class="stats-row">${statCard("Open jobs", String(active.length), "Assigned to your account", "▣", "teal")}${statCard("New offers", String(offers.length), "Waiting for your response", "↗", "amber")}${statCard("Customer checks", String(reqs.filter(r => r.status === "CUSTOMER_TEST").length), "Work submitted for review", "✓", "green")}</div>
    ${pageHeading("Your work", "Assignments", "Job addresses are shown only after you accept an assignment.")}
    <section class="toolbar-panel"><div class="filter-tabs"><button class="filter-tab active" data-filter="all">All jobs <b>${reqs.length}</b></button><button class="filter-tab" data-filter="offers">New offers <b>${offers.length}</b></button><button class="filter-tab" data-filter="active">Active</button></div><label class="search-box"><span>⌕</span><input id="request-search" type="search" placeholder="Search jobs" aria-label="Search jobs"></label></section>
    <div class="request-list filterable-list">${reqs.length ? reqs.map(renderTechRequest).join("") : `<div class="empty-card"><span class="empty-icon">▣</span><h3>No assigned jobs yet</h3><p>Dispatch will send eligible work here when it becomes available.</p></div>`}</div>
    <p class="compliance-note">Work timers support service operations and do not calculate wages or payroll.</p>`;
}

function renderTechRequest(r) {
  const assignment = r.assignments?.find(a => a.technician_id === me.id && !["REASSIGNED", "DECLINED"].includes(a.status));
  const isOffer = assignment?.status === "OFFERED";
  const isMine = assignment?.status === "ACCEPTED";
  const items = catalog.filter(item => item.trade_code === r.trade_code);
  const searchable = `${r.public_ref} ${r.category} ${r.issue} ${r.status}`.toLowerCase();
  return `<article class="panel request-card filter-item" data-status-group="${isOffer ? "offers" : ["COMPLETED", "CANCELLED"].includes(r.status) ? "done" : "active"}" data-search="${esc(searchable)}"><div class="request-card-top"><div class="request-ref"><span class="ref-mark">${esc(r.category.slice(0, 1).toUpperCase())}</span><div><span class="eyebrow">${esc(r.public_ref)} · ${esc(r.zone_code.replaceAll("_", " "))}</span><h3>${esc(r.category)}</h3></div></div>${statusTag(r.status)}</div><div class="request-metadata"><span>◷ ${shortDate(r.preferred_slot)}</span>${assignment?.eta ? `<span>Arrival estimate: ${esc(shortDate(assignment.eta))}</span>` : ""}</div><p class="request-description">${esc(r.issue)}</p>${isOffer ? `<div class="assignment-offer"><div><strong>New assignment offer</strong><p>Review the request and accept only if you can complete it safely and within the service zone.</p></div><div class="button-row"><button class="btn primary" data-action="respond" data-decision="accept" data-id="${r.id}">Accept job</button><button class="btn" data-action="respond" data-decision="decline" data-id="${r.id}">Decline</button></div></div>` : ""}${r.address ? `<div class="address-panel"><span class="eyebrow">Customer location · active assignment</span><strong>⌖ ${esc(r.address)}</strong></div>` : ""}${isMine && r.status === "ACCEPTED" ? `<form class="diagnosis-form subpanel" data-id="${r.id}"><div><span class="eyebrow">Inspection</span><h3>Build the estimate</h3><p class="small muted">Choose approved catalog items. Prices are applied by the system.</p></div>${items.map(item => `<label class="catalog-choice"><input type="checkbox" name="item" value="${esc(item.code)}"><span><strong>${esc(item.name_en)}</strong><small>${fmt(item.price_xaf)} · ${item.planned_minutes} min</small></span><input aria-label="Quantity for ${esc(item.name_en)}" type="number" name="qty-${esc(item.code)}" min="1" max="20" value="1"></label>`).join("")}<button class="btn primary" type="submit">Submit diagnosis and estimate</button></form>` : ""}${isMine && r.status === "READY_TO_WORK" ? `<div class="button-row"><span class="notice success">Payment verified · work can begin</span><button class="btn primary" data-action="work" data-event="START" data-id="${r.id}">Start work</button></div>` : ""}${isMine && r.status === "IN_PROGRESS" ? `<div class="button-row"><button class="btn" data-action="work" data-event="PAUSE" data-id="${r.id}">Pause / safety stop</button><button class="btn primary" data-action="work" data-event="COMPLETE" data-id="${r.id}">Submit for customer check</button></div>` : ""}${isMine && r.status === "PAUSED" ? `<div class="button-row"><span class="notice warning">Work paused · record the reason before resuming</span><button class="btn primary" data-action="work" data-event="RESUME" data-id="${r.id}">Resume work</button></div>` : ""}${r.estimate ? `<div class="notice">Estimate ${esc(nice(r.estimate.status))} · ${fmt(r.estimate.total_xaf)}</div>` : ""}</article>`;
}

async function renderTechnicianSchedule() {
  const reqs = await getRequests();
  const upcoming = reqs.filter(r => !["COMPLETED", "CANCELLED"].includes(r.status)).sort((a, b) => String(a.preferred_slot).localeCompare(String(b.preferred_slot)));
  const days = new Map();
  upcoming.forEach(r => { const date = String(r.preferred_slot || "To be confirmed").slice(0, 10); if (!days.has(date)) days.set(date, []); days.get(date).push(r); });
  return `${pageHeading("Technician workspace", "Your schedule", "A view of your active assignments and requested visit times.")}
    <div class="notice warning schedule-note">Visit times are customer preferences. Dispatch must confirm the schedule; this MVP does not manage shifts or leave.</div>
    <div class="schedule-layout"><section class="panel schedule-summary"><span class="eyebrow">Upcoming work</span><strong class="schedule-total">${upcoming.length}</strong><p>Active assignment${upcoming.length === 1 ? "" : "s"}</p><div class="schedule-legend"><span><i class="legend-dot teal-dot"></i>Accepted job</span><span><i class="legend-dot amber-dot"></i>Offer awaiting response</span></div></section><div class="schedule-days">${days.size ? [...days.entries()].map(([day, jobs]) => `<section class="schedule-day"><div class="schedule-day-label"><span>${day === "To be confirmed" ? "Time to be confirmed" : esc(new Intl.DateTimeFormat("en", { weekday: "short", month: "short", day: "numeric" }).format(new Date(`${day}T12:00:00`)))}</span><small>${jobs.length} visit${jobs.length === 1 ? "" : "s"}</small></div>${jobs.map(job => `<article class="schedule-job"><div class="schedule-time">${esc(job.preferred_slot?.slice(11, 16) || "TBC")}</div><div class="schedule-job-main"><strong>${esc(job.category)} · ${esc(job.public_ref)}</strong><span>${esc(job.zone_code.replaceAll("_", " "))} · ${esc(job.issue)}</span></div>${statusTag(job.status)}</article>`).join("")}</section>`).join("") : `<div class="empty-card"><span class="empty-icon">◷</span><h3>Your schedule is clear</h3><p>Accepted assignments will appear here with the customer's preferred visit time.</p></div>`}</div></div>`;
}

function renderHelp(role) {
  const technician = role === "technician";
  const faqs = technician ? [
    ["A customer asks me to do work outside the estimate. What should I do?", "Pause and explain that any new scope needs an approved estimate before work starts. Do not collect an off-platform payment."],
    ["When do I get access to the service address?", "The address is shown only after you accept an active assignment. Use it only to complete that job."],
    ["When should I use a safety stop?", "Stop work if you identify an electrical, structural, gas, water, or other immediate hazard. Keep people away from the hazard and notify dispatch."],
    ["Does the job timer calculate my wages?", "No. This timer records operational work events only. Payroll and statutory calculations follow separate approved processes."]
  ] : [
    ["When will a technician be assigned?", "Dispatch checks availability and confirms the visit after your request is received. Your selected time is a preference until confirmed."],
    ["When do I pay?", "You can review the itemized estimate first. Work must not begin until payment has been verified. The local demo payment does not collect real money."],
    ["Can I ask for a different repair after approval?", "Tell the technician or support team before the additional work begins. New work must be included in an updated estimate for your approval."],
    ["How do I confirm the repair is complete?", "Use the completion controls on your request after the technician submits the job for your check. You can request rework or report a problem instead."]
  ];
  return `${pageHeading(technician ? "Technician workspace" : "Customer care", technician ? "Safety & support" : "Help & support", technician ? "Simple guidance for safe, consistent service." : "Clear answers from request to completed service.")}
    <div class="help-layout"><section class="help-callout ${technician ? "safety-callout" : ""}"><span class="help-symbol">${technician ? "!" : "?"}</span><div><span class="eyebrow light">${technician ? "Safety first" : "Need a person?"}</span><h2>${technician ? "Stop work if the site is unsafe." : "We’re here to help."}</h2><p>${technician ? "Secure the area, protect the customer, and notify dispatch before continuing." : "For urgent changes to your appointment, contact the operations team through your approved local support channel."}</p></div></section><section class="panel faq-panel"><div class="panel-heading"><div><span class="eyebrow">Quick answers</span><h2>Frequently asked questions</h2></div></div>${faqs.map(([q, a], i) => `<details class="faq-item" ${i === 0 ? "open" : ""}><summary>${esc(q)}<span aria-hidden="true">＋</span></summary><p>${esc(a)}</p></details>`).join("")}</section></div>
    <p class="compliance-note">For immediate danger, contact the appropriate local emergency service first.</p>`;
}

const progressStages = [
  { label: "New", statuses: ["REQUESTED"] },
  { label: "Dispatch", statuses: ["DISPATCHED"] },
  { label: "Diagnosis", statuses: ["ACCEPTED", "DIAGNOSING", "ESTIMATE_PENDING", "ESTIMATE_DECLINED"] },
  { label: "Approval", statuses: ["AWAITING_PAYMENT", "READY_TO_WORK"] },
  { label: "In progress", statuses: ["IN_PROGRESS", "PAUSED"] },
  { label: "Review", statuses: ["CUSTOMER_TEST", "REWORK_OPEN", "DISPUTE_OPEN"] },
  { label: "Completed", statuses: ["COMPLETED"] },
  { label: "Cancelled", statuses: ["CANCELLED"] }
];
function progressValues(rows = []) {
  const statuses = Object.fromEntries(rows.map(row => [row.status, Number(row.job_count)]));
  const values = progressStages.map(stage => stage.statuses.reduce((sum, status) => sum + (statuses[status] || 0), 0));
  const total = values.reduce((sum, value) => sum + value, 0);
  const completed = statuses.COMPLETED || 0;
  const cancelled = statuses.CANCELLED || 0;
  const denominator = total - cancelled;
  return { statuses, values, total, completed, cancelled, open: total - completed - cancelled, completion: denominator ? Math.round(completed * 100 / denominator) : 0 };
}
function renderProgressSvg(rows = []) {
  const { values } = progressValues(rows);
  const max = Math.max(...values, 1);
  const top = 24, base = 190, plotHeight = base - top, chartWidth = 840, step = 99, start = 58, barWidth = 48;
  const grid = [0, .25, .5, .75, 1].map(f => { const y = base - f * plotHeight; const label = Math.round(max * f); return `<line x1="35" y1="${y}" x2="825" y2="${y}" class="progress-grid-line"/><text x="28" y="${y + 4}" text-anchor="end" class="progress-axis-label">${label}</text>`; }).join("");
  const bars = progressStages.map((stage, i) => { const value = values[i], h = value ? Math.max(5, value / max * plotHeight) : 0, x = start + i * step, y = base - h; const color = i === 6 ? "completed" : i === 7 ? "cancelled" : "active"; return `<g><title>${stage.label}: ${value} jobs</title><rect class="progress-bar ${color}" x="${x}" y="${y}" width="${barWidth}" height="${h}" rx="7"/><text x="${x + barWidth/2}" y="${Math.max(16,y-7)}" text-anchor="middle" class="progress-value-label">${value}</text><text x="${x + barWidth/2}" y="214" text-anchor="middle" class="progress-stage-label">${stage.label}</text></g>`; }).join("");
  return `<svg class="progress-svg" viewBox="0 0 ${chartWidth} 238" role="img" aria-label="Current service job counts by workflow stage">${grid}${bars}</svg>`;
}
function renderAdminProgressPanel(rows = []) {
  const data = progressValues(rows);
  return `<section class="panel admin-progress-panel"><div class="panel-heading"><div><span class="eyebrow">Live operations</span><h2>Service progress</h2></div><span class="live-indicator"><i></i>Live · 15 sec</span></div><div class="progress-kpis"><div><strong id="progress-total">${data.total}</strong><span>Total jobs</span></div><div><strong id="progress-open">${data.open}</strong><span>Open jobs</span></div><div><strong id="progress-completion">${data.completion}%</strong><span>Completion rate</span></div></div><div id="admin-progress-chart">${renderProgressSvg(rows)}</div><div class="progress-chart-footer"><span>Jobs grouped by current workflow stage</span><span id="progress-updated">Live snapshot · ${new Date().toLocaleTimeString()}</span></div></section>`;
}
async function refreshAdminProgress() {
  if (progressRefreshPending || me?.role !== "admin" || currentView !== "home" || !$("#admin-progress-chart")) return;
  progressRefreshPending = true;
  try {
    const result = await api("/admin/progress");
    if (me?.role !== "admin" || currentView !== "home") return;
    const data = progressValues(result.statuses || []);
    $("#admin-progress-chart").innerHTML = renderProgressSvg(result.statuses || []);
    $("#progress-total").textContent = data.total;
    $("#progress-open").textContent = data.open;
    $("#progress-completion").textContent = `${data.completion}%`;
    $("#progress-updated").textContent = `Updated ${new Date().toLocaleTimeString()}`;
  } catch (error) {
    const updated = $("#progress-updated");
    if (updated) updated.textContent = `Live update unavailable · ${error.message}`;
  } finally { progressRefreshPending = false; }
}
function startAdminProgressMonitor() {
  if (progressRefreshTimer) return;
  refreshAdminProgress();
  progressRefreshTimer = setInterval(refreshAdminProgress, 15000);
}
function stopAdminProgressMonitor() {
  if (progressRefreshTimer) clearInterval(progressRefreshTimer);
  progressRefreshTimer = null;
  progressRefreshPending = false;
}
async function renderOperationsOverview() {
  const [requests, queueResult, staffResult, progressResult] = await Promise.all([getRequests(), api("/dispatch/queue").catch(() => ({ requests: [] })), api("/staff").catch(() => ({ technicians: [] })), me.role === "admin" ? api("/admin/progress") : Promise.resolve(null)]);
  const open = requests.filter(r => !["COMPLETED", "CANCELLED"].includes(r.status));
  const needsAttention = requests.filter(r => ["AWAITING_PAYMENT", "REWORK_OPEN", "DISPUTE_OPEN"].includes(r.status));
  return `<section class="welcome-banner ops-banner"><div><span class="eyebrow light">Operations workspace</span><h1>Good morning, ${esc(me.name.split(" ")[0])}.</h1><p>Keep dispatch, service quality, and customer updates moving in one place.</p></div><div class="banner-mark" aria-hidden="true">↗</div></section>
    <div class="stats-row">${statCard("Open jobs", String(open.length), "Across all service zones", "▤", "teal")}${statCard("Need dispatch", String(queueResult.requests.length), "Unassigned or awaiting response", "⇄", "amber")}${statCard("Active technicians", String(new Set(staffResult.technicians.map(t => t.id)).size), "Authorized staff", "♙", "blue")}${statCard("Needs attention", String(needsAttention.length), "Payment or customer case", "!", "red")}</div>
    ${progressResult ? renderAdminProgressPanel(progressResult.statuses) : ""}
    <div class="dashboard-columns"><section class="panel"><div class="panel-heading"><div><span class="eyebrow">Next actions</span><h2>Dispatch queue</h2></div><button class="text-button" data-view="dispatch">Open dispatch board →</button></div>${queueResult.requests.slice(0, 4).map(r => `<div class="queue-row"><span class="ref-mark small-mark">${esc(r.category.slice(0, 1))}</span><div class="queue-copy"><strong>${esc(r.public_ref)} · ${esc(r.category)}</strong><span>${esc(r.zone_code.replaceAll("_", " "))} · ${esc(r.issue)}</span></div>${statusTag(r.status)}</div>`).join("") || `<div class="empty-inline">Dispatch queue is clear.</div>`}</section><section class="panel attention-panel"><div class="panel-heading"><div><span class="eyebrow">Follow up</span><h2>Attention needed</h2></div></div>${needsAttention.slice(0, 4).map(r => `<div class="attention-row"><span class="attention-dot"></span><div><strong>${esc(r.public_ref)} · ${esc(r.category)}</strong><span>${esc(nice(r.status))} · ${esc(r.zone_code.replaceAll("_", " "))}</span></div></div>`).join("") || `<div class="empty-inline">No exceptions to review right now.</div>`}</section></div>
    <div class="section-heading-inline"><div><span class="eyebrow">Service delivery</span><h2>Recently updated jobs</h2></div><button class="text-button" data-view="requests">View all jobs →</button></div><section class="panel table-panel"><div class="table-scroll"><table><thead><tr><th>Request</th><th>Customer</th><th>Service area</th><th>Estimate</th><th>Status</th></tr></thead><tbody>${open.slice(0, 8).map(r => `<tr><td><strong>${esc(r.public_ref)}</strong><small>${esc(r.category)}</small></td><td>${esc(r.customer_name || "Customer")}</td><td>${esc(r.zone_code.replaceAll("_", " "))}</td><td>${r.estimate ? fmt(r.estimate.total_xaf) : "—"}</td><td>${statusTag(r.status)}</td></tr>`).join("") || `<tr><td colspan="5" class="table-empty">No open jobs at the moment.</td></tr>`}</tbody></table></div></section>`;
}

function renderCatalogAdminForm(item, categories) {
  const value=(key,fallback="")=>esc(item?.[key] ?? fallback);
  return `<form class="admin-form admin-catalog-form" data-catalog-form><input name="code" type="hidden" value="${value("code")}"><div class="form-grid"><label>Service name<input name="name" value="${value("name_en")}" required maxlength="180"></label><label>French name<input name="nameFr" value="${value("name_fr")}" maxlength="180"></label><label>Category<select name="category" required>${categories.map(category=>`<option value="${esc(category.category_name)}" ${item?.category===category.category_name?"selected":""}>${esc(category.category_name)} · ${esc(category.trade_code)}</option>`).join("")}</select></label><label>Lowest estimate (XAF)<input name="priceMin" type="number" min="0" step="500" value="${value("price_min_xaf",5000)}" required></label><label>Highest estimate (XAF)<input name="priceMax" type="number" min="0" step="500" value="${value("price_max_xaf",15000)}" required></label><label>Planned minutes<input name="plannedMinutes" type="number" min="0" max="1440" value="${value("planned_minutes",45)}" required></label><label class="span-two">Description<textarea name="description" maxlength="2000">${value("description")}</textarea></label></div><button class="btn primary" type="submit">${item?"Save service and prices":"Add service"}</button></form>`;
}

async function renderAdminManagement() {
  const [categoryResult, technicianResult] = await Promise.all([api("/admin/categories"), api("/admin/technicians")]);
  const categories=categoryResult.categories||[], technicians=technicianResult.technicians||[];
  const trades=[...new Map(categories.map(category=>[category.trade_code,category])).values()];
  return `${pageHeading("Admin controls","Team & service management","Add technicians, maintain service categories, and set catalog estimate ranges.")}
    <div class="notice warning admin-management-note"><strong>Price ranges are planning estimates.</strong> Review local rates before they are presented as customer quotations.</div>
    <section class="panel admin-management-section"><div class="panel-heading"><div><span class="eyebrow">Team access</span><h2>Add a technician</h2><p>Create a technician login and authorize the trade and service areas they can accept.</p></div></div><form id="admin-tech-form" class="admin-form"><div class="form-grid"><label>Full name<input name="name" autocomplete="name" required></label><label>Email address<input name="email" type="email" autocomplete="email" required></label><label>Phone number<input name="phone" type="tel" autocomplete="tel"></label><label>Account password<input name="password" type="password" minlength="12" autocomplete="new-password" required><small>At least 12 characters. Share the password privately with the technician.</small></label><label>Primary trade<select name="tradeCode" required>${trades.map(trade=>`<option value="${esc(trade.trade_code)}">${esc(trade.trade_code)}</option>`).join("")}</select></label><fieldset class="admin-zone-field"><legend>Authorized service areas</legend><div class="admin-zone-grid">${zones.map(zone=>`<label><input type="checkbox" name="zones" value="${esc(zone.code)}"> ${esc(zone.name)}</label>`).join("")}</div></fieldset></div><button class="btn primary" type="submit">Create technician account</button></form></section>
    <section class="panel admin-management-section"><div class="panel-heading"><div><span class="eyebrow">Technician roster</span><h2>${technicians.length} technician${technicians.length===1?"":"s"}</h2></div></div><div class="table-scroll"><table><thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Trades</th><th>Service areas</th></tr></thead><tbody>${technicians.map(tech=>`<tr><td><strong>${esc(tech.display_name)}</strong></td><td>${esc(tech.email)}</td><td>${esc(tech.phone||"—")}</td><td>${esc(tech.trades||"—")}</td><td>${esc(tech.zones||"—")}</td></tr>`).join("")||`<tr><td colspan="5" class="table-empty">No technician accounts yet.</td></tr>`}</tbody></table></div></section>
    <section class="panel admin-management-section"><div class="panel-heading"><div><span class="eyebrow">Service taxonomy</span><h2>Categories</h2><p>Categories connect services to the trade used for technician matching.</p></div></div><form class="admin-form admin-category-form" data-original-name=""><div class="form-grid"><label>New category name<input name="categoryName" required maxlength="80" placeholder="e.g. Solar installation"></label><label>Technician trade code<input name="tradeCode" required pattern="[a-z][a-z0-9_]{1,39}" placeholder="e.g. solar"></label><label>Image path in assets/Images/<input name="imagePath" value="assets/Images/catalog/handyman-1.jpg"></label><label>Icon path in assets/Icons/<input name="iconPath" value="assets/Icons/clarity--tools-solid.svg"></label></div><button class="btn primary" type="submit">Add category</button></form><div class="admin-category-list">${categories.map(category=>`<details class="admin-edit-card"><summary>${esc(category.category_name)} <small>${esc(category.trade_code)}</small></summary><form class="admin-form admin-category-form" data-original-name="${esc(category.category_name)}"><div class="form-grid"><label>Category name<input name="categoryName" value="${esc(category.category_name)}" required maxlength="80"></label><label>Technician trade code<input name="tradeCode" value="${esc(category.trade_code)}" required pattern="[a-z][a-z0-9_]{1,39}"></label><label>Image path<input name="imagePath" value="${esc(category.image_path)}" required></label><label>Icon path<input name="iconPath" value="${esc(category.icon_path)}" required></label></div><button class="btn" type="submit">Save category</button></form></details>`).join("")}</div></section>
    <section class="panel admin-management-section"><div class="panel-heading"><div><span class="eyebrow">Catalog & pricing</span><h2>Add a service</h2><p>Services inherit the selected category trade and its image and icon.</p></div></div>${renderCatalogAdminForm(null,categories)}</section>
    <section class="panel admin-management-section"><div class="panel-heading"><div><span class="eyebrow">Published jobs</span><h2>${catalog.length} service items</h2></div><label class="search-box"><span>⌕</span><input id="admin-catalog-search" type="search" placeholder="Find a service" aria-label="Search services"></label></div><div class="admin-catalog-list">${catalog.map(item=>`<details class="admin-edit-card admin-service-edit" data-search="${esc(`${item.code} ${item.name_en} ${item.category}`.toLowerCase())}"><summary><span class="code-chip">${esc(item.code)}</span> ${esc(item.name_en)} <small>${esc(item.category)} · ${priceRange(item)}</small></summary>${renderCatalogAdminForm(item,categories)}</details>`).join("")}</div></section>`;
}
async function renderDispatch() {
  const [queue, staff] = await Promise.all([api("/dispatch/queue"), api("/staff").catch(() => ({ technicians: [] }))]);
  return `${pageHeading("Operations workspace", "Dispatch board", "Match each request with an active technician authorized for the trade and zone.", `<span class="tag-soft">${queue.requests.length} awaiting dispatch</span>`)}
    <section class="toolbar-panel"><div class="filter-tabs"><button class="filter-tab active" data-filter="all">All requests <b>${queue.requests.length}</b></button><button class="filter-tab" data-filter="REQUESTED">New</button><button class="filter-tab" data-filter="DISPATCHED">Awaiting response</button></div><label class="search-box"><span>⌕</span><input id="request-search" type="search" placeholder="Search queue" aria-label="Search dispatch queue"></label></section>
    <div class="dispatch-list">${queue.requests.length ? queue.requests.map(r => { const eligible = staff.technicians.filter(t => t.trade_code === r.trade_code && t.zone_code === r.zone_code);return `<article class="panel dispatch-card filter-item" data-status-group="${r.status}" data-search="${esc(`${r.public_ref} ${r.category} ${r.issue} ${r.zone_code}`.toLowerCase())}"><div class="dispatch-main"><div class="request-card-top"><div class="request-ref"><span class="ref-mark">${esc(r.category.slice(0, 1))}</span><div><span class="eyebrow">${esc(r.public_ref)} · ${esc(r.created_at.slice(0, 10))}</span><h3>${esc(r.category)}</h3></div></div>${statusTag(r.status)}</div><p>${esc(r.issue)}</p><div class="request-metadata"><span>Trade: ${esc(r.trade_code)}</span><span>Zone: ${esc(r.zone_code.replaceAll("_", " "))}</span><span>Preferred: ${shortDate(r.preferred_slot)}</span></div></div><div class="dispatch-assignment">${eligible.length ? `<label>Eligible technician<select id="staff-${r.id}">${eligible.map(t => `<option value="${t.id}">${esc(t.display_name)} · ${esc(t.trade_code)}</option>`).join("")}</select></label><label>Assignment note<input id="reason-${r.id}" value="Trade and zone match"></label><button class="btn primary full-width" data-action="assign" data-id="${r.id}">Send assignment offer →</button>` : `<div class="notice warning"><strong>No eligible technician</strong><span>There is no active authorization for this trade and zone.</span></div>`}</div></article>`;}).join("") : `<div class="empty-card"><span class="empty-icon">⇄</span><h3>Dispatch queue is clear</h3><p>New requests will appear here for trade and zone matching.</p></div>`}</div>`;
}

async function renderOperationsJobs() {
  const requests = await getRequests();
  return `${pageHeading("Operations workspace", "All service jobs", "Search requests and review their current stage. Customer addresses are not shown in this list.")}
    <section class="toolbar-panel"><div class="filter-tabs"><button class="filter-tab active" data-filter="all">All <b>${requests.length}</b></button><button class="filter-tab" data-filter="open">Open</button><button class="filter-tab" data-filter="done">Closed</button></div><label class="search-box"><span>⌕</span><input id="request-search" type="search" placeholder="Search ref, service, zone" aria-label="Search jobs"></label></section>
    <div class="job-list">${requests.length ? requests.map(r => `<article class="panel job-row filter-item" data-status-group="${["COMPLETED", "CANCELLED"].includes(r.status) ? "done" : "open"}" data-search="${esc(`${r.public_ref} ${r.category} ${r.customer_name} ${r.zone_code} ${r.issue}`.toLowerCase())}"><span class="ref-mark">${esc(r.category.slice(0, 1))}</span><div class="job-row-copy"><span class="eyebrow">${esc(r.public_ref)} · ${esc(shortDate(r.created_at))}</span><strong>${esc(r.category)} · ${esc(r.customer_name || "Customer")}</strong><small>${esc(r.zone_code.replaceAll("_", " "))} · ${esc(r.issue)}</small></div>${r.estimate ? `<strong class="job-amount">${fmt(r.estimate.total_xaf)}</strong>` : `<span class="job-amount muted">No estimate</span>`}${statusTag(r.status)}${me.role === "admin" && r.estimate?.payment?.status === "PENDING" ? `<button class="btn primary small-btn" data-action="dev-confirm" data-id="${r.estimate.payment.id}">Confirm demo payment</button>` : ""}</article>`).join("") : `<div class="empty-card">There are no requests to show.</div>`}</div>`;
}

function renderPricebook() {
  const categories = [...new Set(catalog.map(item => item.category))];
  return `${pageHeading("Operations workspace", "Service pricebook", "Approved catalog items used by technicians to prepare customer estimates.", `<span class="status-pill amber"><i></i>Example rates · approval required</span>`)}
    <div class="notice warning pricebook-warning"><strong>Sample data only.</strong> These illustrative XAF values must be approved by finance and reviewed for taxes before being used to charge customers.</div>
    <section class="toolbar-panel"><div class="filter-tabs price-filter"><button class="filter-tab active" data-filter="all">All items <b>${catalog.length}</b></button>${categories.map(category => `<button class="filter-tab" data-filter="${esc(category)}">${esc(category)}</button>`).join("")}</div><label class="search-box"><span>⌕</span><input id="price-search" type="search" placeholder="Find an item" aria-label="Search pricebook"></label></section>
    <section class="panel table-panel"><div class="table-scroll"><table><thead><tr><th>Code</th><th>Service item</th><th>Trade</th><th>Unit</th><th>Planned time</th><th>Planning estimate range</th></tr></thead><tbody>${catalog.map(item => `<tr class="filter-item" data-status-group="${esc(item.category)}" data-search="${esc(`${item.code} ${item.name_en} ${item.trade_code}`.toLowerCase())}"><td><span class="code-chip">${esc(item.code)}</span></td><td><strong>${esc(item.name_en)}</strong><small>${esc(item.description)}</small></td><td>${esc(item.trade_code)}</td><td>${esc(item.unit)}</td><td>${esc(item.planned_minutes)} min</td><td><strong>${priceRange(item)}</strong></td></tr>`).join("") || `<tr><td colspan="6" class="table-empty">No catalog items are published.</td></tr>`}</tbody></table></div></section>
    <p class="compliance-note">Price changes require an approved pricebook process. Editing and approval controls are not part of this MVP.</p>`;
}

function renderPayroll() {
  return `${pageHeading("People & payroll", "Payroll workspace", "A safe landing page for authorized payroll staff.")}
    <div class="notice warning"><strong>Payroll data is not connected.</strong> This MVP does not calculate wages, statutory deductions, CNPS, or payslips.</div>
    <div class="stats-row payroll-stats">${statCard("Payroll period", "Not configured", "Set up an approved pay calendar", "◷", "blue")}${statCard("Work events", "Not available", "Operational timer data is separate", "▤", "teal")}${statCard("Employee records", "Restricted", "Use the approved HR system", "♙", "amber")}</div>
    <section class="panel payroll-panel"><span class="eyebrow">Next steps</span><h2>Keep payroll in the approved process</h2><p>Payroll and employee records require a separate permission model and locally reviewed statutory rules. No salary or tax figures are presented here.</p><ul><li>Confirm the payroll owner and approval roles.</li><li>Review contracts, deductions, benefits, and CNPS obligations with a qualified local advisor.</li><li>Use a tested, access-controlled payroll system until an approved integration is implemented.</li></ul></section>`;
}

function applyFilters(filter = "all", search = "") {
  const term = search.trim().toLowerCase();
  document.querySelectorAll(".filter-item").forEach(item => {
    const group = item.dataset.statusGroup || "";
    const matchFilter = filter === "all" || (filter === "open" ? group !== "done" : filter === "done" ? group === "done" : group === filter);
    const matchSearch = !term || (item.dataset.search || item.textContent.toLowerCase()).includes(term);
    item.classList.toggle("hidden", !(matchFilter && matchSearch));
  });
}

function bindAdminManagement() {
  $("#admin-tech-form")?.addEventListener("submit", async event => {
    event.preventDefault(); const form=event.currentTarget; const fd=new FormData(form);
    const payload={name:fd.get("name"),email:fd.get("email"),phone:fd.get("phone"),password:fd.get("password"),tradeCode:fd.get("tradeCode"),zones:fd.getAll("zones")};
    try { await api("/admin/technicians",{method:"POST",body:JSON.stringify(payload)}); notify("Technician account created.","success"); await render(); }
    catch(error){notify(error.message,"error");}
  });
  document.querySelectorAll(".admin-category-form").forEach(form=>form.addEventListener("submit",async event=>{
    event.preventDefault();const fd=new FormData(form);const payload=Object.fromEntries(fd.entries());payload.originalName=form.dataset.originalName||"";
    try{await api("/admin/categories/save",{method:"POST",body:JSON.stringify(payload)});notify(payload.originalName?"Category updated.":"Category added.","success");catalog=(await api("/catalog")).items||[];await render();}
    catch(error){notify(error.message,"error");}
  }));
  document.querySelectorAll("[data-catalog-form]").forEach(form=>form.addEventListener("submit",async event=>{
    event.preventDefault();const payload=Object.fromEntries(new FormData(form).entries());payload.priceMin=Number(payload.priceMin);payload.priceMax=Number(payload.priceMax);payload.plannedMinutes=Number(payload.plannedMinutes);
    try{const result=await api("/admin/catalog/save",{method:"POST",body:JSON.stringify(payload)});notify(payload.code?"Service and estimate range updated.":"Service added to the catalog.","success");catalog=(await api("/catalog")).items||[];await render();}
    catch(error){notify(error.message,"error");}
  }));
  $("#admin-catalog-search")?.addEventListener("input",event=>{const term=event.currentTarget.value.trim().toLowerCase();document.querySelectorAll(".admin-service-edit").forEach(card=>card.classList.toggle("hidden",!card.dataset.search.includes(term)));});
}
function bind() {
  if (me?.role === "admin" && currentView === "management") bindAdminManagement();
  $("#auth-form")?.addEventListener("submit", async event => {
    event.preventDefault();
    const data = Object.fromEntries(new FormData(event.currentTarget).entries());
    try {
      if (authMode === "register") { data.consent = data.consent === "on"; data.termsVersion = "v1"; data.language = language; }
      const result = authMode === "register" ? await api("/auth/register", { method: "POST", body: JSON.stringify(data) }) : await api("/auth/login", { method: "POST", body: JSON.stringify(data) });
      token = result.token; me = result.user; authVisible = false; localStorage.setItem(tokenKey, token); currentView = "home"; notify("You’re signed in.", "success"); await render();
    } catch (error) { notify(error.message, "error"); }
  });
  $("#request-form")?.addEventListener("submit", async event => {
    event.preventDefault(); const form = event.currentTarget; const data = Object.fromEntries(new FormData(form).entries());
    data.tradeCode = $("#request-category").selectedOptions[0]?.dataset.trade; data.consentVersion = "v1"; data.consent = data.consent === "on";
    const submit = form.querySelector('[type="submit"]'); submit.disabled = true; submit.textContent = "Sending request…";
    try { await api("/requests", { method: "POST", headers: { "Idempotency-Key": crypto.randomUUID() }, body: JSON.stringify(data) }); notify("Request sent. Dispatch will confirm availability.", "success"); currentView = "requests"; await render(); }
    catch (error) { notify(error.message, "error"); submit.disabled = false; submit.innerHTML = "Send request <span>→</span>"; }
  });
  document.querySelectorAll(".diagnosis-form").forEach(form => form.addEventListener("submit", async event => {
    event.preventDefault(); const fd = new FormData(form); const codes = fd.getAll("item"); if (!codes.length) { notify("Select at least one approved work item.", "error"); return; }
    try { await api(`/requests/${form.dataset.id}/diagnosis`, { method: "POST", body: JSON.stringify({ items: codes.map(code => ({ code, quantity: Number(fd.get(`qty-${code}`) || 1) })) }) }); notify("Estimate submitted for customer review.", "success"); await render(); }
    catch (error) { notify(error.message, "error"); }
  }));
  document.querySelectorAll("[data-view]").forEach(control => control.addEventListener("click", async event => { event.preventDefault(); currentView = control.dataset.view; if (control.dataset.view === "home" && control.hasAttribute("data-action")) await render(); else await render(); }));
  document.querySelectorAll("[data-action]").forEach(button => button.addEventListener("click", event => { if (button.dataset.action === "focus-request") { event.preventDefault(); $("#request-form")?.scrollIntoView({ behavior: "smooth", block: "center" }); return; } runAction(button); }));
  document.querySelectorAll("[data-category]").forEach(button => button.addEventListener("click", () => { const select = $("#request-category"); if (select) { select.value = button.dataset.category; $("#request-form").scrollIntoView({ behavior: "smooth", block: "center" }); select.focus(); } }));
  document.querySelectorAll("[data-service-request]").forEach(button => button.addEventListener("click", () => { const item = catalog.find(entry => entry.code === button.dataset.serviceRequest); if (!item) return; const select = $("#request-category"); if (select) select.value = item.category; const issue = $("#request-issue"); if (issue) issue.value = `${item.name_en}: `; $("#request-form")?.scrollIntoView({ behavior: "smooth", block: "center" }); issue?.focus(); }));
  document.querySelectorAll("[data-filter]").forEach(button => button.addEventListener("click", () => { document.querySelectorAll("[data-filter]").forEach(tab => tab.classList.remove("active")); button.classList.add("active"); applyFilters(button.dataset.filter, $("#request-search")?.value || $("#price-search")?.value || ""); }));
  $("#request-search")?.addEventListener("input", event => applyFilters(document.querySelector("[data-filter].active")?.dataset.filter || "all", event.currentTarget.value));
  $("#price-search")?.addEventListener("input", event => applyFilters(document.querySelector("[data-filter].active")?.dataset.filter || "all", event.currentTarget.value));
}

async function runAction(button) {
  const action = button.dataset.action, id = button.dataset.id;
  try {
    if (action === "toggle-auth") { authMode = authMode === "login" ? "register" : "login"; authVisible = true; return render(); }
    if (action === "show-login") { authMode = "login"; authVisible = true; return render(); }
    if (action === "show-register") { authMode = "register"; authVisible = true; return render(); }
    if (action === "show-home") { authVisible = false; return render(); }
    if (action === "reload") { return boot(); }
    if (action === "logout") { await api("/auth/logout", { method: "POST" }); token = ""; me = null; localStorage.removeItem(tokenKey); currentView = "home"; return render(); }
    if (action === "assign") { await api(`/requests/${id}/assignments`, { method: "POST", body: JSON.stringify({ technicianId: $("#staff-" + id).value, reason: $("#reason-" + id).value }) }); notify("Assignment offer sent.", "success"); }
    if (action === "respond") { await api(`/requests/${id}/response`, { method: "POST", body: JSON.stringify({ decision: button.dataset.decision }) }); notify(`Assignment ${button.dataset.decision}ed.`, "success"); }
    if (action === "accept-estimate" || action === "decline-estimate") { await api(`/estimates/${id}/decision`, { method: "POST", body: JSON.stringify({ decision: action === "accept-estimate" ? "accept" : "decline" }) }); notify(action === "accept-estimate" ? "Estimate approved." : "Estimate declined.", "success"); }
    if (action === "pay") { await api(`/estimates/${id}/payments`, { method: "POST", headers: { "Idempotency-Key": crypto.randomUUID() }, body: "{}" }); notify("Demo payment is pending. No money was collected.", "info"); }
    if (action === "dev-confirm") { await api(`/payments/${id}/dev-confirm`, { method: "POST", body: "{}" }); notify("Local demo payment marked confirmed. No money moved.", "success"); }
    if (action === "work") { const body = { action: button.dataset.event }; if (body.action === "PAUSE") { body.reason = prompt("Pause reason (for example safety stop or missing part):") || ""; if (!body.reason) return; } await api(`/requests/${id}/work-events`, { method: "POST", body: JSON.stringify(body) }); notify("Work status updated.", "success"); }
    if (action === "complete") { await api(`/requests/${id}/completion`, { method: "POST", body: JSON.stringify({ decision: "complete" }) }); notify("Completion confirmed. Thank you.", "success"); }
    if (action === "case") { const detail = prompt(button.dataset.kind === "rework" ? "What needs another look?" : "Describe the problem:"); if (!detail?.trim()) return; await api(`/requests/${id}/completion`, { method: "POST", body: JSON.stringify({ decision: button.dataset.kind, detail }) }); notify("Your case has been opened for review.", "success"); }
    await render();
  } catch (error) { notify(error.message, "error"); }
}

boot();
