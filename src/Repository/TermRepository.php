<?php

namespace App\Repository;

use App\Entity\Term;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Term>
 *
 * @method Term|null find($id, $lockMode = null, $lockVersion = null)
 * @method Term|null findOneBy(array $criteria, array $orderBy = null)
 * @method Term[]    findAll()
 * @method Term[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Term::class);
    }

    public function save(Term $entity, bool $flush = false): void
    {
        // If the term's parent is new, throw some data into it.
        $p = $entity->getParent();
        if ($p != null && $p->getID() == null) {
            $p->setLanguage($entity->getLanguage());
            $p->setStatus($entity->getStatus());
            $p->setTranslation($entity->getTranslation());
            $p->setSentence($entity->getSentence());
        }

        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Term $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findTermInLanguage(string $value, int $langid): ?Term
    {
        // Using Doctrine Query Language --
        // Interesting, but am not totally confident with it.
        // e.g. That I had to use the private field WoTextLC
        // instead of the public property was surprising.
        // Anyway, it works. :-P
        $dql = "SELECT t FROM App\Entity\Term t
        JOIN App\Entity\Language L
        WHERE L.LgID = :langid AND t.WoTextLC = :val";
        $em = $this->getEntityManager();
        $query = $em
               ->createQuery($dql)
               ->setParameter('langid', $langid)
               ->setParameter('val', mb_strtolower($value));
        $terms = $query->getResult(); // array of ForumUser objects

        if (count($terms) == 0)
            return null;
        return $terms[0];
    }

}
