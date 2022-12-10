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
            'status' => 'Active',
            'texts' => $textRepository->findAllWithStats(false)
        ]);
    }

    #[Route('/archived', name: 'app_text_archived', methods: ['GET'])]
    public function archived(TextRepository $textRepository): Response
    {
        return $this->render('text/index.html.twig', [
            'status' => 'Archived',
            'texts' => $textRepository->findAllWithStats(true)
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

    #[Route('/{TxID}/delete', name: 'app_text_delete', methods: ['POST'])]
    public function delete(Request $request, Text $text, TextRepository $textRepository): Response
    {
        $tok = $request->request->get('_token');
        if ($this->isCsrfTokenValid('delete'.$text->getID(), $tok)) {
            $textRepository->remove($text, true);
        }
        return $this->redirectToRoute('app_text_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{TxID}/archive', name: 'app_text_archive', methods: ['POST'])]
    public function archive(Request $request, Text $text, TextRepository $textRepository): Response
    {
        $tok = $request->request->get('_token');
        if ($this->isCsrfTokenValid('archive'.$text->getID(), $tok)) {
            $text->setArchived(true);
            $textRepository->save($text, true);
        }
        return $this->redirectToRoute('app_text_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{TxID}/unarchive', name: 'app_text_unarchive', methods: ['POST'])]
    public function unarchive(Request $request, Text $text, TextRepository $textRepository): Response
    {
        $tok = $request->request->get('_token');
        if ($this->isCsrfTokenValid('unarchive'.$text->getID(), $tok)) {
            $text->setArchived(false);
            $textRepository->save($text, true);
        }
        return $this->redirectToRoute('app_text_index', [], Response::HTTP_SEE_OTHER);
    }

}
