<?php

// Ref https://south634.com/using-a-data-transformer-in-symfony-to-handle-duplicate-tags/

namespace App\Form\DataTransformer;

use App\Entity\Term;
use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\ORM\EntityManagerInterface;
 
class TermParentTransformer implements DataTransformerInterface
{
    private $manager;
 
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }
 
    public function transform($parent): string
    {
        if (null === $parent)
            return '';
        return $parent->getText();
    }
 
    public function reverseTransform($parent_text): ?Term
    {
        if (!parent_text) {
            return null;
        }
        
        $repo = $this->manager->getRepository(Term::class);
        $p = $repo->findByText($parent_text);

        $ret = null;
        if ($p !== null) {
            $ret = $p;
        }
        else {
            // New, create it.
            // $c->add($parent_text);
        }
 
        return $ret;
    }
 
}
