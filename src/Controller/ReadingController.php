<?php

namespace App\Controller;

use App\Repository\TextRepository;
use App\Entity\Text;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/read')]
class ReadingController extends AbstractController
{

    #[Route('/{TxID}', name: 'app_read', methods: ['GET'])]
    public function read(Request $request, Text $text, TextRepository $textRepository): Response
    {
        $sentences = $textRepository->getSentences($text);
        return $this->render('read/index.html.twig', [
            'sentences' => $sentences
        ]);
    }

}
