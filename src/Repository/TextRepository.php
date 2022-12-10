<?php

namespace App\Repository;

use App\Entity\Text;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

// TODO: pull splitCheckText into its own parsing library.
require_once __DIR__ . '/../../inc/database_connect.php';

/**
 * @extends ServiceEntityRepository<Text>
 *
 * @method Text|null find($id, $lockMode = null, $lockVersion = null)
 * @method Text|null findOneBy(array $criteria, array $orderBy = null)
 * @method Text[]    findAll()
 * @method Text[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TextRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Text::class);
    }

    private function removeSentencesAndWords(int $textid): void
    {
        // TODO - if create sentence and textitem entities, find and delete?
        $conn = $this->getEntityManager()->getConnection();
        $sqls = [
            "delete from sentences where SeTxID = $textid",
            "delete from textitems2 where Ti2TxID = $textid"
        ];
        foreach ($sqls as $sql) {
            $stmt = $conn->prepare($sql);
            $stmt->executeQuery();
        }
    }

    public function save(Text $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
            $this->removeSentencesAndWords($entity->getID());

            if (! $entity->isArchived() ) {
                $langid = $entity->getLanguage()->getLgID();
                splitCheckText($entity->getText(), $langid, $entity->getID());
            }
        }
    }

    public function remove(Text $entity, bool $flush = false): void
    {
        $textid = $entity->getID();
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
            $this->removeSentencesAndWords($textid);
        }
    }

    public function findAllWithStats(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        // TODO: this query is slow ... we could either a) ajax
        // in relevant content to displayed records, b) page the
        // datatable, or c) calculate and cache the data for
        // each text, refreshing the cache as needed.  I feel c)
        // is best, at the moment.
        $sql = "SELECT t.TxID, LgName, TxTitle, TxArchived, tags.taglist,
          ifnull(terms.countTerms, 0) as countTerms,
          ifnull(unkterms.countUnknowns, 0) as countUnknowns
          /* ifnull(mwordterms.countExpressions, 0) as countExpressions, */

          FROM texts t
          INNER JOIN languages on LgID = t.TxLgID

          LEFT OUTER JOIN (
            SELECT TtTxID as TxID, GROUP_CONCAT(T2Text ORDER BY T2Text SEPARATOR ', ') AS taglist
            FROM
            texttags tt
            INNER JOIN tags2 t2 on t2.T2ID = tt.TtT2ID
            GROUP BY TtTxID
          ) AS tags on tags.TxID = t.TxID

          LEFT OUTER JOIN (
            SELECT Ti2TxID as TxID, COUNT(DISTINCT Ti2TextLC) AS countTerms
            FROM textitems2
            WHERE Ti2WoID <> 0
            GROUP BY Ti2TxID
          ) AS terms on terms.TxID = t.TxID

          /** Ignoring expression count for now, can't see the need.
          LEFT OUTER JOIN (
            SELECT Ti2TxID AS TxID, COUNT(DISTINCT Ti2WoID) as countExpressions
            FROM textitems2
            WHERE Ti2WordCount > 1
            GROUP BY Ti2TxID
          ) AS mwordterms on mwordterms.TxID = t.TxID
          */

          LEFT OUTER JOIN (
            SELECT Ti2TxID as TxID, 0 as status, COUNT(*) as countUnknowns
            FROM textitems2
            WHERE Ti2WoID = 0 AND Ti2WordCount = 1
            GROUP BY Ti2TxID
          ) AS unkterms ON unkterms.TxID = t.TxID";
        
        $stmt = $conn->prepare($sql);
        $resultSet = $stmt->executeQuery();
        $ret = [];
        foreach ($resultSet->fetchAllAssociative() as $row) {
            $t = new Text();
            $t->ID = $row['TxID'];
            $t->Language = $row['LgName'];
            $t->Title = $row['TxTitle'];
            $t->Tags = $row['taglist'];
            $t->isArchived = $row['TxArchived'];
            $t->TermCount = (int) $row['countTerms'];
            $t->UnknownCount = (int) $row['countUnknowns'];
            $ret[] = $t;
        }
        return $ret;
    }

    /* Status pivot table query.  Slow when querying for all texts, fast with just one. */

    /*
      SELECT TxID,
      SUM(CASE WHEN status=0 THEN c ELSE 0 END) AS s0,
      SUM(CASE WHEN status=1 THEN c ELSE 0 END) AS s1,
      SUM(CASE WHEN status=2 THEN c ELSE 0 END) AS s2,
      SUM(CASE WHEN status=3 THEN c ELSE 0 END) AS s3,
      SUM(CASE WHEN status=4 THEN c ELSE 0 END) AS s4,
      SUM(CASE WHEN status=5 THEN c ELSE 0 END) AS s5,
      SUM(CASE WHEN status=98 THEN c ELSE 0 END) AS s98,
      SUM(CASE WHEN status=99 THEN c ELSE 0 END) AS s99
      FROM (
      SELECT Ti2TxID AS TxID, WoStatus AS status, COUNT(*) as c
      FROM textitems2
      INNER JOIN words ON WoID = Ti2WoID
      WHERE Ti2WoID <> 0
      AND Ti2TxID in (1)
      GROUP BY Ti2TxID, WoStatus

      UNION
      SELECT Ti2TxID as TxID, 0 as status, COUNT(*) as c
      FROM textitems2
      WHERE Ti2WoID = 0 AND Ti2WordCount = 1
      AND Ti2TxID in (1)
      GROUP BY Ti2TxID
  
      ) rawdata
      GROUP BY TxID;
    */


//    /**
//     * @return Text[] Returns an array of Text objects
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

//    public function findOneBySomeField($value): ?Text
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
