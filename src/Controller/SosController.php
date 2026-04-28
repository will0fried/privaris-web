<?php

namespace App\Controller;

use App\Service\SosCatalog;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Pages SOS — un guide d'action pour les personnes en panique.
 *
 * Le contenu est servi par le catalogue (App\Service\SosCatalog) qui le tient
 * en code. Ces pages doivent rester ultra-stables et faciles à trouver depuis
 * la home : c'est exactement le moment où quelqu'un tape « privaris » dans
 * Google après avoir cliqué sur une arnaque.
 */
#[Route('/sos', name: 'app_sos_')]
class SosController extends AbstractController
{
    public function __construct(private readonly SosCatalog $catalog) {}

    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->render('sos/index.html.twig', [
            'scenarios' => $this->catalog->all(),
        ]);
    }

    #[Route('/{slug}', name: 'show', requirements: ['slug' => '[a-z0-9-]+'])]
    public function show(string $slug): Response
    {
        $scenario = $this->catalog->findBySlug($slug);
        if ($scenario === null) {
            throw $this->createNotFoundException();
        }

        return $this->render('sos/show.html.twig', [
            'scenario' => $scenario,
            'related'  => $this->catalog->relatedTo($scenario),
        ]);
    }
}
