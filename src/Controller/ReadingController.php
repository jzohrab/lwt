<?php

namespace App\Controller;

use App\Domain\ReadingFacade;
use App\Repository\TextRepository;
use App\Repository\TermRepository;
use App\Domain\Parser;
use App\Domain\ExpressionUpdater;
use App\Entity\Text;
use App\Entity\Sentence;
use App\Form\TermType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

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


    #[Route('/text/{TxID}', name: 'app_read_text', methods: ['GET'])]
    public function text(Request $request, Text $text, ReadingFacade $facade): Response
    {
        $textitems = $facade->getTextItems($text);
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
        TextRepository $textRepository
    ): Response
    {
        // The $text is set to '-' if there *is* no text,
        // b/c otherwise the route didn't work.
        if ($text == '-')
            $text = '';
        $term = $termRepository->load($wid, $textid, $ord, $text);

        $form = $this->createForm(TermType::class, $term);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $termRepository->save($term, true);
            $textentity = $textRepository->find($textid);
            $rawtextitems = $textRepository->getTextItems($textentity);

            // Use a temporary sentence to determine which items hide
            // which other items.
            $sentence = new Sentence(999, $rawtextitems);
            $textitems = $sentence->getTextItems();
            $updateitems = array_filter($textitems, fn($t) => $t->WoID == $term->getID());

            // what updates to do.
            $update_js = [];
            foreach ($updateitems as $item) {
                $hide_ids = array_map(fn($i) => $i->getSpanID(), $item->hides);
                $hide_ids = array_values($hide_ids);
                $replace_id = $item->getSpanID();
                if (count($hide_ids) > 0)
                    $replace_id = $hide_ids[0];
                $u = [
                    'replace' => $replace_id,
                    'hide' => $hide_ids
                ];
                $update_js[ $item->getSpanID() ] = $u;
            }

            // The updates are encoded here, and decoded in the
            // twig javascript.  Thanks to
            // https://stackoverflow.com/questions/38072085/
            //   how-to-render-json-into-a-twig-in-symfony2
            return $this->render('read/updated.html.twig', [
                'term' => $term,
                'textitems' => $updateitems,
                'updates' => json_encode($update_js)
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
