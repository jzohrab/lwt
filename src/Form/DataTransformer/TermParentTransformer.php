<?php

// Ref https://south634.com/using-a-data-transformer-in-symfony-to-handle-duplicate-tags/

namespace App\Form\DataTransformer;
 
use Symfony\Component\Form\DataTransformerInterface;
use Doctrine\ORM\EntityManagerInterface;
 
class TermParentTransformer implements DataTransformerInterface
{
    private $manager;
 
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }
 
    public function transform($parents)
    {
        if ($parents->isEmpty())
            return null;
        return $parents[0]->getText();
    }
 
    public function reverseTransform($parent_text)
    {
        $c = new ArrayCollection();
 
        $repo = $this->manager->getRepository(\App\Entity\Term::class);

        $p = $repo->findByText($parent_text);

        if ($p !== null) {
            $c->add($p);
        }
        else {
            $c->add($parent_text);
        }
 
        return $c;
    }
 
}
