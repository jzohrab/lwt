<?php

// Ref https://south634.com/using-a-data-transformer-in-symfony-to-handle-duplicate-tags/

namespace App\Form\DataTransformer;

use App\Entity\Term;
use App\Entity\Language;
use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\ORM\EntityManagerInterface;
 
class TermParentTransformer implements DataTransformerInterface
{
    private $manager;
 
    public function __construct(EntityManagerInterface $manager, Term $term)
    {
        $this->manager = $manager;
        $this->term = $term;
    }
 
    public function transform($parent): string
    {
        if (null === $parent)
            return '';
        return $parent->getText();
    }

    
    /**
     * Convert parent_text text box content back into a real Term
     * instance, creating a new Term if needed.
     */
    public function reverseTransform($parent_text): ?Term
    {
        if (!$parent_text) {
            return null;
        }

        if (is_null($this->term->getLanguage())) {
            // Should never happen, but just in case.
            return null;
        }
        
        $repo = $this->manager->getRepository(Term::class);
        $langid = $this->term->getLanguage()->getLgID();
        $p = $repo->findTermInLanguage($parent_text, $langid);

        $ret = null;
        if ($p !== null) {
            $ret = $p;
        }
        else {
            $parts = mb_split("\s+", $parent_text);
            $testlen = function($p) { return mb_strlen($p) > 0; };
            $realparts = array_filter($parts, $testlen);
            $wordcount = count($realparts);

            $p = new Term();
            $p->setLanguage($this->term->getLanguage());
            $p->setText($parent_text);
            $p->setStatus($this->term->getStatus());
            $p->setWordCount($wordcount);
            $repo->save($p, true);

            $ret = $p;
        }
 
        return $ret;
    }
 
}
