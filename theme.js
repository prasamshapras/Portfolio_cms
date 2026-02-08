(function () {
  function getTheme() {
    const t = localStorage.getItem("theme");
    if (t === "light" || t === "dark") return t;
    return (window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches) ? "dark" : "light";
  }

  function setTheme(t) {
    document.documentElement.setAttribute("data-theme", t);
    localStorage.setItem("theme", t);
    document.querySelectorAll(".theme-label").forEach(el => {
      el.textContent = (t === "dark") ? "Dark" : "Light";
    });
  }

  document.addEventListener("DOMContentLoaded", () => {
    setTheme(getTheme());
    const btn = document.getElementById("themeToggle");
    if (btn) {
      btn.addEventListener("click", () => {
        const cur = document.documentElement.getAttribute("data-theme") || "dark";
        setTheme(cur === "dark" ? "light" : "dark");
      });
    }
  });
})();
