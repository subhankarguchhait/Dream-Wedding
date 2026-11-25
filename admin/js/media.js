/**
 * Dreamm Framme Event CMS — media
 * DSMedia.upload()     upload with progress (XMLHttpRequest) → admin/actions/media-upload.php
 * DSMedia.openPicker() choose an image from the library (or upload a new one) for image fields
 * Media Library page   drag & drop multi-upload, details (rename / alt text / folder), replace, delete, copy URL
 */
(function () {
  "use strict";
  const BASE = document.querySelector('meta[name="base-url"]')?.content || "/";
  const CSRF = () => document.querySelector('meta[name="csrf-token"]')?.content || "";
  const DS = () => window.DS;
  const ACCEPT = /\.(jpe?g|png|webp)$/i;

  function upload(file, folder, onProgress, extra = {}) {
    return new Promise((resolve) => {
      if (!(extra.accept || ACCEPT).test(file.name)) { resolve({ ok: false, message: extra.acceptMessage || `${file.name}: only JPG, PNG or WebP images are allowed.` }); return; }
      const fd = new FormData();
      fd.append(extra.field || "file", file);
      fd.append("folder", folder || "general");
      fd.append("csrf_token", CSRF());
      Object.entries(extra.data || {}).forEach(([k, v]) => fd.append(k, v));
      const xhr = new XMLHttpRequest();
      xhr.open("POST", BASE + (extra.url || "admin/actions/media-upload.php"));
      xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
      xhr.setRequestHeader("Accept", "application/json");
      xhr.upload.onprogress = (e) => e.lengthComputable && onProgress?.(e.loaded / e.total);
      xhr.onload = () => { try { resolve(JSON.parse(xhr.responseText)); } catch { resolve({ ok: false, message: `Upload failed (server returned ${xhr.status}).` }); } };
      xhr.onerror = () => resolve({ ok: false, message: "Network error during upload." });
      xhr.send(fd);
    });
  }

  function openPicker({ folder = "general" } = {}) {
    return new Promise((resolve) => {
      const { openModal, toast, esc } = DS();
      let chosen = null, items = [], filter = "all";
      const body = document.createElement("div");
      body.style.cssText = "display:grid;gap:14px";
      body.innerHTML = `<div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between">
          <div class="chips-filter" data-chips></div>
          <button type="button" class="btn btn--forest btn--sm" data-up><i class="fa-solid fa-cloud-arrow-up"></i>Upload to “${esc(folder)}”</button></div>
        <div class="progress" hidden><span></span></div><input type="file" multiple accept=".jpg,.jpeg,.png,.webp,.svg" hidden>
        <div class="media-grid" style="padding:0;max-height:52vh;overflow:auto" data-grid><p class="empty"><span class="spinner"></span></p></div>
        <div class="modal__foot" style="margin:4px -22px -22px"><span class="spacer f__help">Tip: double-click an image to use it straight away.</span><button type="button" class="btn btn--ghost" data-modal-close>Cancel</button><button type="button" class="btn btn--gold" data-use disabled><i class="fa-solid fa-check"></i>Use selected image</button></div>`;
      const grid = body.querySelector("[data-grid]"), chips = body.querySelector("[data-chips]"), file = body.querySelector("input[type=file]"), bar = body.querySelector(".progress"), use = body.querySelector("[data-use]");
      let done = false;
      const m = openModal({ title: "Choose an image", body, width: 940, onClose: () => resolve(done ? chosen : null) });
      const finish = (p) => { chosen = p; done = true; m.close(); };
      const draw = () => {
        const folders = ["all", ...new Set(items.map((i) => i.folder))];
        chips.innerHTML = folders.map((f) => `<button type="button" data-f="${esc(f)}" class="${f === filter ? "is-active" : ""}">${f === "all" ? "All" : esc(f)}</button>`).join("");
        const list = items.filter((i) => filter === "all" || i.folder === filter);
        grid.innerHTML = list.length ? list.map((i) => `<button type="button" class="media-item${chosen === i.path ? " is-picked" : ""}" data-path="${esc(i.path)}" title="${esc(i.name)}"><img src="${esc(i.url)}" alt="" loading="lazy"><span class="media-item__meta"><strong>${esc(i.name)}</strong><small>${esc(i.date)}</small></span></button>`).join("")
          : '<div class="empty" style="grid-column:1/-1"><i class="fa-regular fa-images"></i><h3>No images yet</h3>Upload one with the button above.</div>';
      };
      const load = async () => {
        const res = await fetch(BASE + "admin/actions/media-list.php", { credentials: "same-origin", headers: { Accept: "application/json", "X-Requested-With": "XMLHttpRequest" } }).catch(() => null);
        const d = res ? await res.json().catch(() => null) : null;
        if (!d?.ok) { grid.innerHTML = '<div class="alert alert--error" style="grid-column:1/-1">Could not load the library.</div>'; return; }
        items = d.items; draw();
      };
      chips.addEventListener("click", (e) => { const b = e.target.closest("[data-f]"); if (b) { filter = b.dataset.f; draw(); } });
      grid.addEventListener("click", (e) => { const b = e.target.closest("[data-path]"); if (b) { chosen = b.dataset.path; use.disabled = false; draw(); } });
      grid.addEventListener("dblclick", (e) => { const b = e.target.closest("[data-path]"); if (b) finish(b.dataset.path); });
      use.addEventListener("click", () => finish(chosen));
      body.querySelector("[data-up]").addEventListener("click", () => file.click());
      file.addEventListener("change", async () => {
        const files = [...file.files]; file.value = ""; bar.hidden = false;
        for (const [i, f] of files.entries()) {
          const r = await upload(f, folder, (p) => (bar.firstElementChild.style.width = ((i + p) / files.length) * 100 + "%"));
          if (r.ok) chosen = r.items[0].path; else toast(r.message, "error");
        }
        bar.hidden = true; use.disabled = !chosen; await load();
      });
      load();
    });
  }

  window.DSMedia = { upload, openPicker };

  /* ------------------------------------------------------------ Media Library page */
  document.addEventListener("DOMContentLoaded", () => {
    const dz = document.querySelector("[data-dropzone]");
    if (!dz) return;
    const { toast, openModal, confirmDialog, api, esc } = DS();
    const input = document.querySelector("[data-dropzone-input]");
    const list = document.querySelector("[data-upload-list]");
    const folderSel = document.getElementById("uploadFolder");
    const fmtSize = (n) => (n < 1048576 ? Math.round(n / 1024) + " KB" : (n / 1048576).toFixed(1) + " MB");

    async function handle(files) {
      if (!files.length) return;
      let ok = 0;
      for (const f of files) {
        const row = document.createElement("div");
        row.className = "upload-row";
        row.innerHTML = `<span>${esc(f.name)} <span class="muted">${fmtSize(f.size)}</span></span><div class="progress"><span></span></div>`;
        list.prepend(row);
        const r = await upload(f, folderSel.value, (p) => (row.querySelector(".progress span").style.width = p * 100 + "%"));
        if (r.ok) { ok++; row.querySelector(".progress span").style.width = "100%"; }
        else row.innerHTML = `<span style="color:var(--err)">${esc(r.message)}</span>`;
      }
      if (ok) { toast(`${ok} image${ok > 1 ? "s" : ""} uploaded`); setTimeout(() => location.reload(), 900); }
    }
    dz.addEventListener("click", () => input.click());
    dz.addEventListener("keydown", (e) => { if (e.key === "Enter" || e.key === " ") { e.preventDefault(); input.click(); } });
    ["dragenter", "dragover"].forEach((ev) => dz.addEventListener(ev, (e) => { e.preventDefault(); dz.classList.add("is-drag"); }));
    ["dragleave", "drop"].forEach((ev) => dz.addEventListener(ev, (e) => { e.preventDefault(); dz.classList.remove("is-drag"); }));
    dz.addEventListener("drop", (e) => handle([...e.dataTransfer.files]));
    input.addEventListener("change", () => { handle([...input.files]); input.value = ""; });

    document.addEventListener("click", (e) => {
      const item = e.target.closest("[data-media]");
      if (!item) return;
      const d = JSON.parse(item.dataset.media);
      const folders = [...document.getElementById("uploadFolder").options].map((o) => o.value);
      const body = document.createElement("div");
      body.innerHTML = `<div class="media-preview">
          <div class="media-preview__img"><img src="${esc(d.url)}" alt="${esc(d.alt)}"></div>
          <form class="grid" style="gap:14px;align-content:start" data-details>
            <div class="f"><label>Display name</label><input type="text" name="name" maxlength="190" value="${esc(d.name)}"></div>
            <div class="f"><label>Alt text <span class="f__help">(describes the image for accessibility & SEO)</span></label><input type="text" name="alt" maxlength="255" value="${esc(d.alt)}"></div>
            <div class="f"><label>Folder</label><select name="folder">${folders.map((f) => `<option${f === d.folder ? " selected" : ""}>${esc(f)}</option>`).join("")}</select></div>
            <div class="f"><label>Image path</label><div style="display:flex;gap:6px"><input type="text" readonly value="${esc(d.path)}"><button type="button" class="btn btn--ghost btn--sm" data-copy><i class="fa-regular fa-copy"></i>Copy URL</button></div></div>
            <dl class="kv"><div><dt>File</dt><dd>${esc(d.file)}</dd></div><div><dt>Size</dt><dd>${fmtSize(d.size)}${d.width ? ` · ${d.width}×${d.height}px` : ""}</dd></div><div><dt>Type</dt><dd>${esc(d.type)}</dd></div><div><dt>Uploaded</dt><dd>${esc(d.uploaded)}</dd></div><div><dt>Used in</dt><dd>${d.used ? d.used + " place" + (d.used > 1 ? "s" : "") : "Not used yet"}</dd></div></dl>
            <div class="progress" hidden><span></span></div><input type="file" accept=".jpg,.jpeg,.png,.webp,.svg" hidden>
            <div class="alert alert--info"><i class="fa-solid fa-circle-info"></i>Replace keeps every page, brand and setting that uses this image pointing at the new file.</div>
          </form></div>
        <div class="modal__foot" style="margin:18px -22px -22px"><button type="button" class="btn btn--danger" data-del><i class="fa-solid fa-trash-can"></i>Delete</button><span class="spacer"></span><button type="button" class="btn btn--ghost" data-replace><i class="fa-solid fa-arrows-rotate"></i>Replace image</button><button type="button" class="btn btn--gold" data-save><i class="fa-solid fa-floppy-disk"></i>Save details</button></div>`;
      const m = openModal({ title: "Image details", body, width: 980 });
      const form = body.querySelector("[data-details]"), file = form.querySelector("input[type=file]"), bar = form.querySelector(".progress");
      body.querySelector("[data-copy]").onclick = async () => { try { await navigator.clipboard.writeText(new URL(d.url, location.href).href); toast("URL copied"); } catch { toast("Select the path and press Ctrl/Cmd + C", "info"); } };
      body.querySelector("[data-save]").onclick = async () => {
        const r = await api("admin/actions/media-update.php", { op: "details", id: d.id, name: form.name.value, alt: form.alt.value, folder: form.folder.value });
        toast(r.message, r.ok ? "success" : "error"); if (r.ok) setTimeout(() => location.reload(), 600);
      };
      body.querySelector("[data-replace]").onclick = () => file.click();
      file.onchange = async () => {
        const f = file.files[0]; file.value = "";
        if (!f || !(await confirmDialog(`Replace “${d.name}” with “${f.name}” everywhere it is used?`, { confirmText: "Replace" }))) return;
        bar.hidden = false;
        const r = await upload(f, d.folder, (p) => (bar.firstElementChild.style.width = p * 100 + "%"), { url: "admin/actions/media-update.php", data: { op: "replace", id: d.id } });
        bar.hidden = true;
        toast(r.message, r.ok ? "success" : "error"); if (r.ok) setTimeout(() => location.reload(), 700);
      };
      body.querySelector("[data-del]").onclick = async () => {
        const warn = d.used ? ` It is used in ${d.used} place${d.used > 1 ? "s" : ""}, which will show no image until you choose another.` : "";
        if (!(await confirmDialog(`Permanently delete “${d.name}”?${warn}`, { danger: true }))) return;
        const r = await api("admin/actions/media-delete.php", { id: d.id });
        toast(r.message, r.ok ? "success" : "error"); if (r.ok) { m.close(); item.remove(); }
      };
    });
  });
})();
