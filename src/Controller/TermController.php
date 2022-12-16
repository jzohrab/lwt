<?php

namespace App\Controller;

use App\Entity\Term;
use App\Form\TermType;
use App\Repository\TermRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;


#[Route('/term')]
class TermController extends AbstractController
{
    #[Route('/', name: 'app_term_index', methods: ['GET'])]
    public function index(TermRepository $termRepository): Response
    {
        return $this->render('term/index.html.twig');
    }

    #[Route('/datatables', name: 'app_term_datatables', methods: ['POST'])]
    public function datatables_source(Request $request, TermRepository $repo): JsonResponse
    {
        $parameters = $request->request->all();
        $data = $repo->getDataTablesList($parameters);
        $data["draw"] = $parameters['draw'];
        return $this->json($data);
    }

    #[Route('/new', name: 'app_term_new', methods: ['GET', 'POST'])]
    public function new(Request $request, TermRepository $termRepository): Response
    {
        $term = new Term();
        $form = $this->createForm(TermType::class, $term);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $termRepository->save($term, true);

            return $this->redirectToRoute('app_term_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('term/new.html.twig', [
            'term' => $term,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_term_show', methods: ['GET'])]
    public function show(Term $term): Response
    {
        return $this->render('term/show.html.twig', [
            'term' => $term,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_term_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Term $term, TermRepository $termRepository): Response
    {
        $form = $this->createForm(TermType::class, $term);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $termRepository->save($term, true);

            return $this->redirectToRoute('app_term_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('term/edit.html.twig', [
            'term' => $term,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_term_delete', methods: ['POST'])]
    public function delete(Request $request, Term $term, TermRepository $termRepository): Response
    {
        if ($this->isCsrfTokenValid('delete'.$term->getId(), $request->request->get('_token'))) {
            $termRepository->remove($term, true);
        }

        return $this->redirectToRoute('app_term_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/load/{wid}/{textid}/{ord}/{text}', name: 'app_term_load', methods: ['GET'])]
    public function load_form($wid, $textid, $ord, $text): Response
    {
        return $this->render('term/placeholder.html.twig', [
            'wid' => $wid, 'textid' => $textid, 'ord' => $ord, 'text' => $text
        ]);
    }
    
}
