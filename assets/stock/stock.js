/* PRDRWNDN® Jersey Stock (wp-admin). Data lives in WordPress; every change goes through admin-ajax (action prd_stock). */
(function () {
  const BOOT = window.PRD_STOCK || {};
  const SIZES = ["S", "M", "L", "XL", "2XL", "3XL"];
  const DESIGNS = [{ id: "izuba", name: "Izuba" }, { id: "imigongo", name: "Imigongo" }];
  const STATUS_OPTS = [["Picked up", "Paid · picked up"], ["To ship", "Paid · ship it"], ["On hold", "Hold for pickup"], ["Shipped", "Paid · shipped"]];
  const FILTERS = [["all", "All"], ["hold", "On hold"], ["toship", "To ship"], ["web", "Website"], ["izuba", "Izuba"], ["imigongo", "Imigongo"]];
  const root = document.getElementById("prd-stock");
  if (!root) return;
  const $ = (s) => root.querySelector(s);
  const money = (n) => (n < 0 ? "-$" : "$") + Math.abs(Math.round(n * 100) / 100).toLocaleString("en-US", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const money0 = (n) => (n < 0 ? "-$" : "$") + Math.abs(Math.round(n)).toLocaleString("en-US");

  let state = null;
  let cfg = null, sales = [], costs = [], store = null;
  const ui = { mode: "single", lines: [{ design: "izuba", size: null }, { design: "imigongo", size: null }], status: "Picked up", filter: "all", armed: null, priceEdited: false, statusEdited: false, editing: null, draft: null, busy: false };
  const otherDesign = (id) => (id === "izuba" ? "imigongo" : "izuba");

  function h(tag, attrs, ...kids) {
    const el = document.createElement(tag);
    for (const [k, v] of Object.entries(attrs || {})) {
      if (v === null || v === undefined || v === false) continue;
      if (k === "class") el.className = v;
      else if (k === "text") el.textContent = v;
      else if (k.startsWith("on")) el.addEventListener(k.slice(2), v);
      else el.setAttribute(k, v === true ? "" : v);
    }
    for (const kid of kids) if (kid !== null && kid !== undefined && kid !== false) el.append(kid);
    return el;
  }
  function todayStr() {
    const d = new Date(), p = (n) => String(n).padStart(2, "0");
    return d.getFullYear() + "-" + p(d.getMonth() + 1) + "-" + p(d.getDate());
  }
  function fmtDate(s) {
    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s || "");
    if (!m) return "";
    return new Date(+m[1], +m[2] - 1, +m[3]).toLocaleDateString("en-US", { month: "short", day: "numeric" });
  }

  /* ── State ─────────────────────────────────────── */
  function setState(s) {
    state = s;
    cfg = s.config || null;
    sales = (s.sales || []).concat(s.web || []);
    costs = s.costs || [];
    store = s.store || null;
    if (ui.editing && !sales.some((r) => r.id === ui.editing)) { ui.editing = null; ui.draft = null; }
  }

  const isHold = (r) => r.status === "On hold" || r.status === "Awaiting payment";
  const isPaid = (r) => !isHold(r);
  const isToShip = (r) => r.status === "To ship";
  const made = () => (cfg && cfg.made) || null;
  const madeOf = (d, s) => { const m = made(); return (m && m[d] && Number(m[d][s])) || 0; };
  const priceOf = (key, fallback) => { const p = cfg && cfg.prices && Number(cfg.prices[key]); return p > 0 ? p : fallback; };
  const P_PRESALE = () => priceOf("presale", 45), P_REGULAR = () => priceOf("regular", 50);
  const P_BUNDLE = () => priceOf("bundle", 80);
  const nameOf = (id) => (DESIGNS.find((d) => d.id === id) || { name: String(id || "?") }).name;
  function feeRule(channel) { const f = cfg && cfg.fees && cfg.fees[channel]; return f ? { pct: Number(f.pct) || 0, fixed: Number(f.fixed) || 0 } : { pct: 0, fixed: 0 }; }
  function feeOn(channel, gross) { const f = feeRule(channel); return gross > 0 ? gross * f.pct / 100 + f.fixed : 0; }
  const rowFee = (r) => r.web ? (Number(r.fee) || 0) : feeOn(r.channel, (Number(r.price) || 0) * (Number(r.qty) || 0));
  const feeText = (channel) => { const f = feeRule(channel); return f.pct + "%" + (f.fixed ? " + " + Math.round(f.fixed * 100) + "¢" : ""); };

  function tallies() {
    const sold = {}, held = {};
    for (const d of DESIGNS) { sold[d.id] = {}; held[d.id] = {}; for (const s of SIZES) { sold[d.id][s] = 0; held[d.id][s] = 0; } }
    for (const r of sales) {
      if (!sold[r.design] || !(r.size in sold[r.design])) continue;
      const q = Number(r.qty) || 0;
      if (isHold(r)) held[r.design][r.size] += q; else sold[r.design][r.size] += q;
    }
    return { sold, held, free: (d, s) => madeOf(d, s) - sold[d][s] - held[d][s] };
  }
  const ready = () => !!state && !!made();

  /* ── Server ────────────────────────────────────── */
  async function api(op, data) {
    const body = new URLSearchParams();
    body.set("action", "prd_stock");
    body.set("nonce", BOOT.nonce || "");
    body.set("op", op);
    body.set("data", JSON.stringify(data || {}));
    let res, json;
    try {
      res = await fetch(BOOT.ajax, { method: "POST", credentials: "same-origin", headers: { "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8" }, body });
      json = await res.json();
    } catch (e) {
      throw new Error("Couldn't reach the website. Check your connection and try again.");
    }
    if (!json || !json.success) {
      const msg = (json && json.data && json.data.message) || (res.status === 403 ? "You've been signed out. Reload the page and sign in again." : "Couldn't save. Try again.");
      const err = new Error(msg);
      if (json && json.data && json.data.code) err.code = json.data.code;
      throw err;
    }
    setState(json.data);
    renderAll();
    return json.data;
  }
  async function run(op, data, okText) {
    if (ui.busy) return false;
    ui.busy = true; root.setAttribute("aria-busy", "true");
    try { await api(op, data); if (okText) flash(okText, false); return true; }
    catch (e) {
      flash(e.message, true);
      if (e.code !== "nonce") { try { await api("state", {}); } catch (_) { /* keep what's on screen */ } }
      return false;
    }
    finally { ui.busy = false; root.removeAttribute("aria-busy"); }
  }

  /* ── Render ────────────────────────────────────── */
  function renderSummary() {
    if (!ready()) return;
    const t = tallies();
    let soldT = 0, heldT = 0, madeT = 0;
    for (const d of DESIGNS) for (const s of SIZES) { soldT += t.sold[d.id][s]; heldT += t.held[d.id][s]; madeT += madeOf(d.id, s); }
    const toShip = sales.filter(isToShip).reduce((a, r) => a + (Number(r.qty) || 0), 0);
    const rev = sales.filter(isPaid).reduce((a, r) => a + (Number(r.price) || 0) * (Number(r.qty) || 0), 0);
    $("#sumSold").textContent = soldT + " / " + madeT;
    $("#sumHeld").textContent = String(heldT);
    $("#sumLeft").textContent = String(madeT - soldT - heldT);
    $("#sumShip").textContent = String(toShip);
    $("#sumRev").textContent = money0(rev);
    if (cfg && cfg.note) $("#eyebrow").textContent = "PRDRWNDN® · " + cfg.note;
  }

  function renderStock() {
    const el = $("#stock");
    if (!state) return;
    if (!made()) { el.replaceChildren(h("div", { class: "placeholder", text: "Stock counts aren't set up yet." })); return; }
    const t = tallies();
    el.replaceChildren(...DESIGNS.map((d) => {
      let madeT = 0, freeT = 0, heldT = 0;
      const cells = SIZES.map((s) => {
        const mk = madeOf(d.id, s), fr = t.free(d.id, s), hd = t.held[d.id][s], sd = t.sold[d.id][s];
        madeT += mk; freeT += fr; heldT += hd;
        const state_ = fr <= 0 ? "out" : fr === 1 ? "low" : "";
        const pS = mk ? Math.min(100, (sd / mk) * 100) : 0, pH = mk ? Math.min(100 - pS, (hd / mk) * 100) : 0;
        return h("button", {
          type: "button", class: "cell " + state_,
          "aria-label": d.name + " size " + s + ": " + fr + " free of " + mk + (hd ? ", " + hd + " on hold" : "") + ". Log this size.",
          onclick: () => { const i = ui.mode === "bundle" && ui.lines[0].size && !ui.lines[1].size ? 1 : 0; const L = ui.lines[i]; L.design = d.id; if (ui.mode === "bundle" && i === 0 && !ui.lines[1].size) ui.lines[1].design = otherDesign(d.id); L.size = freeFor(tallies(), i, d.id, s) > 0 ? s : null; renderForm(); $("#logH").scrollIntoView({ behavior: matchMedia("(prefers-reduced-motion: reduce)").matches ? "auto" : "smooth", block: "start" }); }
        },
          h("span", { class: "sz", text: s }),
          h("span", { class: "left", text: String(fr) }),
          h("span", { class: "of", text: fr < 0 ? (-fr) + " too many" : "of " + mk }),
          h("span", { class: "hd", text: hd ? hd + " held" : "" }),
          h("span", { class: "bar" }, h("i", { style: "width:" + pS + "%" }), h("i", { class: "h", style: "width:" + pH + "%" })));
      });
      return h("article", { class: "card", "aria-label": d.name + " stock" },
        h("div", { class: "card-head" },
          h("div", { class: "card-name" }, h("span", { class: "swatch " + d.id, "aria-hidden": "true" }), h("h4", { text: d.name })),
          h("div", { class: "card-left" }, h("b", { text: String(freeT) }), " free of " + madeT, heldT ? h("span", { class: "h", text: " · " + heldT + " held" }) : null)),
        h("div", { class: "sizes" }, ...cells));
    }));
  }

  function renderSite() {
    const body = $("#siteBody"), note = $("#siteNote");
    if (!state || !store) return;
    const t = ready() ? tallies() : null;
    const linked = store.linked || {};
    const levels = store.levels || {};
    const webOrders = new Set((state.web || []).map((r) => r.bundleId || r.id)).size;
    note.textContent = webOrders ? webOrders + " website order" + (webOrders === 1 ? "" : "s") + " with jerseys so far." : "No website orders with jerseys yet.";
    const kids = [];
    const names = DESIGNS.filter((d) => linked[d.id]).map((d) => h("a", { href: linked[d.id].url, target: "_blank", rel: "noopener", text: linked[d.id].name }));
    const joinNames = [];
    names.forEach((a, i) => { if (i) joinNames.push(" and "); joinNames.push(a); });

    if (store.sync) {
      kids.push(h("p", { class: "site-line" }, h("span", { class: "dot on", "aria-hidden": "true" }), h("b", { text: "Synced." }),
        " The shop shows the same free numbers as above. Every sale, hold, cancel or delete here updates it, and website orders show up below by themselves."));
      if (names.length) kids.push(h("p", { class: "sub" }, "Linked to ", ...joinNames, "."));
      if (store.changed && store.changed.length) {
        kids.push(h("p", { class: "sub", text: "Just updated on the website: " + store.changed.map((c) => nameOf(c.design) + " " + c.size + " " + (c.from === null ? "(not tracked)" : c.from) + " to " + c.to).join(", ") + "." }));
      }
      const armed = ui.armed === "sync-off";
      kids.push(h("div", { class: "ctl" }, h("button", {
        type: "button", class: "ghost danger" + (armed ? " armed" : ""), text: armed ? "Tap again to stop syncing" : "Stop syncing",
        onclick: () => { if (ui.armed !== "sync-off") { arm("sync-off"); return; } ui.armed = null; run("sync", { on: false }, "Website sync is off. The shop keeps its current stock numbers."); }
      })));
    } else {
      kids.push(h("p", { class: "site-line" }, h("span", { class: "dot off", "aria-hidden": "true" }), h("b", { text: "Not synced yet." }),
        " The shop's stock doesn't know about sales and holds logged here. Here's what the website has now and what it will show once synced:"));
      if (t) {
        const anyLinked = Object.keys(linked).length > 0;
        if (anyLinked) {
          for (const d of DESIGNS) {
            if (!linked[d.id]) continue;
            const now = SIZES.map((s) => { const v = levels[d.id] && levels[d.id][s]; return v === null || v === undefined ? "any" : String(v); });
            const will = SIZES.map((s) => String(Math.max(0, t.free(d.id, s))));
            const head = h("tr", {}, h("th", { scope: "col", text: d.name }), ...SIZES.map((s) => h("th", { scope: "col", text: s })));
            kids.push(h("div", { class: "diff-wrap" }, h("table", { class: "st-diff" },
              h("thead", {}, head),
              h("tbody", {},
                h("tr", {}, h("th", { scope: "row", text: "Website now" }), ...now.map((v) => h("td", { text: v }))),
                h("tr", {}, h("th", { scope: "row", text: "After sync" }), ...will.map((v, i) => h("td", { class: v !== now[i] ? "chg" : null, text: v })))))));
          }
          const untracked = DESIGNS.some((d) => linked[d.id] && SIZES.some((s) => !levels[d.id] || levels[d.id][s] === null || levels[d.id][s] === undefined));
          if (untracked) kids.push(h("p", { class: "sub", text: "\"any\" means the website isn't counting stock for that size, so it would sell it even when you have none." }));
          kids.push(h("div", { class: "actions-row" }, h("button", {
            type: "button", class: "primary", text: "SYNC THE WEBSITE",
            onclick: () => run("sync", { on: true }, "Synced. The shop now shows the same free numbers as this page.")
          })));
        }
      }
    }
    if (store.issues && store.issues.length) kids.push(h("ul", { class: "issues" }, ...store.issues.map((x) => h("li", { text: x }))));
    body.className = "site-body";
    body.replaceChildren(...kids);
  }

  function freeFor(t, i, d, s) {
    if (!t) return null;
    let fr = t.free(d, s);
    if (ui.mode === "bundle") { const o = ui.lines[1 - i]; if (o.design === d && o.size === s) fr -= 1; }
    return fr;
  }
  function setDefaultPrice() {
    if (ui.priceEdited) return;
    const ch = $("#channel").value;
    $("#price").value = String(ui.mode === "bundle" ? P_BUNDLE() : ch === "Presale site" ? P_PRESALE() : P_REGULAR());
    renderPreview();
  }

  function renderForm() {
    const t = ready() ? tallies() : null;
    const bundle = ui.mode === "bundle";
    $("#modeSeg").replaceChildren(...[["single", "One jersey"], ["bundle", "Two for " + money0(P_BUNDLE()) + " (" + money0(P_BUNDLE() / 2) + " each)"]].map(([m, label]) => h("button", {
      type: "button", "aria-pressed": String(ui.mode === m),
      onclick: () => { if (ui.mode !== m) { ui.mode = m; ui.priceEdited = false; setDefaultPrice(); } renderForm(); }
    }, label)));
    const box = $("#linesBox");
    box.className = "lines" + (bundle ? " two" : "");
    box.replaceChildren(...(bundle ? [0, 1] : [0]).map((i) => {
      const L = ui.lines[i], dId = "designLbl" + i, sId = "sizeLbl" + i;
      return h("div", { class: "line" },
        bundle ? h("span", { class: "line-title", text: "Jersey " + (i + 1) }) : null,
        h("div", { class: "field" },
          h("span", { class: "lbl", id: dId, text: bundle ? "Design" : "Jersey" }),
          h("div", { class: "seg", role: "group", "aria-labelledby": dId }, ...DESIGNS.map((d) => h("button", {
            type: "button", "aria-pressed": String(L.design === d.id),
            onclick: () => { L.design = d.id; if (bundle && i === 0 && !ui.lines[1].size) ui.lines[1].design = otherDesign(d.id); if (L.size && t && freeFor(t, i, d.id, L.size) <= 0) L.size = null; renderForm(); }
          }, h("span", { class: "swatch sm " + d.id, "aria-hidden": "true" }), d.name)))),
        h("div", { class: "field" },
          h("span", { class: "lbl", id: sId, text: "Size" }),
          h("div", { class: "seg", role: "group", "aria-labelledby": sId }, ...SIZES.map((s) => {
            const fr = freeFor(t, i, L.design, s), sel = L.size === s;
            return h("button", {
              type: "button", "aria-pressed": String(sel), disabled: !sel && fr !== null && fr <= 0,
              "aria-label": "Size " + s + (fr !== null ? ", " + Math.max(0, fr) + " free" : ""),
              onclick: () => { L.size = s; renderForm(); }
            }, s, fr !== null ? h("span", { class: "count", text: fr > 0 ? fr + " free" : "none free" }) : null);
          }))));
    }));
    $("#qtyField").hidden = bundle;
    $("#priceLbl").textContent = bundle ? "Price for both ($)" : "Price each ($)";
    $("#statusSeg").replaceChildren(...STATUS_OPTS.map(([v, label]) => h("button", {
      type: "button", class: v === "On hold" ? "holdbtn" : null, "aria-pressed": String(ui.status === v),
      onclick: () => { ui.status = v; ui.statusEdited = true; renderForm(); }
    }, label)));
    const holding = ui.status === "On hold";
    const btn = $("#submitBtn");
    btn.textContent = holding ? (bundle ? "HOLD BOTH FOR PICKUP" : "HOLD FOR PICKUP") : (bundle ? "LOG 2 JERSEYS" : "LOG SALE");
    btn.className = "primary" + (holding ? " holding" : "");
    $("#customerLbl").textContent = holding ? "Who it's held for (needed)" : "Customer (optional)";
    renderPreview();
  }

  function renderPreview() {
    const p = $("#preview");
    if (!p) return;
    const bundle = ui.mode === "bundle";
    const lines = bundle ? ui.lines : [ui.lines[0]];
    const qty = bundle ? 1 : Math.max(1, Math.floor(Number($("#qty").value)) || 1);
    const price = Number($("#price").value) || 0;
    const cust = $("#customer").value.trim();
    const items = lines.map((L) => nameOf(L.design) + " " + (L.size || "(pick a size)")).join(" + ") + (!bundle && qty > 1 ? " × " + qty : "");
    const amount = bundle ? money0(price) + " for both" : money0(price * qty) + (qty > 1 ? " (" + money0(price) + " each)" : "");
    const status = (STATUS_OPTS.find((o) => o[0] === ui.status) || [ui.status, ui.status])[1];
    const same = bundle && !!lines[0].size && lines[0].design === lines[1].design && lines[0].size === lines[1].size;
    p.className = "preview" + (same ? " warn" : "");
    const kids = [h("b", { text: "You're logging: " }), [items, amount, status, cust].filter(Boolean).join(" · ")];
    if (same) kids.push(h("span", { text: ". Both jerseys are the same design and size. Change Jersey 2 if they took one of each." }));
    p.replaceChildren(...kids);
  }

  function renderFilters() {
    $("#filters").replaceChildren(...FILTERS.map(([id, label]) => h("button", {
      type: "button", "aria-pressed": String(ui.filter === id), onclick: () => { ui.filter = id; renderFilters(); renderSales(); }
    }, label)));
  }

  function kv(rows) {
    const dl = h("dl", { class: "kv" });
    for (const r of rows) {
      if (!r) continue;
      const dt = h("dt", { text: r[0] }), dd = h("dd", { class: r[2] || null, text: r[1] });
      if (r[3]) dl.append(h("div", { class: r[3], style: "display:contents" }, dt, dd)); else dl.append(dt, dd);
    }
    return dl;
  }

  function renderMoney() {
    const el = $("#money");
    if (!ready()) return;
    const t = tallies();
    let madeT = 0, freeT = 0;
    for (const d of DESIGNS) for (const s of SIZES) { madeT += madeOf(d.id, s); freeT += t.free(d.id, s); }
    const totalCost = costs.reduce((a, c) => a + (Number(c.amount) || 0), 0);
    const perJ = madeT ? totalCost / madeT : 0;
    const paid = sales.filter(isPaid), holds = sales.filter(isHold);
    const paidQty = paid.reduce((a, r) => a + (Number(r.qty) || 0), 0);
    const gross = paid.reduce((a, r) => a + (Number(r.price) || 0) * (Number(r.qty) || 0), 0);
    const fees = paid.reduce((a, r) => a + rowFee(r), 0);
    const net = gross - fees;
    const position = net - totalCost;
    const holdValue = holds.reduce((a, r) => a + (Number(r.price) || 0) * (Number(r.qty) || 0), 0);
    const holdQty = holds.reduce((a, r) => a + (Number(r.qty) || 0), 0);
    const pReg = P_REGULAR(), pPre = P_PRESALE(), pB = P_BUNDLE();
    const dealCount = new Set(sales.filter((r) => r.bundleId).map((r) => r.bundleId)).size;
    const webQty = paid.filter((r) => r.web).reduce((a, r) => a + (Number(r.qty) || 0), 0);
    const toGo = position < 0 ? Math.ceil(-position / pReg) : 0;
    const pctBack = Math.max(0, Math.min(100, totalCost ? net / totalCost * 100 : 0));

    const sofar = h("div", { class: "panel mblock" },
      h("h4", { text: "So far" }),
      h("div", {}, h("div", { class: "big " + (position >= 0 ? "pos" : "neg"), text: money(position) }),
        h("p", { class: "sub", text: position >= 0 ? "Profit after all costs and fees." : "Still to earn back before you're in profit." })),
      h("div", { class: "progress", role: "img", "aria-label": "Costs earned back: " + Math.round(pctBack) + "%" }, h("i", { style: "width:" + pctBack + "%" })),
      kv([
        ["Paid in · " + paidQty + " jersey" + (paidQty === 1 ? "" : "s"), money(gross)],
        webQty ? ["Of those, from the website", String(webQty)] : null,
        dealCount ? ["Two-jersey orders", String(dealCount)] : null,
        fees > 0 ? ["Payment fees (est.)", "-" + money(fees)] : null,
        ["Costs paid out", "-" + money(totalCost)],
        [position >= 0 ? "Profit so far" : "Still to earn back", money(position), position >= 0 ? "pos" : "neg", "total"]
      ]),
      h("p", { class: "sub", text: (position < 0 ? "About " + toGo + " more at " + money0(pReg) + " to break even. " : "") + (holdQty ? holdQty + " on hold, worth " + money0(holdValue) + " when paid." : "") }));

    function priceCard(p, tag) {
      const site = p - feeOn("Presale site", p) - perJ, tik = p - feeOn("TikTok Shop", p) - perJ, cash = p - perJ;
      return h("div", { class: "pc" },
        h("div", {}, h("div", { class: "price", text: money0(p) }), h("div", { class: "tag", text: tag })),
        kv([
          ["Profit each, cash / Zelle / Apple Pay / Cash App (no fees)", money(cash), "pos"],
          ["Profit each, card on the website or presale site (" + feeText("Presale site") + ")", money(site), "pos"],
          ["Profit each, TikTok Shop (" + feeText("TikTok Shop") + ")", money(tik), "pos"],
          ["All " + madeT + " sold at this price", money0(p * madeT - totalCost)],
          ["Break even after", Math.ceil(totalCost / p) + " jerseys"]
        ]));
    }
    const remainingAt = (p) => position + holdValue + freeT * p;
    const compare = h("div", { class: "panel mblock compare" },
      h("h4", { text: money0(pPre) + " vs " + money0(pReg) }),
      h("p", { class: "sub", text: "Each jersey cost you " + money(perJ) + " (" + money(totalCost) + " ÷ " + madeT + "). Profit is after that cost." }),
      h("div", { class: "pricecards" }, priceCard(pPre, "Presale price"), priceCard(pReg, "Regular price")),
      kv([
        ["Profit each in a 2-for-" + money0(pB) + " deal (no fees)", money(pB / 2 - perJ), "pos"],
        ["If holds are paid and the " + freeT + " left sell at " + money0(pReg), money0(remainingAt(pReg)), "pos", "total"],
        ["...or at " + money0(pPre), money0(remainingAt(pPre)), "pos"],
        ["...or all as 2-for-" + money0(pB) + " deals", money0(remainingAt(pB / 2)), "pos"]
      ]),
      h("p", { class: "sub", text: "Projections leave out payment fees and your shipping costs to customers." }));

    const list = h("ul", { class: "costs" }, ...costs.slice().sort((a, b) => (Number(a.createdAt) || 0) - (Number(b.createdAt) || 0)).map((c) => {
      const armed = ui.armed === "cost:" + c.id;
      return h("li", {}, h("span", { text: String(c.label || "Cost") }), h("span", { class: "amt", text: money(Number(c.amount) || 0) }),
        h("button", { type: "button", class: "ghost danger" + (armed ? " armed" : ""), text: armed ? "Tap again" : "Remove", "aria-label": "Remove cost " + (c.label || ""), onclick: () => removeCost(c.id) }));
    }));
    const cLabel = h("input", { type: "text", id: "costLabel", maxlength: "60", placeholder: "e.g. Poly mailers", "aria-label": "What the cost was for" });
    const cAmt = h("input", { type: "number", id: "costAmt", min: "0", step: "0.01", inputmode: "decimal", placeholder: "Amount $", "aria-label": "Amount in dollars" });
    const cForm = h("form", { class: "costform", onsubmit: (ev) => { ev.preventDefault(); addCost(cLabel.value, cAmt.value); } }, cLabel, cAmt, h("button", { type: "submit", class: "ghost go", text: "Add cost" }));
    const costBlock = h("div", { class: "panel mblock" },
      h("h4", { text: "Costs" }),
      list,
      kv([["Total", money(totalCost), null, "total"], ["Per jersey (÷ " + madeT + ")", money(perJ)]]),
      cForm,
      h("p", { class: "sub", text: "Add mailers, postage or anything else you pay for. Profit updates straight away." }));

    // Keep what's typed in the cost form across re-renders.
    const oldL = $("#costLabel"), oldA = $("#costAmt");
    if (oldL) cLabel.value = oldL.value;
    if (oldA) cAmt.value = oldA.value;
    el.replaceChildren(sofar, compare, costBlock);
  }

  function renderSales() {
    const el = $("#salesList");
    if (!state) return;
    const groups = [], byBundle = {};
    for (const r of sales) {
      if (r.bundleId) {
        if (!byBundle[r.bundleId]) { byBundle[r.bundleId] = { rows: [] }; groups.push(byBundle[r.bundleId]); }
        byBundle[r.bundleId].rows.push(r);
      } else groups.push({ rows: [r] });
    }
    const match = (r) => ui.filter === "all" || (ui.filter === "hold" ? isHold(r) : ui.filter === "toship" ? isToShip(r) : ui.filter === "web" ? !!r.web : r.design === ui.filter);
    const list = groups.filter((g) => g.rows.some(match)).map((g) => ({
      rows: g.rows.slice().sort((a, b) => ((Number(a.createdAt) || 0) - (Number(b.createdAt) || 0)) || String(a.design).localeCompare(String(b.design))),
      hold: g.rows.some(isHold) ? 1 : 0,
      date: g.rows.reduce((m, r) => (String(r.date || "") > m ? String(r.date || "") : m), ""),
      at: Math.max(...g.rows.map((r) => Number(r.createdAt) || 0))
    })).sort((a, b) => (b.hold - a.hold) || b.date.localeCompare(a.date) || (b.at - a.at));
    if (!list.length) {
      el.replaceChildren(h("div", { class: "empty", text: sales.length ? "Nothing matches this filter." : "Sales and holds you log show up here, newest first." }));
      return;
    }
    el.replaceChildren(...list.map((g) => g.rows.length > 1 ? bundleCard(g.rows) : saleCard(g.rows[0])));
  }

  const viaOf = (r) => {
    if (r.web) return "Website order #" + r.orderNo + (r.payment ? " (" + r.payment + ")" : "");
    return r.payment && r.payment !== r.channel && r.payment !== "Card" ? String(r.channel || "") + " (" + String(r.payment) + ")" : String(r.channel || "");
  };
  const whenOf = (r) => isHold(r) && !r.web ? "Held " + fmtDate(r.date) : (r.heldDate ? "Held " + fmtDate(r.heldDate) + " · picked up " + fmtDate(r.date) : fmtDate(r.date));

  function saleCard(r) {
    const d = DESIGNS.find((x) => x.id === r.design);
    const qty = Number(r.qty) || 0, price = Number(r.price) || 0, held = isHold(r);
    const meta = [whenOf(r), viaOf(r), r.customer ? String(r.customer) : ""].filter(Boolean).join(" · ");
    return h("article", { class: "sale" + (held ? " isheld" : "") + (r.web ? " isweb" : "") },
      h("div", { class: "what" },
        h("span", { class: "chip " + (d ? d.id : ""), text: d ? d.name : String(r.design || "?") }),
        h("span", { class: "size", text: String(r.size || "?") + (qty !== 1 ? " × " + qty : "") })),
      h("div", { class: "amt" }, money0(price * qty), held ? h("small", { text: r.web ? "not paid yet" : "due at pickup" }) : null),
      h("div", { class: "meta", text: meta }),
      lineCtl(r));
  }

  function editor(r) {
    const dft = ui.draft, qty = Number(r.qty) || 1, t = tallies();
    const freeEx = (d, s) => t.free(d, s) + (r.design === d && r.size === s ? qty : 0);
    return h("div", { class: "editor" },
      h("div", { class: "seg", role: "group", "aria-label": "Design" }, ...DESIGNS.map((d) => h("button", {
        type: "button", "aria-pressed": String(dft.design === d.id),
        onclick: () => { dft.design = d.id; if (dft.size && freeEx(d.id, dft.size) < qty) dft.size = null; renderSales(); }
      }, h("span", { class: "swatch sm " + d.id, "aria-hidden": "true" }), d.name))),
      h("div", { class: "seg", role: "group", "aria-label": "Size" }, ...SIZES.map((s) => {
        const fr = freeEx(dft.design, s), sel = dft.size === s;
        return h("button", { type: "button", "aria-pressed": String(sel), disabled: !sel && fr < qty, onclick: () => { dft.size = s; renderSales(); } },
          s, h("span", { class: "count", text: fr > 0 ? fr + " free" : "none free" }));
      })),
      h("div", { class: "ctl" },
        h("button", { type: "button", class: "ghost go", text: "Save change", onclick: () => saveEdit(r) }),
        h("button", { type: "button", class: "ghost", text: "Cancel", onclick: () => { ui.editing = null; ui.draft = null; renderSales(); } })));
  }
  async function saveEdit(r) {
    const dft = ui.draft;
    if (!dft || !dft.size) { flash("Pick a size.", true); return; }
    ui.editing = null; ui.draft = null;
    if (dft.design === r.design && dft.size === r.size) { renderSales(); return; }
    await run("update", { id: r.id, patch: { design: dft.design, size: dft.size } }, "Changed to " + nameOf(dft.design) + " " + dft.size + ".");
  }
  function pillClass(r) { return isHold(r) ? "hold" : (isToShip(r) || r.status === "To pick up") ? "toship" : "done"; }
  function lineCtl(r) {
    if (r.web) {
      return h("div", { class: "ctl" },
        h("span", { class: "pill " + pillClass(r), text: r.status }),
        r.orderUrl ? h("a", { class: "ghost", href: r.orderUrl, text: "Open order #" + r.orderNo }) : null);
    }
    if (ui.editing === r.id && ui.draft) return editor(r);
    const held = isHold(r);
    const ctl = h("div", { class: "ctl" }, h("span", { class: "pill " + pillClass(r), text: r.status || "To ship" }));
    if (held) ctl.append(h("button", { type: "button", class: "ghost go", text: "Picked up & paid", onclick: () => run("update", { id: r.id, patch: { status: "Picked up", heldDate: r.date || todayStr(), date: todayStr() } }) }));
    if (r.status === "To ship") ctl.append(h("button", { type: "button", class: "ghost go", text: "Mark shipped", onclick: () => run("update", { id: r.id, patch: { status: "Shipped" } }) }));
    if (r.status === "Shipped") ctl.append(h("button", { type: "button", class: "ghost", text: "Back to “To ship”", onclick: () => run("update", { id: r.id, patch: { status: "To ship" } }) }));
    if (r.status === "Picked up" && r.heldDate) ctl.append(h("button", { type: "button", class: "ghost", text: "Undo pickup", onclick: () => run("update", { id: r.id, patch: { status: "On hold", date: r.heldDate, heldDate: null } }) }));
    ctl.append(h("button", { type: "button", class: "ghost", text: "Change jersey", onclick: () => { ui.editing = r.id; ui.draft = { design: r.design, size: r.size }; renderSales(); } }));
    const armed = ui.armed === r.id;
    ctl.append(h("button", { type: "button", class: "ghost danger" + (armed ? " armed" : ""), text: armed ? "Tap again to " + (held ? "cancel" : "delete") : (held ? "Cancel hold" : "Delete"), onclick: () => removeSale(r.id) }));
    return ctl;
  }
  function bundleCard(rows) {
    const first = rows[0];
    const sum = (list) => list.reduce((a, r) => a + (Number(r.price) || 0) * (Number(r.qty) || 0), 0);
    const total = sum(rows), due = sum(rows.filter(isHold));
    const count = rows.reduce((a, r) => a + (Number(r.qty) || 0), 0);
    const meta = [viaOf(first), first.customer ? String(first.customer) : ""].filter(Boolean).join(" · ");
    return h("article", { class: "sale bundle" + (due ? " isheld" : "") + (first.web ? " isweb" : "") },
      h("div", { class: "what" }, h("span", { class: "chip deal", text: count + " for " + money0(total) })),
      h("div", { class: "amt" }, money0(total), due ? h("small", { text: money0(due) + (first.web ? " not paid yet" : " due at pickup") }) : null),
      h("div", { class: "meta", text: meta }),
      h("div", { class: "items" }, ...rows.map((r) => {
        const d = DESIGNS.find((x) => x.id === r.design);
        return h("div", { class: "item" },
          h("div", { class: "what" },
            h("span", { class: "chip " + (d ? d.id : ""), text: d ? d.name : String(r.design || "?") }),
            h("span", { class: "size", text: String(r.size || "?") + ((Number(r.qty) || 1) !== 1 ? " × " + r.qty : "") }),
            h("span", { class: "when", text: whenOf(r) })),
          first.web ? null : lineCtl(r));
      })),
      first.web ? lineCtl(first) : null);
  }

  function renderAll() { renderSummary(); renderStock(); renderSite(); renderForm(); renderMoney(); renderSales(); }

  function showMsg(text, isErr) { const m = $("#formMsg"); m.textContent = text; m.className = "msg " + (isErr ? "err" : "ok"); }
  let flashTimer = null;
  function flash(text, isErr) {
    const n = $("#notice");
    n.textContent = text; n.hidden = false; n.className = "st-notice" + (isErr ? "" : " ok");
    clearTimeout(flashTimer);
    if (!isErr) flashTimer = setTimeout(() => { n.hidden = true; }, 3500);
  }

  let armTimer = null;
  function arm(key) {
    ui.armed = key; renderSales(); renderMoney(); renderSite();
    clearTimeout(armTimer); armTimer = setTimeout(() => { ui.armed = null; renderSales(); renderMoney(); renderSite(); }, 4000);
  }
  async function removeSale(id) {
    if (ui.armed !== id) { arm(id); return; }
    ui.armed = null; clearTimeout(armTimer);
    await run("delete", { id });
  }
  async function removeCost(id) {
    if (ui.armed !== "cost:" + id) { arm("cost:" + id); return; }
    ui.armed = null; clearTimeout(armTimer);
    await run("cost_remove", { id });
  }
  async function addCost(label, amount) {
    const a = Math.round(Number(amount) * 100) / 100, l = String(label || "").trim().slice(0, 60);
    if (!l) { flash("Say what the cost was for.", true); return; }
    if (!(a > 0)) { flash("Enter the cost in dollars.", true); return; }
    const ok = await run("cost_add", { label: l, amount: a });
    if (ok) { const L = $("#costLabel"), A = $("#costAmt"); if (L) L.value = ""; if (A) A.value = ""; }
  }

  function defaultsForChannel() {
    const ch = $("#channel").value;
    $("#payField").hidden = ch === "Presale site" || ch === "TikTok Shop";
    setDefaultPrice();
    if (!ui.statusEdited) { ui.status = ch === "In person" ? "Picked up" : "To ship"; renderForm(); }
  }
  $("#channel").addEventListener("change", defaultsForChannel);
  $("#price").addEventListener("input", () => { ui.priceEdited = true; renderPreview(); });
  $("#qty").addEventListener("input", renderPreview);
  $("#customer").addEventListener("input", renderPreview);
  $("#date").value = todayStr();

  $("#saleForm").addEventListener("submit", async (ev) => {
    ev.preventDefault();
    if (!ready()) { showMsg("Stock is still loading. Try again in a moment.", true); return; }
    const bundle = ui.mode === "bundle";
    const lines = bundle ? ui.lines : [ui.lines[0]];
    if (lines.some((L) => !L.size)) { showMsg(bundle ? "Pick a size for both jerseys." : "Pick a size first.", true); return; }
    const qty = bundle ? 1 : Math.floor(Number($("#qty").value));
    if (!(qty >= 1 && qty <= 10)) { showMsg("How many must be between 1 and 10.", true); return; }
    const price = Number($("#price").value);
    if (!(price >= 0)) { showMsg(bundle ? "Enter the price for both, in dollars." : "Enter the price each, in dollars.", true); return; }
    const customer = $("#customer").value.trim().slice(0, 80);
    const holding = ui.status === "On hold";
    if (holding && !customer) { showMsg("Add who it's held for.", true); return; }
    const t = tallies(), need = {};
    for (const L of lines) { const k = L.design + "|" + L.size; need[k] = (need[k] || 0) + qty; }
    for (const k of Object.keys(need)) {
      const [d, s] = k.split("|"), fr = t.free(d, s);
      if (need[k] > fr) { showMsg("Only " + Math.max(0, fr) + " " + nameOf(d) + " free in " + s + ".", true); return; }
    }
    const ch = $("#channel").value;
    const payment = ch === "Presale site" ? "Card" : ch === "TikTok Shop" ? "TikTok Shop" : $("#payment").value;
    const each = bundle ? Math.round(price / 2 * 100) / 100 : price;
    const shared = { qty, price: each, channel: ch, payment, customer, date: $("#date").value || todayStr(), status: ui.status };
    if (bundle) shared.bundleId = "b" + Date.now().toString(36) + Math.random().toString(36).slice(2, 6);
    const recs = lines.map((L) => Object.assign({ design: L.design, size: L.size }, shared));
    const btn = $("#submitBtn"); btn.disabled = true;
    showMsg("Saving...", false);
    const ok = await run("add", { recs });
    btn.disabled = false;
    if (ok) {
      const what = recs.map((r) => nameOf(r.design) + " " + r.size).join(" + ") + (qty > 1 ? " × " + qty : "");
      showMsg((holding ? "Held: " : "Logged: ") + what + (bundle ? ", 2 for " + money0(price) : "") + (holding ? " for " + customer : "") + "." + (store && store.sync ? " Website stock updated." : ""), false);
      ui.lines[0].design = "izuba"; ui.lines[0].size = null; ui.lines[1].design = "imigongo"; ui.lines[1].size = null;
      $("#customer").value = ""; $("#qty").value = "1";
      renderForm();
    } else showMsg("Not saved. See the message at the top.", true);
  });

  // Pick up website orders and changes from your other devices.
  let lastPoll = Date.now();
  async function poll() {
    if (ui.busy || ui.editing || document.hidden) return;
    lastPoll = Date.now();
    try { await api("state", {}); } catch (e) { /* quiet; the next change will show any problem */ }
  }
  setInterval(poll, 60000);
  document.addEventListener("visibilitychange", () => { if (!document.hidden && Date.now() - lastPoll > 15000) poll(); });

  renderFilters();
  if (BOOT.state) setState(BOOT.state);
  else flash("Couldn't load the stock. Reload the page.", true);
  setDefaultPrice();
  renderAll();
})();
