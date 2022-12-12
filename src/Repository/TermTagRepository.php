<?php

namespace App\Repository;

use App\Entity\TermTag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TermTag>
 *
 * @method TermTag|null find($id, $lockMode = null, $lockVersion = null)
 * @method TermTag|null findOneBy(array $criteria, array $orderBy = null)
 * @method TermTag[]    findAll()
 * @method TermTag[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TermTagRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TermTag::class);
    }

    public function save(TermTag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TermTag $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByText($value): ?TermTag
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.text = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

}
