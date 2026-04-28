<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    /** Longueur max d'une requête (anti-abus, anti-flood logs). */
    private const MAX_QUERY_LENGTH = 100;

    /** Nombre min de caractères pour déclencher la recherche. */
    private const MIN_QUERY_LENGTH = 2;

    /** Nombre de résultats par page. */
    private const PER_PAGE = 12;

    #[Route('/recherche', name: 'app_search')]
    public function index(Request $request, ArticleRepository $articles): Response
    {
        $rawQuery = (string) $request->query->get('q', '');
        $query    = mb_substr(trim($rawQuery), 0, self::MAX_QUERY_LENGTH);
        $page     = max(1, (int) $request->query->get('page', 1));

        $hasQuery   = mb_strlen($query) >= self::MIN_QUERY_LENGTH;
        $pagination = $hasQuery
            ? $articles->search($query, $page, self::PER_PAGE)
            : ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'perPage' => self::PER_PAGE, 'query' => $query];

        $response = $this->render('search/index.html.twig', [
            'query'      => $query,
            'hasQuery'   => $hasQuery,
            'tooShort'   => $rawQuery !== '' && !$hasQuery,
            'minLength'  => self::MIN_QUERY_LENGTH,
            'articles'   => $pagination['items'],
            'pagination' => $pagination,
        ]);

        // Pas d'indexation des pages de recherche (contenu duplicate, query strings)
        $response->headers->set('X-Robots-Tag', 'noindex, follow');

        return $response;
    }
}
