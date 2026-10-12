/* ---------------------------------------------------------
   Lightbox : agrandit une image en plein écran au clic.
   S'applique à tout élément portant la classe "zoomable"
   (une <img>, ou un bouton contenant une <img>).
   Délégation d'événement : fonctionne aussi pour les images
   ajoutées après coup.

   Extension pour les annonces : les éléments qui partagent le
   même data-group forment un diaporama (flèches ‹ › affichées
   seulement s'il y a plusieurs photos). data-full permet de
   charger une version plus grande que la miniature.
--------------------------------------------------------- */
function initLightbox() {
  if (window.__lightboxReady) return;   // évite un double chargement du script
  window.__lightboxReady = true;

  // Overlay créé une seule fois, réutilisé pour toutes les images
  const overlay = document.createElement("div");
  overlay.className = "lightbox";
  overlay.setAttribute("role", "dialog");
  overlay.setAttribute("aria-modal", "true");
  overlay.innerHTML = `
    <button class="lightbox-close" aria-label="Fermer">✕</button>
    <button class="lightbox-nav lightbox-prev" aria-label="Photo précédente">‹</button>
    <img src="" alt="">
    <button class="lightbox-nav lightbox-next" aria-label="Photo suivante">›</button>
  `;
  document.body.appendChild(overlay);
  const imgEl = overlay.querySelector("img");
  const closeBtn = overlay.querySelector(".lightbox-close");

  let photos = [];   // [{ src, alt }]
  let index = 0;
  let opener = null;

  function dataOf(el) {
    const inner = el.tagName === "IMG" ? el : el.querySelector("img");
    return {
      src: el.dataset.full || (inner && inner.currentSrc) || (inner && inner.src) || "",
      alt: el.dataset.alt || (inner && inner.alt) || "",
    };
  }

  function show(i) {
    index = (i + photos.length) % photos.length;
    imgEl.src = photos[index].src;
    imgEl.alt = photos[index].alt;
  }

  function open(el) {
    const group = el.dataset.group;
    const members = group
      ? [...document.querySelectorAll(".zoomable")].filter((x) => x.dataset.group === group)
      : [el];
    photos = members.map(dataOf);
    overlay.classList.toggle("single", photos.length < 2);
    opener = el;
    show(members.indexOf(el));
    overlay.classList.add("is-open");
    document.body.style.overflow = "hidden";
    closeBtn.focus();
  }

  function close() {
    overlay.classList.remove("is-open");
    document.body.style.overflow = "";
    if (opener && opener.offsetParent !== null) opener.focus();
  }

  // Délégation d'événement : fonctionne même sur les images ajoutées après coup
  document.addEventListener("click", (e) => {
    const el = e.target.closest(".zoomable");
    if (el) { e.preventDefault(); open(el); }
  });

  closeBtn.addEventListener("click", close);
  overlay.querySelector(".lightbox-prev").addEventListener("click", () => show(index - 1));
  overlay.querySelector(".lightbox-next").addEventListener("click", () => show(index + 1));
  overlay.addEventListener("click", (e) => { if (e.target === overlay) close(); });

  document.addEventListener("keydown", (e) => {
    if (!overlay.classList.contains("is-open")) return;
    if (e.key === "Escape") close();
    else if (e.key === "ArrowRight" && photos.length > 1) show(index + 1);
    else if (e.key === "ArrowLeft" && photos.length > 1) show(index - 1);
  });

  // Balayage gauche/droite sur mobile
  let startX = null;
  overlay.addEventListener("touchstart", (e) => { startX = e.touches.length === 1 ? e.touches[0].clientX : null; }, { passive: true });
  overlay.addEventListener("touchend", (e) => {
    if (startX === null || photos.length < 2) return;
    const dx = e.changedTouches[0].clientX - startX;
    if (Math.abs(dx) > 60) show(index + (dx < 0 ? 1 : -1));
    startX = null;
  });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initLightbox);
} else {
  initLightbox();
}
