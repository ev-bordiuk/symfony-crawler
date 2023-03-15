<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Result;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\Turbo\TurboBundle;

class ResultsController extends AbstractController
{
    #[Route('/results', name: 'app_results')]
    public function index(EntityManagerInterface $entityManager, Request $request): Response
    {
        $sort  = $request->query->get('sort', 'images_total');
        $order = $request->query->get('order', 'DESC');

        $results = $entityManager->getRepository(Result::class)->orderBy($sort, $order);

        return $this->render('results/index.html.twig', [
            'results' => $results,
            'sort' => $sort,
            'order' => $order
        ]);
    }
}
