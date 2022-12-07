<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\BlargRepository;

class BlargController extends AbstractController
{
    #[Route('/blarg', name: 'app_blarg')]
    public function index(BlargRepository $blargRepository): Response
    {
        $blargs = $blargRepository->findAllWithExtra();
        return $this->render('blarg/index.html.twig', [
            'controller_name' => 'BlargController',
            'blargs' => $blargs
        ]);
    }
}
