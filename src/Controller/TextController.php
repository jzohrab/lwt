<?php

namespace App\Controller;

use App\Entity\Text;
use App\Form\TextType;
use App\Repository\TextRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/text')]
class TextController extends AbstractController
{
    #[Route('/', name: 'app_text_index', methods: ['GET'])]
    public function index(TextRepository $textRepository): Response
    {
        return $this->render('text/index.html.twig', [
            'texts' => $textRepository->findAllWithStats()
        ]);
    }

    #[Route('/new', name: 'app_text_new', methods: ['GET', 'POST'])]
    public function new(Request $request, TextRepository $textRepository): Response
    {
        $text = new Text();
        $form = $this->createForm(TextType::class, $text);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $textRepository->save($text, true);

            return $this->redirectToRoute('app_text_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('text/new.html.twig', [
            'text' => $text,
            'form' => $form,
        ]);
    }

    #[Route('/{TxID}', name: 'app_text_show', methods: ['GET'])]
    public function show(Text $text): Response
    {
        return $this->render('text/show.html.twig', [
            'text' => $text,
        ]);
    }

    #[Route('/{TxID}/edit', name: 'app_text_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Text $text, TextRepository $textRepository): Response
    {
        $form = $this->createForm(TextType::class, $text);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $textRepository->save($text, true);

            return $this->redirectToRoute('app_text_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('text/edit.html.twig', [
            'text' => $text,
            'form' => $form,
        ]);
    }

    #[Route('/{TxID}', name: 'app_text_delete', methods: ['POST'])]
    public function delete(Request $request, Text $text, TextRepository $textRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$text->getTxID(), $request->request->get('_token'))) {
            $textRepository->remove($text, true);
        }

        return $this->redirectToRoute('app_text_index', [], Response::HTTP_SEE_OTHER);
    }
}
