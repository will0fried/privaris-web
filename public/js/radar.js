/* ============================================================
   Le radar — logique d'affichage et d'interaction.
   Lit window.EPISODES (voir episodes.js). Pas besoin d'y toucher
   pour publier ; seulement pour faire évoluer le comportement.
   ============================================================ */
(function () {
  "use strict";
  var PERIOD = 6;   // durée d'un tour de balayage (doit matcher l'animation CSS)
  var RMAX = 42;    // rayon max (% du scope) où poser les blips
  var episodes = window.EPISODES || [];

  var scope = document.getElementById("scope");
  var fiche = document.getElementById("fiche");
  if (!scope || !fiche) { return; }

  function escapeAttr(s) { return String(s).replace(/"/g, "&quot;"); }

  function place(m) {
    var rad = m.a * Math.PI / 180;
    var x = 50 + m.r * RMAX * Math.sin(rad);
    var y = 50 - m.r * RMAX * Math.cos(rad);

    var b = document.createElement("button");
    b.type = "button";
    b.className = "blip" + (m.st === "live" ? " live" : "") + (m.st === "mystery" ? " mystery" : "");
    b.style.left = x + "%";
    b.style.top = y + "%";
    b.style.setProperty("--delay", ((m.a / 360) * PERIOD).toFixed(2) + "s");
    b.setAttribute("aria-label", m.st === "mystery" ? "Signal non identifié" : (m.code + " — " + m.t));

    var lab = document.createElement("span");
    lab.className = "lab";
    lab.style.left = x + "%";
    lab.style.top = y + "%";
    var stCls = m.st === "live" ? "st" : "st " + (m.st === "mystery" ? "myst" : "soon");
    var stTxt = m.st === "live" ? "● en ligne" : (m.st === "mystery" ? "◌ inconnu" : "○ à venir");
    lab.innerHTML = '<span class="' + stCls + '">' + stTxt + "</span> · <b>" +
      (m.st === "mystery" ? "????" : m.code) + "</b>" + (m.st === "mystery" ? "" : " — " + m.t);

    function show() { lab.classList.add("show"); }
    function hide() { lab.classList.remove("show"); }
    b.addEventListener("mouseenter", show);
    b.addEventListener("mouseleave", hide);
    b.addEventListener("focus", show);
    b.addEventListener("blur", hide);
    b.addEventListener("click", function () { select(m, b); });

    scope.appendChild(b);
    scope.appendChild(lab);
    m._el = b;
  }

  function select(m, b) {
    var prev = scope.querySelectorAll(".blip.active");
    for (var i = 0; i < prev.length; i++) { prev[i].classList.remove("active"); }
    if (b) { b.classList.add("active"); }
    render(m);
    document.getElementById("carnet").scrollIntoView({ behavior: "smooth", block: "center" });
  }

  function render(m) {
    if (m.st === "live") {
      fiche.innerHTML =
        '<div class="fiche__head"><span class="no">' + (m.code.replace("S01E", "MANIP N°")) +
        '</span><span>réf. PRV-' + m.code.replace("S01E", "S01-") + '</span>' +
        '<span class="state live">● EN LIGNE</span></div>' +
        '<div class="fiche__body"><h3>' + m.t + "</h3>" +
        '<div class="row"><div class="k">Objectif</div><div class="v">' + (m.o || "") + "</div></div>" +
        '<div class="row"><div class="k">Protocole</div><div class="v">' + (m.p || "") + "</div></div>" +
        '<div class="row"><div class="k">Observations</div><div class="v">' + (m.ob || "") + "</div></div>" +
        '<div class="row"><div class="k">Ce que j\'en retiens</div><div class="v">' + (m.cq || "") + "</div></div></div>";
    } else {
      var head = m.st === "mystery" ? "SIGNAL ????" : m.code.replace("S01E", "MANIP N°");
      var stTxt = m.st === "mystery" ? "◌ NON IDENTIFIÉ" : "○ À VENIR";
      var kick = m.st === "mystery" ? "détection en cours" : "au programme";
      fiche.innerHTML =
        '<div class="fiche__head"><span class="no">' + head + '</span>' +
        '<span class="state soon">' + stTxt + "</span></div>" +
        '<div class="fiche__body"><h3>' + m.t + "</h3>" +
        '<p class="teaser"><span class="soonk">' + kick + "</span>" + (m.ts || "") + "</p></div>";
    }
  }

  // Construire le radar
  for (var i = 0; i < episodes.length; i++) { place(episodes[i]); }

  // Ouvrir le premier "live" par défaut (sinon le premier épisode)
  var first = episodes.filter(function (e) { return e.st === "live"; })[0] || episodes[0];
  if (first) { render(first); if (first._el) { first._el.classList.add("active"); } }
})();
