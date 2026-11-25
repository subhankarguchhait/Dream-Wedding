/**
 * Dreamm Framme Event CMS — admin interactions
 * AJAX forms (with server-side validation errors), toasts, modals, confirmations,
 * section editor (show/hide, reorder), repeaters, image & icon fields, publish switches,
 * drag-and-drop ordering, enquiry viewer, administrator modal and notification polling.
 */
(function () {
  "use strict";
  const BASE = document.querySelector('meta[name="base-url"]')?.content || "/";
  const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content || "";
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const esc = (s) => String(s ?? "").replace(/[&<>"']/g, (c) => ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]));
  const urlFor = (p) => (/^(https?:|blob:|data:)/i.test(p) ? p : BASE + String(p).replace(/^\/+/, ""));

  /* ------------------------------------------------------------ toasts */
  function toast(message, type = "success") {
    const zone = $("#toasts");
    const icons = { success: "fa-solid fa-circle-check", error: "fa-solid fa-circle-exclamation", info: "fa-solid fa-bell" };
    const t = document.createElement("div");
    t.className = `toast toast--${type}`;
    t.setAttribute("role", type === "error" ? "alert" : "status");
    t.innerHTML = `<i class="${icons[type] || icons.info}"></i><span>${esc(message)}</span><button type="button" aria-label="Dismiss"><i class="fa-solid fa-xmark"></i></button>`;
    const close = () => { t.classList.add("is-leaving"); setTimeout(() => t.remove(), 300); };
    t.querySelector("button").onclick = close;
    zone.append(t);
    setTimeout(close, type === "error" ? 8000 : 4500);
  }
  try { JSON.parse(document.body.dataset.flash || "[]").forEach((f) => toast(f.message, f.type === "error" ? "error" : "success")); } catch (e) { /* ignore */ }

  /* ------------------------------------------------------------ modals */
  function openModal({ title = "", body, width = 720, onClose } = {}) {
    const prev = document.activeElement;
    const el = document.createElement("div");
    el.className = "modal";
    el.innerHTML = `<div class="modal__backdrop"></div><div class="modal__panel" role="dialog" aria-modal="true" style="--w:${width}px"><div class="modal__head"><h2></h2><button type="button" class="btn btn--ghost btn--icon" aria-label="Close" data-modal-close><i class="fa-solid fa-xmark"></i></button></div><div class="modal__body"></div></div>`;
    el.querySelector("h2").textContent = title;
    el.querySelector(".modal__panel").setAttribute("aria-label", title);
    if (body instanceof Node) el.querySelector(".modal__body").append(body); else el.querySelector(".modal__body").innerHTML = body || "";
    let closed = false;
    const close = () => {
      if (closed) return; closed = true;
      el.remove(); document.removeEventListener("keydown", onKey);
      if (!$(".modal")) document.body.classList.remove("is-locked");
      prev?.focus?.(); onClose?.();
    };
    const onKey = (e) => { const all = $$(".modal"); if (e.key === "Escape" && all[all.length - 1] === el) close(); };
    el.addEventListener("click", (e) => { if (e.target.classList.contains("modal__backdrop") || e.target.closest("[data-modal-close]")) close(); });
    document.addEventListener("keydown", onKey);
    document.body.append(el);
    document.body.classList.add("is-locked");
    setTimeout(() => (el.querySelector(".modal__body input:not([type=hidden]), .modal__body select, .modal__body textarea") || el.querySelector("[data-modal-close]"))?.focus(), 40);
    return { el, close, body: el.querySelector(".modal__body") };
  }

  function confirmDialog(message, { danger = false, confirmText } = {}) {
    return new Promise((resolve) => {
      let answered = false;
      const body = document.createElement("div");
      body.innerHTML = `<div class="confirm${danger ? " is-danger" : ""}"><i class="fa-solid ${danger ? "fa-trash-can" : "fa-circle-question"}"></i><h3>${danger ? "Are you sure?" : "Please confirm"}</h3><p></p></div>
        <div class="modal__foot" style="margin:18px -22px -22px"><button type="button" class="btn btn--ghost" data-no>Cancel</button><button type="button" class="btn ${danger ? "btn--danger" : "btn--forest"}" data-yes>${esc(confirmText || (danger ? "Delete" : "Confirm"))}</button></div>`;
      body.querySelector("p").textContent = message;
      const m = openModal({ title: danger ? "Please confirm" : "Confirm", body, width: 460, onClose: () => { if (!answered) resolve(false); } });
      body.querySelector("[data-yes]").onclick = () => { answered = true; m.close(); resolve(true); };
      body.querySelector("[data-no]").onclick = () => { answered = true; m.close(); resolve(false); };
      setTimeout(() => body.querySelector("[data-yes]").focus(), 60);
    });
  }

  /* ------------------------------------------------------------ requests */
  async function api(url, data) {
    const fd = data instanceof FormData ? data : Object.entries(data || {}).reduce((f, [k, v]) => { (Array.isArray(v) ? v : [v]).forEach((x) => f.append(Array.isArray(v) ? k + "[]" : k, x)); return f; }, new FormData());
    if (!fd.has("csrf_token")) fd.append("csrf_token", CSRF());
    let res;
    try {
      res = await fetch(urlFor(url), { method: "POST", body: fd, credentials: "same-origin", headers: { "X-Requested-With": "XMLHttpRequest", Accept: "application/json", "X-CSRF-Token": CSRF() } });
    } catch (e) { return { ok: false, message: "Network error — check your connection and try again." }; }
    const json = await res.json().catch(() => ({ ok: false, message: `Server error (${res.status}). Check storage/logs for details.` }));
    if (res.status === 401 && json.login) { location.href = BASE + "admin/login.php"; }
    return json;
  }

  /* ------------------------------------------------------------ AJAX forms */
  function clearErrors(form) {
    $$("[data-err-for]", form).forEach((e) => (e.textContent = ""));
    $$(".f.is-invalid", form).forEach((f) => f.classList.remove("is-invalid"));
  }
  function showErrors(form, errors) {
    let first = null;
    Object.entries(errors || {}).forEach(([name, msg]) => {
      const holder = form.querySelector(`[data-err-for="${CSS.escape(name)}"]`);
      if (holder) {
        holder.textContent = msg;
        holder.closest(".f")?.classList.add("is-invalid");
        holder.closest(".rep-item")?.classList.remove("is-collapsed");
        first = first || holder.closest(".f");
      }
    });
    first?.scrollIntoView({ behavior: "smooth", block: "center" });
  }
  async function submitAjax(form, submitter) {
    if (form.dataset.confirm && !(await confirmDialog(form.dataset.confirm, { danger: form.hasAttribute("data-danger") }))) return;
    const btn = submitter || form.querySelector("[type=submit]");
    btn?.classList.add("is-loading"); if (btn) btn.disabled = true;
    clearErrors(form);
    const data = await api(form.getAttribute("action"), new FormData(form));
    btn?.classList.remove("is-loading"); if (btn) btn.disabled = false;
    if (!data.ok) {
      showErrors(form, data.errors);
      toast(data.message || "Something went wrong.", "error");
      if (form.hasAttribute("data-autosubmit")) form.reset();
      return;
    }
    markClean(form);
    if (data.redirect) {
      const target = new URL(BASE + data.redirect.replace(/^\/+/, ""), location.href);
      if (target.pathname === location.pathname && target.search === location.search) { location.hash = target.hash; location.reload(); }
      else location.href = target.href;
      return;
    }
    toast(data.message);
    if (data.reload || (form.closest(".modal") && !form.closest(".modal").querySelector("[data-keep-open]"))) { setTimeout(() => location.reload(), 650); return; }
    if (data.remove) {
      const row = form.closest("tr, .media-item, .rep-item");
      if (row) { row.style.transition = "opacity .3s"; row.style.opacity = "0"; setTimeout(() => row.remove(), 300); }
    }
    if (data.reset) form.reset();
    if (data.summary !== undefined) { const s = form.closest(".sec-card")?.querySelector(".sec-card__title small"); if (s && !form.closest(".sec-card").querySelector(".alert--info")) s.textContent = data.summary; }
    if (data.status && form.hasAttribute("data-autosubmit")) {
      const sel = form.querySelector("select"); sel.className = `status-select status-select--${data.status}`;
      form.closest("tr")?.classList.toggle("is-unread", data.status === "new");
      pollNotifications();
    }
  }
  document.addEventListener("submit", (e) => {
    const form = e.target.closest("form[data-ajax]");
    if (!form) return;
    e.preventDefault();
    submitAjax(form, e.submitter);
  });
  document.addEventListener("submit", async (e) => {   // non-AJAX forms that still need a confirmation (logout)
    const form = e.target;
    if (form.matches("form[data-confirm]:not([data-ajax])") && !form.dataset.confirmed) {
      e.preventDefault();
      if (await confirmDialog(form.dataset.confirm)) { form.dataset.confirmed = "1"; form.submit(); }
    }
  }, true);
  document.addEventListener("change", (e) => {
    const form = e.target.closest("form[data-autosubmit]");
    if (form && e.target.matches("select")) submitAjax(form);
  });

  /* ------------------------------------------------------------ unsaved changes */
  const markDirty = (form) => { form.dataset.dirty = "1"; (form.closest(".sec-card") || form).classList.add("is-dirty"); };
  function markClean(form) { delete form.dataset.dirty; (form.closest(".sec-card") || form).classList.remove("is-dirty"); }
  document.addEventListener("input", (e) => { const f = e.target.closest("form[data-track-dirty]"); if (f) markDirty(f); });
  addEventListener("beforeunload", (e) => { if ($("form[data-dirty]")) { e.preventDefault(); e.returnValue = ""; } });

  /* ------------------------------------------------------------ layout */
  document.addEventListener("click", (e) => {
    if (e.target.closest("[data-sidebar-toggle]")) document.body.classList.toggle("sidebar-open");
    if (e.target.closest("[data-sidebar-close]")) document.body.classList.remove("sidebar-open");
    const dd = e.target.closest("[data-dropdown]");
    $$(".dropdown.is-open").forEach((d) => { if (!dd || d !== dd.parentElement) { d.classList.remove("is-open"); d.querySelector("[data-dropdown]").setAttribute("aria-expanded", "false"); } });
    if (dd) { const open = dd.parentElement.classList.toggle("is-open"); dd.setAttribute("aria-expanded", String(open)); }
  });
  document.addEventListener("keydown", (e) => { if (e.key === "Escape") { $$(".dropdown.is-open").forEach((d) => d.classList.remove("is-open")); document.body.classList.remove("sidebar-open"); } });

  /* ------------------------------------------------------------ section editor */
  document.addEventListener("click", (e) => {
    const head = e.target.closest("[data-sec-toggle]");
    if (head && !e.target.closest("button, label, input, a")) head.closest(".sec-card").classList.toggle("is-open");
  });
  document.addEventListener("change", async (e) => {
    const cb = e.target.closest("[data-sec-visible]");
    if (!cb) return;
    const card = cb.closest(".sec-card"), wrap = cb.closest("[data-sections]");
    card.classList.toggle("is-off", !cb.checked);
    const data = await api("admin/actions/page-save.php", { op: "toggle", page: wrap.dataset.page, section: card.dataset.key, visible: cb.checked ? "1" : "0" });
    if (data.ok) toast(data.message); else { cb.checked = !cb.checked; card.classList.toggle("is-off", !cb.checked); toast(data.message, "error"); }
  });
  function refreshSectionArrows() {
    $$("[data-sections]").forEach((wrap) => {
      const cards = $$(":scope > .sec-card", wrap);
      cards.forEach((c, i) => { c.querySelector('[data-sec-move="-1"]').disabled = i === 0; c.querySelector('[data-sec-move="1"]').disabled = i === cards.length - 1; });
    });
  }
  refreshSectionArrows();
  document.addEventListener("click", async (e) => {
    const b = e.target.closest("[data-sec-move]");
    if (!b) return;
    const card = b.closest(".sec-card"), wrap = card.parentElement;
    const sib = b.dataset.secMove === "-1" ? card.previousElementSibling : card.nextElementSibling;
    if (!sib) return;
    b.dataset.secMove === "-1" ? wrap.insertBefore(card, sib) : wrap.insertBefore(sib, card);
    refreshSectionArrows();
    const data = await api("admin/actions/page-save.php", { op: "order", page: wrap.dataset.page, keys: $$(":scope > .sec-card", wrap).map((c) => c.dataset.key) });
    toast(data.message, data.ok ? "success" : "error");
  });

  /* ------------------------------------------------------------ repeaters */
  let repSeq = 0;
  function renumber(rep) {
    const items = $$(":scope > .repeater__list > .rep-item", rep);
    items.forEach((it, i) => {
      it.querySelector(".rep-item__num").textContent = i + 1;
      it.querySelector("[data-rep-up]").disabled = i === 0;
      it.querySelector("[data-rep-down]").disabled = i === items.length - 1;
    });
    const add = rep.querySelector("[data-rep-add]");
    if (add) add.disabled = items.length >= (+rep.dataset.max || 99);
  }
  $$("[data-repeater]").forEach(renumber);
  document.addEventListener("click", (e) => {
    const rep = e.target.closest("[data-repeater]");
    if (!rep) return;
    const list = rep.querySelector(".repeater__list");
    const item = e.target.closest(".rep-item");
    if (e.target.closest("[data-rep-add]")) {
      const tpl = rep.querySelector(":scope > template").innerHTML.replaceAll("__INDEX__", "n" + Date.now().toString(36) + repSeq++);
      list.insertAdjacentHTML("beforeend", tpl);
      renumber(rep);
      list.lastElementChild.querySelector("input:not([type=hidden]), textarea")?.focus();
      markDirty(rep.closest("form"));
    } else if (e.target.closest("[data-rep-remove]")) {
      item.remove(); renumber(rep); markDirty(rep.closest("form"));
    } else if (e.target.closest("[data-rep-up]") && item.previousElementSibling) {
      list.insertBefore(item, item.previousElementSibling); renumber(rep); markDirty(rep.closest("form"));
    } else if (e.target.closest("[data-rep-down]") && item.nextElementSibling) {
      list.insertBefore(item.nextElementSibling, item); renumber(rep); markDirty(rep.closest("form"));
    } else if (e.target.closest("[data-rep-toggle]") && !e.target.closest("button")) {
      item.classList.toggle("is-collapsed");
    }
  });
  document.addEventListener("input", (e) => {
    const item = e.target.closest(".rep-item");
    const rep = item?.closest("[data-repeater]");
    if (!rep || !e.target.name?.endsWith(`[${rep.dataset.labelField}]`)) return;
    item.querySelector(".rep-item__head strong").textContent = e.target.value.trim() || "Item";
  });

  /* ------------------------------------------------------------ image & icon fields */
  function setImage(field, path) {
    const input = field.querySelector('input[type="text"]');
    input.value = path || "";
    const prev = field.querySelector(".img-field__preview");
    prev.innerHTML = path ? `<img src="${esc(urlFor(path))}" alt="">` : '<i class="fa-regular fa-image"></i>';
    field.querySelector("[data-img-remove]").hidden = !path;
    markDirty(field.closest("form"));
  }
  document.addEventListener("click", async (e) => {
    const field = e.target.closest("[data-image-field]");
    if (!field) return;
    if (e.target.closest("[data-img-upload]")) field.querySelector('input[type="file"]').click();
    if (e.target.closest("[data-img-remove]")) setImage(field, "");
    if (e.target.closest("[data-img-library]")) {
      const path = await window.DSMedia.openPicker({ folder: field.dataset.folder });
      if (path) setImage(field, path);
    }
  });
  document.addEventListener("change", async (e) => {
    const field = e.target.closest("[data-image-field]");
    if (!field) return;
    if (e.target.type === "file" && e.target.files[0]) {
      const bar = field.querySelector(".progress"); bar.hidden = false;
      const r = await window.DSMedia.upload(e.target.files[0], field.dataset.folder, (p) => (bar.firstElementChild.style.width = p * 100 + "%"));
      bar.hidden = true; bar.firstElementChild.style.width = "0"; e.target.value = "";
      if (r.ok && r.items?.[0]) { setImage(field, r.items[0].path); toast("Image uploaded — remember to save."); } else toast(r.message || "Upload failed.", "error");
    } else if (e.target.type === "text") setImage(field, e.target.value.trim());
  });
  document.addEventListener("click", (e) => {
    const pick = e.target.closest("[data-icon-field] [data-icon]");
    if (!pick) return;
    const wrap = pick.closest("[data-icon-field]"), input = wrap.querySelector("input");
    input.value = pick.dataset.icon; input.dispatchEvent(new Event("input", { bubbles: true }));
  });
  document.addEventListener("input", (e) => {
    const wrap = e.target.closest("[data-icon-field]");
    if (!wrap || e.target.tagName !== "INPUT") return;
    const ok = /^fa-(solid|regular|brands)( fa-[a-z0-9-]+)+$/.test(e.target.value.trim());
    wrap.querySelector(".icon-field__preview i").className = ok ? e.target.value.trim() : "fa-solid fa-star";
    $$("[data-icon]", wrap).forEach((b) => b.classList.toggle("is-active", b.dataset.icon === e.target.value.trim()));
  });
  $$("[data-icon-field] input").forEach((i) => i.dispatchEvent(new Event("input", { bubbles: true })));
  $$("form[data-track-dirty]").forEach(markClean);

  /* character counters */
  $$("textarea[data-count][maxlength]").forEach((ta) => {
    const c = document.createElement("div"); c.className = "char-count";
    const upd = () => (c.textContent = `${ta.value.length} / ${ta.maxLength}`);
    ta.after(c); ta.addEventListener("input", upd); upd();
  });

  /* ------------------------------------------------------------ collections */
  document.addEventListener("change", async (e) => {
    const sw = e.target.closest("[data-publish]");
    if (!sw) return;
    const data = await api("admin/actions/collection-order.php", { op: "toggle", collection: sw.dataset.publish, id: sw.dataset.id, published: sw.checked ? "1" : "0" });
    if (data.ok) { toast(data.message); sw.closest("label").querySelector("[data-publish-label]").textContent = sw.checked ? "Live" : "Hidden"; }
    else { sw.checked = !sw.checked; toast(data.message, "error"); }
  });
  $$("tbody[data-sortable]").forEach((tbody) => {
    if (!tbody.dataset.sortable) return;
    let drag = null;
    tbody.addEventListener("dragstart", (e) => { drag = e.target.closest("tr"); drag.classList.add("is-dragging"); e.dataTransfer.effectAllowed = "move"; });
    tbody.addEventListener("dragover", (e) => {
      e.preventDefault();
      const over = e.target.closest("tr");
      if (!over || over === drag) return;
      const r = over.getBoundingClientRect();
      tbody.insertBefore(drag, e.clientY > r.top + r.height / 2 ? over.nextSibling : over);
    });
    tbody.addEventListener("dragend", async () => {
      drag?.classList.remove("is-dragging");
      drag = null;
      const data = await api("admin/actions/collection-order.php", { op: "reorder", collection: tbody.dataset.sortable, ids: $$("tr[data-id]", tbody).map((r) => r.dataset.id) });
      toast(data.message, data.ok ? "success" : "error");
    });
  });

  /* ------------------------------------------------------------ enquiries */
  const STATUS_LABELS = { new: "New", contacted: "Contacted", in_progress: "In Progress", converted: "Converted", closed: "Closed" };
  function openEnquiry(row) {
    const d = JSON.parse(row.dataset.enquiry);
    const body = $("#enquiryTemplate").content.cloneNode(true).firstElementChild;
    const q = (k) => body.querySelector(`[data-e="${k}"]`);
    q("status-badge").className = `badge badge--${d.status}`; q("status-badge").textContent = STATUS_LABELS[d.status] || d.status;
    q("received").textContent = "Received " + d.received; q("enquiry_id").textContent = d.enquiry_id;
    q("email").textContent = d.email; q("email").href = "mailto:" + d.email + "?subject=" + encodeURIComponent("Re: " + (d.subject || "Your enquiry"));
    q("phone").textContent = d.phone || "—"; if (d.tel) q("phone").href = d.tel;
    ["company", "industry", "service", "subject"].forEach((k) => (q(k).textContent = d[k] || "—"));
    q("message").textContent = d.message;
    q("id-input").value = d.id; q("status-select").value = d.status; q("notes").value = d.admin_notes || "";
    const foot = document.createElement("div");
    foot.className = "modal__foot"; foot.style.margin = "18px -22px -22px";
    foot.innerHTML = (d.whatsapp ? `<a class="btn btn--ghost" target="_blank" rel="noopener" href="${esc(d.whatsapp)}"><i class="fa-brands fa-whatsapp"></i>WhatsApp</a>` : "")
      + `<a class="btn btn--gold" href="mailto:${esc(d.email)}?subject=${encodeURIComponent("Re: " + (d.subject || "Your enquiry"))}&body=${encodeURIComponent("Hi " + d.name + ",\n\nThank you for contacting us.\n\n")}"><i class="fa-solid fa-reply"></i>Reply by email</a>`;
    body.append(foot);
    openModal({ title: `Enquiry from ${d.name}`, body, width: 760 });
    // Opening a new enquiry marks it as Contacted only when the admin replies — never silently.
  }
  document.addEventListener("click", (e) => {
    const row = e.target.closest("tr[data-enquiry]");
    if (!row || e.target.closest("select, form, a") && !e.target.closest("[data-enquiry-open]")) return;
    if (e.target.closest("button") && !e.target.closest("[data-enquiry-open]")) return;
    openEnquiry(row);
  });
  const auto = $("tr[data-autoopen]"); if (auto) openEnquiry(auto);

  /* ------------------------------------------------------------ administrators */
  document.addEventListener("click", (e) => {
    const b = e.target.closest('[data-modal-open="adminUserModal"]');
    if (!b) return;
    const tpl = $("#adminUserModal");
    const form = tpl.content.cloneNode(true).firstElementChild;
    const u = b.dataset.adminUser ? JSON.parse(b.dataset.adminUser) : null;
    if (u) {
      form.id.value = u.id; form.name.value = u.name; form.email.value = u.email; form.role.value = u.role; form.status.value = u.status;
      form.querySelector("[data-pw-hint]").textContent = "(leave empty to keep)";
    }
    openModal({ title: u ? `Edit ${u.name}` : "Add administrator", body: form, width: 640 });
  });

  /* ------------------------------------------------------------ notifications */
  async function pollNotifications() {
    try {
      const res = await fetch(BASE + "admin/actions/notifications.php", { credentials: "same-origin", headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" } });
      if (!res.ok) return;
      const d = await res.json();
      $$("[data-new-count]").forEach((el) => { el.hidden = !d.count; el.textContent = d.count; });
      const list = $("[data-notif-list]");
      if (list) list.innerHTML = d.items.length
        ? d.items.map((n) => `<a class="notif" href="${esc(BASE)}admin/enquiries.php?view=${n.id}"><strong>${esc(n.title)}</strong><small>${esc(n.text)}</small></a>`).join("")
        : '<p class="notif-empty">You\'re all caught up ✨</p>';
    } catch (e) { /* offline: try again later */ }
  }
  setInterval(pollNotifications, 60000);

  window.DS = { toast, openModal, confirmDialog, api, BASE, urlFor, esc };
})();

/* =============================================================================
   Dreamm Framme additions: video fields · multi-image fields · album photo manager
   ============================================================================= */
(function () {
  "use strict";
  const $ = (s, r = document) => r.querySelector(s);
  const $$ = (s, r = document) => [...r.querySelectorAll(s)];
  const DS = () => window.DS;
  const dirty = (el) => { const f = el.closest("form[data-track-dirty]"); if (f) { f.dataset.dirty = "1"; f.classList.add("is-dirty"); } };
  const VIDEO_RE = /\.(mp4|m4v|webm)$/i;

  /* ---------- video fields (hero slider) ---------- */
  function setVideo(field, path) {
    field.querySelector('input[type="text"]').value = path || "";
    const prev = field.querySelector(".img-field__preview");
    prev.innerHTML = path ? `<video src="${DS().esc(DS().urlFor(path))}" muted playsinline preload="metadata" controls></video>` : '<i class="fa-solid fa-film"></i>';
    field.querySelector("[data-video-remove]").hidden = !path;
    dirty(field);
  }
  document.addEventListener("click", (e) => {
    const field = e.target.closest("[data-video-field]");
    if (!field) return;
    if (e.target.closest("[data-video-upload]")) field.querySelector('input[type="file"]').click();
    if (e.target.closest("[data-video-remove]")) setVideo(field, "");
  });
  document.addEventListener("change", async (e) => {
    const field = e.target.closest("[data-video-field]");
    if (!field || e.target.type !== "file" || !e.target.files[0]) return;
    const file = e.target.files[0];
    const bar = field.querySelector(".progress"); bar.hidden = false;
    const btn = field.querySelector("[data-video-upload]"); btn.disabled = true; btn.classList.add("is-loading");
    const r = await window.DSMedia.upload(file, "videos", (p) => (bar.firstElementChild.style.width = p * 100 + "%"),
      { url: "admin/actions/video-upload.php", accept: VIDEO_RE, acceptMessage: `${file.name}: only MP4 or WebM videos are allowed.` });
    bar.hidden = true; bar.firstElementChild.style.width = "0"; e.target.value = ""; btn.disabled = false; btn.classList.remove("is-loading");
    if (r.ok && r.items?.[0]) { setVideo(field, r.items[0].path); DS().toast(r.message); } else DS().toast(r.message || "Upload failed.", "error");
  });
  document.addEventListener("change", (e) => {
    const field = e.target.closest("[data-video-field]");
    if (field && e.target.type === "text") setVideo(field, e.target.value.trim());
  });

  /* ---------- multi-image fields (service galleries, social grid) ---------- */
  function listItem(list, path) {
    const d = document.createElement("div");
    d.className = "img-list__item"; d.draggable = true;
    d.innerHTML = `<img src="${DS().esc(DS().urlFor(path))}" alt=""><input type="hidden" name="${DS().esc(list.dataset.name)}[]" value="${DS().esc(path)}"><button type="button" data-list-remove aria-label="Remove photo"><i class="fa-solid fa-xmark"></i></button>`;
    return d;
  }
  function addToList(list, path) {
    const items = list.querySelector(".img-list__items");
    const max = Number(list.dataset.max || 12);
    if ($$('input[type="hidden"]', items).some((i) => i.value === path)) return true;
    if (items.children.length >= max) { DS().toast(`Maximum ${max} photos in this gallery.`, "error"); return false; }
    items.append(listItem(list, path)); dirty(list); return true;
  }
  document.addEventListener("click", async (e) => {
    const list = e.target.closest("[data-image-list]");
    if (!list) return;
    if (e.target.closest("[data-list-remove]")) { e.target.closest(".img-list__item").remove(); dirty(list); }
    if (e.target.closest("[data-list-upload]")) list.querySelector('input[type="file"]').click();
    if (e.target.closest("[data-list-library]")) { const p = await window.DSMedia.openPicker({ folder: list.dataset.folder }); if (p) addToList(list, p); }
  });
  document.addEventListener("change", async (e) => {
    const list = e.target.closest("[data-image-list]");
    if (!list || e.target.type !== "file") return;
    const files = [...e.target.files]; e.target.value = "";
    const bar = list.querySelector(".progress"); bar.hidden = false;
    let done = 0, ok = 0;
    for (const f of files) {
      const r = await window.DSMedia.upload(f, list.dataset.folder, (p) => (bar.firstElementChild.style.width = ((done + p) / files.length) * 100 + "%"));
      done++;
      if (r.ok && r.items?.[0]) { if (!addToList(list, r.items[0].path)) break; ok++; } else DS().toast(r.message || "Upload failed.", "error");
    }
    bar.hidden = true; bar.firstElementChild.style.width = "0";
    if (ok) DS().toast(`${ok} photo${ok > 1 ? "s" : ""} added — remember to save.`);
  });
  let listDrag = null;
  document.addEventListener("dragstart", (e) => { const it = e.target.closest?.(".img-list__item"); if (it) { listDrag = it; it.classList.add("is-dragging"); } });
  document.addEventListener("dragover", (e) => {
    if (!listDrag) return;
    const over = e.target.closest?.(".img-list__item");
    if (!over || over === listDrag || over.parentElement !== listDrag.parentElement) return;
    e.preventDefault();
    const r = over.getBoundingClientRect();
    over.parentElement.insertBefore(listDrag, e.clientX > r.left + r.width / 2 ? over.nextSibling : over);
  });
  document.addEventListener("dragend", () => { if (listDrag) { listDrag.classList.remove("is-dragging"); dirty(listDrag); listDrag = null; } });

  /* ---------- album photo manager ---------- */
  const grid = $("[data-photo-grid]");
  const drop = $("[data-photo-drop]");
  if (!grid && !drop) return;
  const ACTION = "admin/actions/album-photo.php";

  async function uploadPhotos(files) {
    files = files.filter((f) => /\.(jpe?g|png|webp)$/i.test(f.name));
    if (!files.length) { DS().toast("Choose JPG, PNG or WebP photos.", "error"); return; }
    if (files.length > 50) { DS().toast("Please upload at most 50 photos at a time.", "error"); return; }
    const bar = $("[data-photo-progress]", drop), status = $("[data-photo-status]", drop);
    bar.hidden = false; status.hidden = false;
    let ok = 0; const errors = [];
    for (let i = 0; i < files.length; i++) {
      status.textContent = `Uploading ${i + 1} of ${files.length}: ${files[i].name}`;
      const r = await window.DSMedia.upload(files[i], "albums", (p) => (bar.firstElementChild.style.width = ((i + p) / files.length) * 100 + "%"),
        { url: ACTION, data: { op: "upload", album_id: drop.dataset.album } });
      if (r.ok) ok++; else errors.push(r.message || files[i].name);
    }
    bar.firstElementChild.style.width = "100%";
    status.textContent = `${ok} of ${files.length} uploaded.` + (errors.length ? " Skipped: " + errors.join(" · ") : "");
    if (ok) { DS().toast(`${ok} photo${ok > 1 ? "s" : ""} uploaded.`); setTimeout(() => location.reload(), errors.length ? 2500 : 700); }
    else DS().toast(errors[0] || "Upload failed.", "error");
  }
  if (drop) {
    const input = $("[data-photo-input]", drop);
    $("[data-photo-pick]", drop).addEventListener("click", () => input.click());
    input.addEventListener("change", () => { uploadPhotos([...input.files]); input.value = ""; });
    ["dragenter", "dragover"].forEach((ev) => drop.addEventListener(ev, (e) => { if (e.dataTransfer?.types?.includes("Files")) { e.preventDefault(); drop.classList.add("is-over"); } }));
    ["dragleave", "drop"].forEach((ev) => drop.addEventListener(ev, () => drop.classList.remove("is-over")));
    drop.addEventListener("drop", (e) => { if (e.dataTransfer?.files?.length) { e.preventDefault(); uploadPhotos([...e.dataTransfer.files]); } });
  }
  if (!grid) return;

  const checks = () => $$("[data-photo-check]", grid);
  const selected = () => checks().filter((c) => c.checked).map((c) => c.value);
  const syncBulk = () => {
    const n = selected().length;
    $$("[data-photo-bulk]").forEach((b) => (b.disabled = !n));
    $$("[data-photo-bulk]").forEach((b) => { const lbl = b.dataset.photoBulk === "bulk_delete" ? "Delete selected" : "Move"; b.lastChild.textContent = n ? `${lbl} (${n})` : lbl; });
    checks().forEach((c) => c.closest(".photo-item").classList.toggle("is-selected", c.checked));
  };
  grid.addEventListener("change", (e) => { if (e.target.matches("[data-photo-check]")) syncBulk(); });
  $("[data-photo-all]")?.addEventListener("change", (e) => { checks().forEach((c) => (c.checked = e.target.checked)); syncBulk(); });
  $$("[data-photo-bulk]").forEach((b) => b.addEventListener("click", async () => {
    const ids = selected(); if (!ids.length) return;
    const op = b.dataset.photoBulk, data = { op, ids };
    if (op === "bulk_move") {
      const t = $("[data-photo-target]").value;
      if (!t) { DS().toast("Choose the album to move the photos to.", "error"); return; }
      data.target_album = t;
    } else if (!(await DS().confirmDialog(`Delete ${ids.length} photo(s)? The files are removed from the server.`, { danger: true }))) return;
    const r = await DS().api(ACTION, data);
    DS().toast(r.message, r.ok ? "success" : "error");
    if (r.ok) setTimeout(() => location.reload(), 600);
  }));

  grid.addEventListener("click", async (e) => {
    const item = e.target.closest(".photo-item"); if (!item) return;
    const id = item.dataset.id;
    if (e.target.closest("[data-photo-delete]")) {
      if (!(await DS().confirmDialog("Delete this photo? The file is removed from the server.", { danger: true }))) return;
      const r = await DS().api(ACTION, { op: "delete", id });
      DS().toast(r.message, r.ok ? "success" : "error");
      if (r.ok) { item.style.opacity = "0"; setTimeout(() => item.remove(), 300); }
    }
    if (e.target.closest("[data-photo-cover]")) {
      const r = await DS().api(ACTION, { op: "cover", id });
      DS().toast(r.message, r.ok ? "success" : "error");
      if (r.ok) setTimeout(() => location.reload(), 500);
    }
    if (e.target.closest("[data-photo-replace]")) {
      const inp = $("[data-photo-replace-input]");
      inp.dataset.id = id; inp.click();
    }
  });
  $("[data-photo-replace-input]")?.addEventListener("change", async (e) => {
    const f = e.target.files[0]; if (!f) return;
    const r = await window.DSMedia.upload(f, "albums", null, { url: ACTION, data: { op: "replace", id: e.target.dataset.id } });
    e.target.value = "";
    DS().toast(r.message, r.ok ? "success" : "error");
    if (r.ok) setTimeout(() => location.reload(), 500);
  });

  if (grid.dataset.album !== "0") {
    let drag = null;
    grid.addEventListener("dragstart", (e) => { drag = e.target.closest(".photo-item"); drag?.classList.add("is-dragging"); });
    grid.addEventListener("dragover", (e) => {
      if (!drag) return;
      e.preventDefault();
      const over = e.target.closest(".photo-item");
      if (!over || over === drag) return;
      const r = over.getBoundingClientRect();
      grid.insertBefore(drag, (e.clientX > r.left + r.width / 2) ? over.nextSibling : over);
    });
    grid.addEventListener("dragend", async () => {
      if (!drag) return;
      drag.classList.remove("is-dragging"); drag = null;
      const r = await DS().api(ACTION, { op: "reorder", album_id: grid.dataset.album, ids: $$(".photo-item", grid).map((x) => x.dataset.id) });
      DS().toast(r.message, r.ok ? "success" : "error");
    });
  }
})();
