<?php

namespace App\Controller;

use App\Repository\EpisodeRepository;
use App\Service\PodcastFeedGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/podcast', name: 'app_podcast_')]
class PodcastController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(EpisodeRepository $episodes): Response
    {
        return $this->render('podcast/index.html.twig', [
            'episodes' => $episodes->findAllPublished(),
        ]);
    }

    #[Route('/{slug}', name: 'show', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug, EpisodeRepository $episodes): Response
    {
        $episode = $episodes->findOneBySlugPublished($slug);
        if (!$episode) {
            throw $this->createNotFoundException();
        }

        return $this->render('podcast/show.html.twig', [
            'episode' => $episode,
            'related' => $episodes->findLatestPublished(4),
        ]);
    }

    #[Route('/feed.xml', name: 'feed', defaults: ['_format' => 'xml'])]
    public function feed(EpisodeRepository $episodes, PodcastFeedGenerator $generator): Response
    {
        $xml = $generator->generate($episodes->findAllPublished());

        $response = new Response($xml);
        $response->headers->set('Content-Type', 'application/rss+xml; charset=utf-8');
        $response->setPublic();
        $response->setMaxAge(3600);
        return $response;
    }
}
