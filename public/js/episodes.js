/* ============================================================
   LES ÉPISODES  —  c'est LE seul fichier à toucher pour publier.
   Ajoute un objet, enregistre : le radar ET le carnet se mettent à jour.

   Champs :
     code   : "S01E05" (affiché). Mets "????" pour un signal mystère.
     t      : le titre.
     st     : "live"  = publié (point rouge, près du centre)
              "soon"  = à venir (point bleu)
              "mystery" = signal non identifié (pointillés, au bord)
     r      : distance au centre, de 0 (tôt) à 1 (tard).  Repère visuel du calendrier.
     a      : angle en degrés (0 = haut, sens horaire). Juste pour répartir joliment.

     Si st = "live" → remplis o / p / ob / cq (les 4 champs du carnet).
     Sinon          → remplis ts (le teaser "au programme").
     Le HTML simple (gras) est autorisé dans les textes via <b>…</b>.
   ============================================================ */
window.EPISODES = [
  {
    code:"S01E00", t:"On allume le radar", st:"live", r:.18, a:90,
    o:"Apprendre la cyber <b>pour de vrai</b> — en cassant et en défendant, pas en recopiant des cours. En public, à découvert.",
    p:"Un carnet, une manip à la fois : hypothèse, test sur mon lab, observations brutes, leçon. Daté, signé, rien de caché.",
    ob:"Pour l'instant : une page propre et un radar qui s'allume. On part de zéro — c'est <b>exactement</b> l'intérêt.",
    cq:"Pas besoin d'avoir l'air d'un expert. Juste de montrer le vrai chemin, plantages compris. <b>Rendez-vous dimanche.</b>"
  },
  {
    code:"S01E01", t:"Premier contact : je scanne mon propre réseau", st:"soon", r:.34, a:205,
    ts:"Découvrir ce qui tourne vraiment chez moi : nmap, les ports ouverts, ce que ma box raconte au monde. La première lumière dans le noir."
  },
  {
    code:"S01E02", t:"Un mini-PC à 120 € devient mon labo", st:"soon", r:.5, a:330,
    ts:"Transformer une brouette d'occasion en hyperviseur. La fondation : tout casser sans jamais toucher à mon vrai ordi."
  },
  {
    code:"S01E03", t:"Ma première machine (volontairement) vulnérable", st:"soon", r:.66, a:50,
    ts:"Déployer une cible faillible, l'isoler, la cartographier. Le terrain de jeu de la première vraie attaque."
  },
  {
    code:"S01E04", t:"Je m'attaque moi-même", st:"soon", r:.82, a:160,
    ts:"Lancer l'attaque depuis Kali, puis changer de casquette et lire ce que ça laisse dans les logs. Les deux faces de la pièce."
  },
  {
    code:"????", t:"Signal non identifié", st:"mystery", r:.95, a:280,
    ts:"Détection en cours… Ce signal-là, je ne peux pas encore te dire ce que c'est. Reviens."
  }
];
