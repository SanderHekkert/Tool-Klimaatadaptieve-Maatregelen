(function () {
    "use strict";

    const form = document.getElementById("maatregelen-form");
    if (!form || !form.dataset.previewUrl) {
        return;
    }

    const chipsEl = document.getElementById("mt-live-chips");
    const metaEl = document.getElementById("mt-live-meta");
    const passEl = document.getElementById("mt-live-pass");
    const failEl = document.getElementById("mt-live-fail");
    const statusEl = document.getElementById("mt-live-status");

    const debounceMs = 380;
    let timer = null;
    let abort = null;

    function h(s) {
        if (s == null || s === "") {
            return "";
        }
        return String(s)
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    function renderChips(chips) {
        if (!chipsEl || !Array.isArray(chips)) {
            return;
        }
        chipsEl.innerHTML =
            '<p class="mt-filter__label">Huidige filters</p><ul class="mt-filter__chips">' +
            chips
                .map(function (c) {
                    return (
                        "<li><span class=\"mt-filter__k\">" +
                        h(c.label) +
                        "</span>" +
                        h(c.value) +
                        "</li>"
                    );
                })
                .join("") +
            "</ul>";
    }

    function renderMeta(meta) {
        if (!metaEl) {
            return;
        }
        const parts = [];
        if (meta.volume_m3 != null) {
            parts.push(
                "<div class=\"mt-alert mt-alert--warn\" style=\"margin:0 0 0.75rem;\"><strong>Te bergen volume:</strong> " +
                    h(Number(meta.volume_m3).toLocaleString("nl-NL", { minimumFractionDigits: 2, maximumFractionDigits: 2 })) +
                    " m³</div>"
            );
        }
        if (Array.isArray(meta.risicos) && meta.risicos.length === 1 && meta.risicos[0] === "overstromingsgevaar") {
            parts.push(
                "<div class=\"mt-alert mt-alert--warn\" style=\"margin:0 0 0.75rem;\">In Bijlage E zijn geen maatregelen die uitsluitend op “overstromingsgevaar” zijn gekoppeld.</div>"
            );
        }
        if (!meta.niveau_selected) {
            parts.push(
                "<div class=\"mt-alert mt-alert--warn\" style=\"margin:0;\">Vink minimaal één schaalniveau aan om maatregelen te kunnen filteren.</div>"
            );
        }
        metaEl.innerHTML = parts.join("");
    }

    function renderPass(items) {
        if (!passEl) {
            return;
        }
        const passed = items.filter(function (i) {
            return i.pass;
        });
        if (passed.length === 0) {
            passEl.innerHTML =
                '<div class="mt-live-empty mt-live-empty--pass"><strong>Nog geen passende maatregelen</strong><p>Pas je filters aan of vul meer gegevens in.</p></div>';
            return;
        }
        passEl.innerHTML =
            '<h3 class="mt-live-sub">Voldoen <span class="mt-live-count">' +
            passed.length +
            "</span></h3>" +
            '<div class="mt-live-list">' +
            passed.map(renderPassItem).join("") +
            "</div>";
    }

    function renderPassItem(i) {
        const warn =
            i.warnings && i.warnings.length
                ? '<ul class="mt-live-warns">' +
                  i.warnings.map(function (w) {
                      return "<li>" + h(w) + "</li>";
                  }).join("") +
                  "</ul>"
                : "";
        const water = i.water && i.water.toelichting ? '<p class="mt-live-water">' + h(i.water.toelichting) + "</p>" : "";
        const badge = i.bijlage_onvolledig ? '<span class="mt-badge">Bijlage E onvolledig</span>' : "";
        return (
            '<article class="mt-live-item mt-live-item--pass">' +
            '<div class="mt-live-item__head"><h4 class="mt-live-item__title">' +
            h(i.naam) +
            "</h4>" +
            badge +
            "</div>" +
            '<dl class="mt-live-dl">' +
            "<div><dt>Investering</dt><dd>" +
            h(i.investering_tekst) +
            "</dd></div>" +
            "<div><dt>Niveau</dt><dd>" +
            h(i.niveau_label) +
            "</dd></div>" +
            "<div><dt>Effect</dt><dd>" +
            h(i.effect_tekst) +
            "</dd></div>" +
            "</dl>" +
            water +
            warn +
            "</article>"
        );
    }

    function renderFail(items) {
        if (!failEl) {
            return;
        }
        const failed = items.filter(function (i) {
            return !i.pass;
        });
        failEl.innerHTML =
            '<h3 class="mt-live-sub">Vallen weg <span class="mt-live-count mt-live-count--muted">' +
            failed.length +
            "</span></h3>" +
            '<div class="mt-live-list mt-live-list--fail">' +
            failed.map(renderFailItem).join("") +
            "</div>";
    }

    function renderFailItem(i) {
        const reasons =
            "<ul class=\"mt-fail-reasons\">" +
            (i.failures || [])
                .map(function (f) {
                    return "<li>" + h(f) + "</li>";
                })
                .join("") +
            "</ul>";
        return (
            '<article class="mt-live-item mt-live-item--fail">' +
            '<div class="mt-live-item__head"><h4 class="mt-live-item__title">' +
            h(i.naam) +
            "</h4></div>" +
            reasons +
            "</article>"
        );
    }

    function setStatus(loading, err) {
        if (!statusEl) {
            return;
        }
        if (loading) {
            statusEl.textContent = "Bezig met bijwerken…";
            statusEl.classList.add("mt-live-status--busy");
            return;
        }
        statusEl.classList.remove("mt-live-status--busy");
        if (err) {
            statusEl.textContent = "Kon resultaat niet laden. Probeer opnieuw.";
            return;
        }
        statusEl.textContent = "Live bijgewerkt";
    }

    async function refresh() {
        if (abort) {
            abort.abort();
        }
        abort = new AbortController();
        setStatus(true, false);
        const fd = new FormData(form);
        const token = document.querySelector('meta[name="csrf-token"]');
        const headers = {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        };
        if (token) {
            headers["X-CSRF-TOKEN"] = token.getAttribute("content");
        }
        try {
            const res = await fetch(form.dataset.previewUrl, {
                method: "POST",
                body: fd,
                headers: headers,
                signal: abort.signal,
            });
            if (!res.ok) {
                throw new Error("HTTP " + res.status);
            }
            const data = await res.json();
            renderChips(data.filter_chips || []);
            renderMeta(data.meta || {});
            renderPass(data.items || []);
            renderFail(data.items || []);
            setStatus(false, false);
        } catch (e) {
            if (e.name === "AbortError") {
                return;
            }
            setStatus(false, true);
        }
    }

    function scheduleRefresh() {
        clearTimeout(timer);
        timer = setTimeout(refresh, debounceMs);
    }

    form.addEventListener("input", scheduleRefresh);
    form.addEventListener("change", scheduleRefresh);

    refresh();
})();
