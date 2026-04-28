<?php

namespace App\Repository;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Article> */
class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function publishedQb(): QueryBuilder
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->andWhere('a.publishedAt <= :now')
            ->setParameter('status', Article::STATUS_PUBLISHED)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('a.publishedAt', 'DESC');
    }

    /** @return Article[] */
    public function findLatestPublished(int $limit = 10): array
    {
        return $this->publishedQb()->setMaxResults($limit)->getQuery()->getResult();
    }

    public function countPublished(): int
    {
        return (int) $this->publishedQb()
            ->select('COUNT(a.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findFirstPublishedAt(): ?\DateTimeInterface
    {
        $result = $this->publishedQb()
            ->select('MIN(a.publishedAt)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? new \DateTimeImmutable($result) : null;
    }

    public function findFeaturedAlert(): ?Article
    {
        return $this->publishedQb()
            ->andWhere('a.alert = true')
            ->setMaxResults(1)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * Récupère les articles marqués "À la une" (hors alerte).
     * L'alerte est déjà gérée par findFeaturedAlert() et n'a rien à faire dans La Une.
     *
     * @return Article[]
     */
    public function findFeaturedPublished(int $limit = 1): array
    {
        return $this->publishedQb()
            ->andWhere('a.featured = true')
            ->andWhere('a.alert = false')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /**
     * Récupère toutes les alertes en cours (articles avec alert=true).
     * Limite implicite pour éviter une sidebar à rallonge.
     *
     * @return Article[]
     */
    public function findAlerts(int $limit = 3): array
    {
        return $this->publishedQb()
            ->andWhere('a.alert = true')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /**
     * Désactive "À la une" sur tous les articles sauf celui passé en paramètre.
     * Utilisé quand l'admin coche "featured" pour garantir l'unicité de la Une.
     *
     * @return int Nombre d'articles modifiés
     */
    public function demoteOtherFeatured(?int $keepId): int
    {
        $qb = $this->createQueryBuilder('a')
            ->update()
            ->set('a.featured', ':false')
            ->where('a.featured = :true')
            ->setParameter('false', false)
            ->setParameter('true', true);

        if ($keepId !== null) {
            $qb->andWhere('a.id != :keep')
               ->setParameter('keep', $keepId);
        }

        return (int) $qb->getQuery()->execute();
    }

    /**
     * Récupère les articles récents pour le rail "À lire aussi".
     *
     * Filtre :
     *   - publiés (via publishedQb)
     *   - hors IDs explicitement exclus (typiquement la Une, déjà affichée en gros
     *     tout en haut de la home — on évite juste ce gros doublon visuel).
     *
     * Les articles en alerte (alert=true) NE SONT PAS filtrés : ils apparaissent
     * dans la sidebar Signal ET dans ce rail. La sidebar limite le visuel à 3 max,
     * mais un article d'alerte reste visible dans le flux normal comme n'importe
     * quel autre article publié. Choix édito : l'alerte est une info, pas une
     * raison de cacher l'article.
     *
     * @param int[] $excludeIds
     * @return Article[]
     */
    public function findLatestExcluding(array $excludeIds, int $limit = 12): array
    {
        $qb = $this->publishedQb();
        if ($excludeIds !== []) {
            $qb->andWhere('a.id NOT IN (:excluded)')
               ->setParameter('excluded', $excludeIds);
        }
        return $qb->setMaxResults($limit)->getQuery()->getResult();
    }

    /** @return Article[] */
    public function findByCategory(Category $category, int $limit = 24): array
    {
        return $this->publishedQb()
            ->andWhere('a.category = :cat')
            ->setParameter('cat', $category)
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    /** @return Article[] */
    public function findByTag(Tag $tag, int $limit = 24): array
    {
        return $this->publishedQb()
            ->innerJoin('a.tags', 't')
            ->andWhere('t = :tag')
            ->setParameter('tag', $tag)
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    public function findOneBySlugPublished(string $slug): ?Article
    {
        return $this->publishedQb()
            ->andWhere('a.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * Pagination. Renvoie un tableau [items, total, page, pages, perPage].
     *
     * Le tri DESC sur publishedAt est conservé. On s'appuie sur Doctrine\Paginator
     * pour compter correctement avec les jointures (categ/tag).
     */
    /**
     * Recherche simple sur titre, résumé et contenu.
     *
     * Approche pragmatique :
     *   - LIKE %query% sur 3 champs (title, excerpt, content) avec OR
     *   - Articles publiés uniquement, triés par date desc
     *   - Pas d'index FULLTEXT pour l'instant : volumes faibles, gain mesurable
     *     seulement au-delà de ~10k articles. Scoring de pertinence à voir
     *     plus tard (CASE WHEN sur les matches titre vs corps).
     *
     * Renvoie un array [items, total, page, pages, perPage, query] aligné avec
     * paginatePublished() pour pouvoir réutiliser le composant pagination.
     */
    public function search(string $query, int $page = 1, int $perPage = 12): array
    {
        $page    = max(1, $page);
        $perPage = max(1, $perPage);
        $query   = trim($query);

        if ($query === '') {
            return ['items' => [], 'total' => 0, 'page' => 1, 'pages' => 1, 'perPage' => $perPage, 'query' => ''];
        }

        // Échappe les wildcards SQL pour éviter qu'un % saisi devienne un joker.
        $escaped = addcslashes($query, '%_\\');
        $like    = '%' . $escaped . '%';

        $qb = $this->publishedQb()
            ->andWhere('a.title LIKE :q OR a.excerpt LIKE :q OR a.content LIKE :q')
            ->setParameter('q', $like)
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage);

        $paginator = new Paginator($qb->getQuery(), fetchJoinCollection: true);
        $total     = \count($paginator);
        $pages     = (int) max(1, ceil($total / $perPage));

        return [
            'items'   => iterator_to_array($paginator->getIterator()),
            'total'   => $total,
            'page'    => min($page, $pages),
            'pages'   => $pages,
            'perPage' => $perPage,
            'query'   => $query,
        ];
    }

    public function paginatePublished(int $page, int $perPage = 12, ?Category $category = null, ?Tag $tag = null): array
    {
        $page    = max(1, $page);
        $perPage = max(1, $perPage);

        $qb = $this->publishedQb();
        if ($category !== null) {
            $qb->andWhere('a.category = :cat')->setParameter('cat', $category);
        }
        if ($tag !== null) {
            $qb->innerJoin('a.tags', 't')
               ->andWhere('t = :tag')
               ->setParameter('tag', $tag);
        }

        $qb->setFirstResult(($page - 1) * $perPage)
           ->setMaxResults($perPage);

        $paginator = new Paginator($qb->getQuery(), fetchJoinCollection: true);
        $total     = \count($paginator);
        $pages     = (int) max(1, ceil($total / $perPage));

        return [
            'items'   => iterator_to_array($paginator->getIterator()),
            'total'   => $total,
            'page'    => min($page, $pages),
            'pages'   => $pages,
            'perPage' => $perPage,
        ];
    }
}
