<?php

namespace App\Repository;

use App\Entity\Blarg;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Blarg>
 *
 * @method Blarg|null find($id, $lockMode = null, $lockVersion = null)
 * @method Blarg|null findOneBy(array $criteria, array $orderBy = null)
 * @method Blarg[]    findAll()
 * @method Blarg[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BlargRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Blarg::class);
    }

    public function save(Blarg $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Blarg $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findAllWithExtra(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT TxID, TxTitle, 4 as Extra FROM texts";
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        $ret = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $b = new Blarg();
            $b->TxID = $row['TxID'];
            $b->TxTitle = $row['TxTitle'];
            $b->Extra = $row['Extra'];
            $ret[] = $b;
        }
        return $ret;
    }
    
//    /**
//     * @return Blarg[] Returns an array of Blarg objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Blarg
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
