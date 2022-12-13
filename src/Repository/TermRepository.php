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
        /*
        return $this->createQueryBuilder('t')
            ->andWhere('t.language.LgID = :lid')
            ->andWhere('t.TextLC = :val')
            ->setParameter('lid', $langid)
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
            ;
        */
        $qb = $this->createQueryBuilder('t');

        return $qb->select('t')
            ->innerJoin('t.language', 'L', 'WITH', 'L.lgID = :langid')
            ->where('t.textLC = :val')
            ->setParameter('langid', $langid)
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult();
    }

//    /**
//     * @return Term[] Returns an array of Term objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

}
