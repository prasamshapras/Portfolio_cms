(function () {
  function getTheme() {
    const t = localStorage.getItem("theme");
    if (t === "light" || t === "dark") return t;
    return (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches)
      ? "dark"
      : "light";
  }

  function setTheme(t) {
    document.documentElement.setAttribute("data-theme", t);
    localStorage.setItem("theme", t);
    document.querySelectorAll(".theme-label").forEach(el => {
      el.textContent = (t === "dark") ? "Dark" : "Light";
    });
  }

  function initThemeToggle() {
    // Apply theme immediately
    setTheme(getTheme());

    // Attach click handler (dashboard + index)
    const btn = document.getElementById("themeToggle");
    if (!btn) return;

    // avoid double binding
    if (btn.dataset.bound === "1") return;
    btn.dataset.bound = "1";

    btn.addEventListener("click", () => {
      const cur = document.documentElement.getAttribute("data-theme") || "dark";
      setTheme(cur === "dark" ? "light" : "dark");
    });
  }

  // Run now if DOM already loaded, otherwise wait
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initThemeToggle);
  } else {
    initThemeToggle();
  }
})();
