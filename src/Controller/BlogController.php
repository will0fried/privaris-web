<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use App\Repository\CategoryRepository;
use App\Repository\TagRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/articles', name: 'app_blog_')]
class BlogController extends AbstractController
{
    /** Nombre d'articles affichés par page de listing. */
    private const PER_PAGE = 12;

    #[Route('', name: 'index')]
    public function index(Request $request, ArticleRepository $articles, CategoryRepository $categories): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $pagination = $articles->paginatePublished($page, self::PER_PAGE);

        return $this->render('blog/index.html.twig', [
            'articles'   => $pagination['items'],
            'pagination' => $pagination,
            'categories' => $categories->findAllOrdered(),
        ]);
    }

    #[Route('/categorie/{slug}', name: 'category', requirements: ['slug' => '[a-z0-9-]+'])]
    public function category(string $slug, Request $request, ArticleRepository $articles, CategoryRepository $categories): Response
    {
        $category = $categories->findOneBy(['slug' => $slug]);
        if (!$category) {
            throw $this->createNotFoundException();
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $pagination = $articles->paginatePublished($page, self::PER_PAGE, category: $category);

        return $this->render('blog/index.html.twig', [
            'articles'       => $pagination['items'],
            'pagination'     => $pagination,
            'categories'     => $categories->findAllOrdered(),
            'activeCategory' => $category,
        ]);
    }

    #[Route('/tag/{slug}', name: 'tag', requirements: ['slug' => '[a-z0-9-]+'])]
    public function tag(string $slug, Request $request, ArticleRepository $articles, CategoryRepository $categories, TagRepository $tags): Response
    {
        $tag = $tags->findOneBy(['slug' => $slug]);
        if (!$tag) {
            throw $this->createNotFoundException();
        }

        $page = max(1, (int) $request->query->get('page', 1));
        $pagination = $articles->paginatePublished($page, self::PER_PAGE, tag: $tag);

        return $this->render('blog/index.html.twig', [
            'articles'   => $pagination['items'],
            'pagination' => $pagination,
            'categories' => $categories->findAllOrdered(),
            'activeTag'  => $tag,
        ]);
    }

    #[Route('/{slug}', name: 'show', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug, ArticleRepository $articles): Response
    {
        $article = $articles->findOneBySlugPublished($slug);
        if (!$article) {
            throw $this->createNotFoundException();
        }

        return $this->render('blog/show.html.twig', [
            'article' => $article,
            'related' => $articles->findLatestPublished(3),
        ]);
    }
}
