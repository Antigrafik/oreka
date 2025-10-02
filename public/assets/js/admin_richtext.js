/* /assets/js/admin_richtext.js */
(function (global) {
  // Selección dentro de un root concreto
  function $(root, sel) {
    const r = typeof root === "string" ? document.querySelector(root) : root;
    return (r ? r.querySelector(sel) : null) || null;
  }

  function makeSingleLineEditable(el) {
    if (!el) return;
    el.addEventListener("keydown", (ev) => {
      if (ev.key === "Enter") ev.preventDefault();
    });
    el.addEventListener("paste", (ev) => {
      ev.preventDefault();
      const text = (ev.clipboardData || window.clipboardData)
        .getData("text")
        .replace(/\s+/g, " ");
      document.execCommand("insertText", false, text);
    });
  }

  function inside(el, range) {
    if (!el || !range) return false;
    const c = range.commonAncestorContainer;
    return el.contains(c.nodeType === 1 ? c : c.parentNode);
  }

  function initRichEditor(cfg) {
    const root = typeof cfg.root === "string" ? document.querySelector(cfg.root) : cfg.root || document;
    const langs = cfg.langs || ["es", "eu"];

    // --- elementos SIEMPRE relativos al root ---
    const toolbar = $(root, cfg.toolbarSelector || ".toolbar");

    const tabBtns = (root || document).querySelectorAll(
      cfg.tabBtnSelector || ".tab-btn"
    );
    const tabs = {
      es: $(root, (cfg.tabs && cfg.tabs.es) || ".tab-es"),
      eu: $(root, (cfg.tabs && cfg.tabs.eu) || ".tab-eu"),
    };

    // Título
    const tEsInput = $(root, cfg.title?.esInput || null);
    const tEuInput = $(root, cfg.title?.euInput || null);
    const tEsCtt = $(root, cfg.title?.esCtt || null);
    const tEuCtt = $(root, cfg.title?.euCtt || null);
    const singleLine = !!cfg.title?.singleLine;

    // Contenido
    const cEsCtt = $(root, cfg.content?.esCtt || "#ed-es");
    const cEuCtt = $(root, cfg.content?.euCtt || "#ed-eu");
    const cEsField = $(root, cfg.content?.esField || "#tx-es");
    const cEuField = $(root, cfg.content?.euField || "#tx-eu");

    // Form del propio root
    const form =
      (root && root.closest && root.closest("form")) ||
      $(root, "form") ||
      null;

    // Para que el formato se quede
    try {
      document.execCommand("styleWithCSS", false, true);
    } catch (_) {}

    // --- selección global (por vista/root) ---
    let activeEditable = null;
    let lastRange = null;

    function saveSelection() {
      const sel = window.getSelection?.();
      if (!sel || sel.rangeCount === 0) return;
      const r = sel.getRangeAt(0);
      if (!activeEditable || inside(activeEditable, r)) {
        lastRange = r.cloneRange();
      }
    }
    function restoreSelection() {
      if (!lastRange) return false;
      const sel = window.getSelection?.();
      if (!sel) return false;
      sel.removeAllRanges();
      sel.addRange(lastRange);
      return true;
    }
    function wireEditable(el) {
      if (!el) return;
      el.addEventListener("focus", () => {
        activeEditable = el;
        saveSelection();
      });
      el.addEventListener("pointerdown", () => {
        activeEditable = el;
      });
      el.addEventListener("mouseup", () => {
        activeEditable = el;
        saveSelection();
      });
      el.addEventListener("keyup", saveSelection);
      el.addEventListener("input", saveSelection);
    }

    // títulos / contenidos
    if (tEsCtt) {
      wireEditable(tEsCtt);
      if (singleLine) makeSingleLineEditable(tEsCtt);
    }
    if (tEuCtt) {
      wireEditable(tEuCtt);
      if (singleLine) makeSingleLineEditable(tEuCtt);
    }
    if (cEsCtt) wireEditable(cEsCtt);
    if (cEuCtt) wireEditable(cEuCtt);

    document.addEventListener("selectionchange", saveSelection, {
      passive: true,
    });

    // --- tabs ---
    let current = langs[0] || "es";
    function activateTab(lang) {
      current = lang;
      if (tabs.es) tabs.es.hidden = lang !== "es";
      if (tabs.eu) tabs.eu.hidden = lang !== "eu";
      tabBtns.forEach((b) =>
        b.classList.toggle("active", b.dataset.tab === lang)
      );
      const focusEl =
        lang === "es"
          ? cEsCtt || tEsCtt || tEsInput
          : cEuCtt || tEuCtt || tEuInput;
      focusEl?.focus();
      saveSelection();
    }
    tabBtns.forEach((b) =>
      b.addEventListener("click", () => activateTab(b.dataset.tab))
    );
    if (tabBtns[0]) activateTab(tabBtns[0].dataset.tab || "es");
    else activateTab(current);

    // --- toolbar (pointerdown para no perder selección) ---
    function applyCommand(cmd, val = null) {
      const focusEl = current === "es" ? cEsCtt || tEsCtt : cEuCtt || tEuCtt;
      focusEl?.focus();
      restoreSelection();
      requestAnimationFrame(() => {
        document.execCommand(cmd, false, val);
        saveSelection();
        focusEl?.focus();
      });
    }
    function cancel(e) {
      e.preventDefault();
      e.stopPropagation();
    }
    toolbar?.addEventListener("click", cancel, true);
    toolbar?.addEventListener("mouseup", cancel, true);

    toolbar?.addEventListener("pointerdown", (e) => {
      const btn = e.target.closest("button");
      if (!btn) return;
      e.preventDefault();
      e.stopPropagation();
      const cmd = btn.dataset.cmd;
      const block = btn.dataset.block;

      const isTitle =
        !!(activeEditable &&
        activeEditable.classList &&
        activeEditable.classList.contains("editor-title"));

      if (cmd) {
        applyCommand(cmd);
        return;
      }
      if (block && !isTitle) {
        applyCommand("formatBlock", block);
        return;
      }
    });

    const btnLink = toolbar?.querySelector("#btn-link");
    const btnClear = toolbar?.querySelector("#btn-clear");
    btnLink?.addEventListener("pointerdown", (e) => {
      e.preventDefault();
      e.stopPropagation();
      const url = prompt("URL del enlace (https://...)");
      if (url) applyCommand("createLink", url);
    });
    btnClear?.addEventListener("pointerdown", (e) => {
      e.preventDefault();
      e.stopPropagation();
      applyCommand("removeFormat");
    });

    // --- submit: volcar valores al propio form del root ---
    form?.addEventListener("submit", () => {
      // títulos
      if (tEsInput) tEsInput.value = tEsInput.value; // ya es <input>
      if (tEuInput) tEuInput.value = tEuInput.value;
      const tEsHidden = $(root, cfg.title?.esField || "#tx-title-es");
      const tEuHidden = $(root, cfg.title?.euField || "#tx-title-eu");
      if (tEsHidden && tEsCtt) tEsHidden.value = tEsCtt.innerHTML.trim();
      if (tEuHidden && tEuCtt) tEuHidden.value = tEuCtt.innerHTML.trim();

      // contenidos
      if (cEsField && cEsCtt) cEsField.value = (cEsCtt.innerHTML || "").trim();
      if (cEuField && cEuCtt) cEuField.value = (cEuCtt.innerHTML || "").trim();

      if (typeof cfg.onSubmit === "function") cfg.onSubmit(form);
    });

    // API mínima por si la quieres usar
    return {
      activate(lang) {
        activateTab(lang);
      },
      focusContent() {
        (current === "es" ? cEsCtt : cEuCtt)?.focus();
      },
      setTitle(lang, html) {
        const node = lang === "es" ? (tEsCtt || tEsInput) : (tEuCtt || tEuInput);
        if (!node) return;
        if ("value" in node) node.value = html;
        else node.innerHTML = html;
      },
      setContent(lang, html) {
        const node = lang === "es" ? cEsCtt : cEuCtt;
        if (node) node.innerHTML = html;
      },
    };
  }

  global.initRichEditor = initRichEditor;
})(window);
