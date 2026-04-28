<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\EpisodeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        ArticleRepository $articles,
        EpisodeRepository $episodes,
        CategoryRepository $categories,
    ): Response {
        // Sidebar Signal — UNIQUEMENT les articles marqués "alert=true".
        // La sidebar s'affiche toujours : si pas d'alerte, état neutre en vue.
        $alerts = $articles->findAlerts(3);

        // La Une = l'article marqué "featured" par la rédaction (hors alerte).
        // Si aucun article n'est marqué, on prend le dernier publié (non-alerte)
        // en fallback pour que la home ne soit jamais vide.
        $featuredList = $articles->findFeaturedPublished(1);
        $featured = $featuredList[0] ?? null;

        // Rail "À lire aussi" — TOUS les articles publiés récents, alerte incluse.
        // La sidebar Signal a un max visuel de 3, mais rien n'empêche l'article
        // d'apparaître aussi dans le rail : il reste du contenu normal à lire.
        // On exclut juste la Une pour éviter le gros doublon visuel en haut.
        $excluded = $featured ? [$featured->getId()] : [];
        $rest = $articles->findLatestExcluding($excluded, 12);

        // Fallback : pas de featured explicite → on promeut le premier "rest"
        // en Une (= le dernier article publié — alerte comprise).
        if ($featured === null && $rest !== []) {
            $featured = array_shift($rest);
        }

        return $this->render('home/index.html.twig', [
            'alerts'     => $alerts,
            'featured'   => $featured,
            'rest'       => $rest,
            'episodes'   => $episodes->findLatestPublished(4),
            'categories' => $categories->findAllOrdered(),
        ]);
    }
}
