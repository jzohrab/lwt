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


    #[Route('/search/{text}/{langid}', name: 'app_term_search', methods: ['GET'])]
    public function search_by_text_in_language($text, $langid, Request $request, TermRepository $repo): JsonResponse
    {
        $terms = $repo->findByTextMatchInLanguage($text, intval($langid));
        $result = [];
        foreach ($terms as $t) {
            $trans = $t->getTranslation();
            $result[$t->getID()] = [
                'text' => $t->getText(),
                'translation' => $t->getTranslation()
            ];
        }
        return $this->json($result);
    }


    private function processTermForm(
        \Symfony\Component\Form\Form $form,
        Request $request,
        Term $term,
        TermRepository $repo
    ): ?Response
    {
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $repo->save($term, true);
            return $this->redirectToRoute('app_term_index', [], Response::HTTP_SEE_OTHER);
        }
        return null;
    }

    #[Route('/new', name: 'app_term_new', methods: ['GET', 'POST'])]
    public function new(Request $request, TermRepository $termRepository): Response
    {
        $term = new Term();
        $form = $this->createForm(TermType::class, $term);
        $resp = $this->processTermForm($form, $request, $term, $termRepository);
        if ($resp != null)
            return $resp;

        return $this->renderForm('term/formframes.html.twig', [
            'term' => $term,
            'form' => $form,
            'showlanguageselector' => true,
            'disabletermediting' => false
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
        $resp = $this->processTermForm($form, $request, $term, $termRepository);
        if ($resp != null)
            return $resp;

        return $this->renderForm('term/formframes.html.twig', [
            'term' => $term,
            'form' => $form,
            'showlanguageselector' => false,
            'disabletermediting' => true
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

}
