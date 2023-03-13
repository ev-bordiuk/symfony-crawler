<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ResultsController extends AbstractController
{
    #[Route('/results', name: 'app_results')]
    public function index(): Response
    {
        return $this->render('results/index.html.twig', [
            'controller_name' => 'ResultsController',
        ]);
    }
}
