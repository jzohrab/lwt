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
 
    public function transform($parent): ?string
    {
        if ($this->term->getParent() == null)
            return null;
        return $this->term->getParent()->getText();
    }

    
    public function reverseTransform($parent_text) {
        return $parent_text;
    }
 
}
