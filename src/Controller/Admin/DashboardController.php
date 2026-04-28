<?php

namespace App\Controller\Admin;

use App\Entity\Article;
use App\Entity\Category;
use App\Entity\Episode;
use App\Entity\Subscriber;
use App\Entity\Tag;
use App\Entity\User;
use App\Repository\ArticleRepository;
use App\Repository\EpisodeRepository;
use App\Repository\SubscriberRepository;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly ArticleRepository $articleRepository,
        private readonly EpisodeRepository $episodeRepository,
        private readonly SubscriberRepository $subscriberRepository,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {}

    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        $activeAlert = $this->articleRepository->findFeaturedAlert();
        $latestArticles = $this->articleRepository->findBy([], ['createdAt' => 'DESC'], 5);
        $latestEpisodes = $this->episodeRepository->findBy([], ['createdAt' => 'DESC'], 5);

        // On précalcule les URLs d'édition — AdminUrlGenerator est stateful,
        // on évite les pièges en Twig.
        $articleEditUrls = [];
        foreach ($latestArticles as $a) {
            $articleEditUrls[$a->getId()] = $this->adminUrlGenerator
                ->setController(ArticleCrudController::class)
                ->setAction('edit')
                ->setEntityId($a->getId())
                ->generateUrl();
        }
        $episodeEditUrls = [];
        foreach ($latestEpisodes as $e) {
            $episodeEditUrls[$e->getId()] = $this->adminUrlGenerator
                ->setController(EpisodeCrudController::class)
                ->setAction('edit')
                ->setEntityId($e->getId())
                ->generateUrl();
        }

        return $this->render('admin/dashboard.html.twig', [
            'totals' => [
                'articles'    => $this->articleRepository->count([]),
                'published'   => $this->articleRepository->count(['status' => Article::STATUS_PUBLISHED]),
                'drafts'      => $this->articleRepository->count(['status' => Article::STATUS_DRAFT]),
                'scheduled'   => $this->articleRepository->count(['status' => Article::STATUS_SCHEDULED]),
                'episodes'    => $this->episodeRepository->count([]),
                'episodesPub' => $this->episodeRepository->count(['status' => Episode::STATUS_PUBLISHED]),
                'subscribers' => $this->subscriberRepository->countConfirmed(),
            ],
            'activeAlert'      => $activeAlert,
            'latestArticles'   => $latestArticles,
            'latestEpisodes'   => $latestEpisodes,
            'articleEditUrls'  => $articleEditUrls,
            'episodeEditUrls'  => $episodeEditUrls,
            'urlArticle'       => $this->adminUrlGenerator->setController(ArticleCrudController::class)->setAction('new')->generateUrl(),
            'urlEpisode'       => $this->adminUrlGenerator->setController(EpisodeCrudController::class)->setAction('new')->generateUrl(),
            'urlArticleCrud'   => $this->adminUrlGenerator->setController(ArticleCrudController::class)->setAction('index')->generateUrl(),
            'urlEpisodeCrud'   => $this->adminUrlGenerator->setController(EpisodeCrudController::class)->setAction('index')->generateUrl(),
            'urlEditAlert'     => $activeAlert
                ? $this->adminUrlGenerator->setController(ArticleCrudController::class)->setAction('edit')->setEntityId($activeAlert->getId())->generateUrl()
                : null,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Privaris <span class="text-xs font-normal opacity-70 ml-1">admin</span>')
            ->setFaviconPath('/favicon.ico')
            ->setTranslationDomain('admin')
            ->setTextDirection('ltr')
            ->renderContentMaximized()
            ;
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-gauge');

        yield MenuItem::section('Contenu');
        yield MenuItem::linkToCrud('Articles',    'fa fa-newspaper', Article::class);
        yield MenuItem::linkToCrud('Épisodes',    'fa fa-microphone', Episode::class);
        yield MenuItem::linkToCrud('Catégories',  'fa fa-folder',     Category::class);
        yield MenuItem::linkToCrud('Tags',        'fa fa-tags',       Tag::class);

        yield MenuItem::section('Audience');
        yield MenuItem::linkToCrud('Abonnés newsletter', 'fa fa-envelope', Subscriber::class);

        yield MenuItem::section('Administration');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);
        yield MenuItem::linkToUrl('Voir le site', 'fa fa-up-right-from-square', '/')
            ->setLinkTarget('_blank');
        yield MenuItem::linkToLogout('Se déconnecter', 'fa fa-sign-out');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        return parent::configureUserMenu($user)
            ->setName(method_exists($user, 'getDisplayName') && $user->getDisplayName() ? $user->getDisplayName() : $user->getUserIdentifier())
            ->displayUserAvatar(false);
    }
}
