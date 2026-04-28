<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\EpisodeRepository;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'app_sitemap', defaults: ['_format' => 'xml'])]
    public function index(
        ArticleRepository $articles,
        EpisodeRepository $episodes,
        CategoryRepository $categories,
        TagRepository $tags,
    ): Response {
        $response = $this->render('sitemap/index.xml.twig', [
            'articles'   => $articles->findLatestPublished(500),
            'episodes'   => $episodes->findAllPublished(),
            'categories' => $categories->findAll(),
            'tags'       => $tags->findAll(),
        ]);
        $response->headers->set('Content-Type', 'application/xml; charset=utf-8');
        return $response;
    }
}
