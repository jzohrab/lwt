<?php

namespace App\Controller;

use App\Repository\TextRepository;
use App\Repository\TermRepository;
use App\Entity\Text;
use App\Entity\Sentence;
use App\Form\TermType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Profiler\Profiler;

#[Route('/read')]
class ReadingController extends AbstractController
{

    #[Route('/empty', name: 'app_read_empty', methods: ['GET'])]
    public function empty(Request $request): Response
    {
        // A dummy hack to clear out the dictionary pane.  Annoying!
        return $this->render('read/empty.html.twig');
    }

    #[Route('/{TxID}', name: 'app_read', methods: ['GET'])]
    public function read(Request $request, Text $text, TextRepository $textRepository): Response
    {
        return $this->render('read/index.html.twig', [
            'textid' => $text->getID(),
        ]);
    }

    private function textItemsBySentenceID($textitems) {
        $textitems_by_sentenceid = array();
        foreach($textitems as $t) {
            $textitems_by_sentenceid[$t->SeID][] = $t;
        }

        $sentences = [];
        foreach ($textitems_by_sentenceid as $seid => $textitems)
            $sentences[] = new Sentence($seid, $textitems);

        return $sentences;
    }

    #[Route('/text/{TxID}', name: 'app_read_text', methods: ['GET'])]
    public function text(Request $request, Text $text, TextRepository $textRepository): Response
    {
        $textitems = $textRepository->getTextItems($text);
        $sentences = $this->textItemsBySentenceID($textitems);
        return $this->render('read/text.html.twig', [
            'dictionary_url' => $text->getLanguage()->getLgGoogleTranslateURI(),
            'sentences' => $sentences
        ]);
    }

    #[Route('/termform/{wid}/{textid}/{ord}/{text}', name: 'app_term_load', methods: ['GET', 'POST'])]
    public function term_form(
        $wid,
        $textid,
        $ord,
        $text,
        Request $request,
        TermRepository $termRepository,
        TextRepository $textRepository,
        ?Profiler $profiler): Response
    {
        // The $text is set to '-' if there *is* no text,
        // b/c otherwise the route didn't work.
        if ($text == '-')
            $text = '';
        $term = $termRepository->load($wid, $textid, $ord, $text);

        // Hide the symfony profiler on the form footer,
        // because it takes up half of the iframe!
        // ref https://symfony.com/doc/current/profiler.html#enabling-the-profiler-conditionally
        if (null !== $profiler) {
            $profiler->disable();
        }

        $form = $this->createForm(TermType::class, $term);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $termRepository->save($term, true);
            $textentity = $textRepository->find($textid);
            $textitems = $textRepository->getTextItems($textentity, $term->getID());
            return $this->render('read/updated.html.twig', [
                'term' => $term,
                'textitems' => $textitems
            ]);
        }

        return $this->renderForm('read/frameform.html.twig', [
            'term' => $term,
            'form' => $form,
            'extra' => $request->query,
            'showlanguageselector' => false,
            'disabletermediting' => true
        ]);
    }
    

}
