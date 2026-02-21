(function () {
  // You can add/remove colors here
  const PALETTES = [
    { key: "blue",   name: "Blue",   accent: "#3b82f6", accent2: "#60a5fa" },
    { key: "purple", name: "Purple", accent: "#8b5cf6", accent2: "#a78bfa" },
    { key: "green",  name: "Green",  accent: "#22c55e", accent2: "#4ade80" },
    { key: "orange", name: "Orange", accent: "#f97316", accent2: "#fb923c" },
    { key: "pink",   name: "Pink",   accent: "#ec4899", accent2: "#f472b6" },
    { key: "teal",   name: "Teal",   accent: "#14b8a6", accent2: "#2dd4bf" },
    { key: "red",    name: "Red",    accent: "#ef4444", accent2: "#f87171" },
    { key: "slate",  name: "Slate",  accent: "#64748b", accent2: "#94a3b8" },
  ];

  function applyPalette(p) {
    if (!p) return;
    document.documentElement.style.setProperty("--accent", p.accent);
    document.documentElement.style.setProperty("--accent2", p.accent2);
    document.documentElement.setAttribute("data-accent", p.key);
  }

  function getSavedPaletteKey() {
    return localStorage.getItem("accent_palette") || "blue";
  }

  function setSavedPaletteKey(key) {
    localStorage.setItem("accent_palette", key);
  }

  function buildPaletteUI(container) {
    // container is optional; if missing, no UI is shown (still supports applying palette)
    if (!container) return;

    container.innerHTML = "";

    const current = getSavedPaletteKey();

    PALETTES.forEach(p => {
      const b = document.createElement("button");
      b.type = "button";
      b.className = "palette-dot";
      b.title = p.name;
      b.setAttribute("aria-label", p.name);
      b.dataset.key = p.key;

      // background preview (gradient)
      b.style.background = `linear-gradient(90deg, ${p.accent}, ${p.accent2})`;

      if (p.key === current) b.classList.add("active");

      b.addEventListener("click", () => {
        document.querySelectorAll(".palette-dot").forEach(x => x.classList.remove("active"));
        b.classList.add("active");

        setSavedPaletteKey(p.key);
        applyPalette(p);

        // optional: notify backend if you add saving for logged-in/admin later
        // window.dispatchEvent(new CustomEvent("paletteChanged", { detail: p }));
      });

      container.appendChild(b);
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    // apply saved palette
    const key = getSavedPaletteKey();
    const p = PALETTES.find(x => x.key === key) || PALETTES[0];
    applyPalette(p);

    // if you place <div id="paletteBar"></div> somewhere, it will render dots
    buildPaletteUI(document.getElementById("paletteBar"));
  });
})();
