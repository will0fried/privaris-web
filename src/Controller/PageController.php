<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\EpisodeRepository;
use App\Repository\SubscriberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PageController extends AbstractController
{
    #[Route('/a-propos', name: 'app_about')]
    public function about(
        ArticleRepository $articles,
        EpisodeRepository $episodes,
        ?SubscriberRepository $subscribers = null,
    ): Response {
        $articleCount = $articles->countPublished();
        $episodeCount = $episodes->countPublished();

        // Date de démarrage = min(first article, first episode), fallback date du projet
        $firstArticle = $articles->findFirstPublishedAt();
        $firstEpisode = $episodes->findFirstPublishedAt();
        $firstDate = null;
        foreach ([$firstArticle, $firstEpisode] as $d) {
            if ($d !== null && ($firstDate === null || $d < $firstDate)) {
                $firstDate = $d;
            }
        }

        // Subscribers optionnel (le repo peut ne pas exposer de count confirmé)
        $subscriberCount = 0;
        if ($subscribers !== null && method_exists($subscribers, 'countConfirmed')) {
            $subscriberCount = (int) $subscribers->countConfirmed();
        }

        return $this->render('pages/about.html.twig', [
            'stats' => [
                'articles' => $articleCount,
                'episodes' => $episodeCount,
                'firstDate' => $firstDate,
                'subscribers' => $subscriberCount,
            ],
        ]);
    }

    #[Route('/mentions-legales', name: 'app_legal')]
    public function legal(): Response
    {
        return $this->render('pages/legal.html.twig');
    }

    #[Route('/confidentialite', name: 'app_privacy')]
    public function privacy(): Response
    {
        return $this->render('pages/privacy.html.twig');
    }
}
