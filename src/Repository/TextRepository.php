<?php

namespace App\Repository;

use App\Entity\Text;
use App\Entity\Sentence;
use App\Entity\TextItem;
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

    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters, $archived = false) {

        // Required, can't interpolate a bool in the sql string.
        $archived = $archived ? 'true' : 'false';

        // TODO: this query is slow ... we could either a) ajax
        // in relevant content to displayed records, b) page the
        // datatable, or c) calculate and cache the data for
        // each text, refreshing the cache as needed.  I feel c)
        // is best, at the moment.
        $base_sql = "SELECT
          t.TxID As TxID,
          LgName,
          TxTitle,
          TxArchived,
          tags.taglist AS TagList,
          CONCAT(ifnull(terms.countTerms, 0), ' / ', ifnull(unkterms.countUnknowns, 0)) as TermStats
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
            SELECT Ti2TxID as TxID, COUNT(*) AS countTerms
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
            SELECT Ti2TxID as TxID, COUNT(*) as countUnknowns
            FROM textitems2
            WHERE Ti2WoID = 0 AND Ti2WordCount = 1
            GROUP BY Ti2TxID
          ) AS unkterms ON unkterms.TxID = t.TxID

          WHERE t.TxArchived = $archived";

        $conn = $this->getEntityManager()->getConnection();
        
        return DataTablesMySqlQuery::getData($base_sql, $parameters, $conn);
    }


    public function getSentences(Text $entity) {

        $textid = $textid = $entity->getID();
        $sql = "SELECT
           $textid AS TextID,
           Ti2WordCount AS WordCount,
           Ti2Text AS Text,
           Ti2TextLC AS TextLC,
           Ti2Order AS `Order`,
           Ti2SeID AS SeID,
           CASE WHEN Ti2WordCount>0 THEN 1 ELSE 0 END AS IsWord,
           CHAR_LENGTH(Ti2Text) AS TextLength,
           w.WoID,
           w.WoText,
           w.WoStatus,
           w.WoTranslation,
           w.WoRomanization,
           IF (wordtags IS NULL, '', CONCAT('[', wordtags, ']')) as Tags,

           pw.WoID as ParentWoID,
           pw.WoTextLC as ParentWoTextLC,
           pw.WoTranslation as ParentWoTranslation,
           IF (parenttags IS NULL, '', CONCAT('[', parenttags, ']')) as ParentTags

           FROM textitems2
           LEFT JOIN words AS w ON Ti2WoID = w.WoID
           LEFT JOIN (
             SELECT
             WtWoID,
             GROUP_CONCAT(DISTINCT TgText ORDER BY TgText separator ', ') as wordtags
             FROM wordtags
             INNER JOIN tags ON TgID = WtTgID
             GROUP BY WtWoID
           ) wordtaglist on wordtaglist.WtWoID = w.WoID

           LEFT JOIN wordparents ON wordparents.WpWoID = w.WoID
           LEFT JOIN words AS pw on pw.WoID = wordparents.WpParentWoID
           LEFT JOIN (
             SELECT
             wordparents.WpWoID,
             GROUP_CONCAT(DISTINCT TgText ORDER BY TgText separator ', ') as parenttags
             FROM wordtags
             INNER JOIN tags ON TgID = WtTgID
             INNER JOIN wordparents on wordparents.WpParentWoID = wordtags.WtWoID
             GROUP BY WpWoID
           ) parenttaglist on parenttaglist.WpWoID = w.WoID

           WHERE Ti2TxID = $textid
           ORDER BY Ti2Order asc, Ti2WordCount desc";

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $res = $stmt->executeQuery();
        $rows = $res->fetchAllAssociative();

        $textitems = [];
        foreach ($rows as $row) {
            $t = new TextItem();
            foreach ($row as $key => $val) {
                $t->$key = $val;
            }
            $intkeys = [ 'TextID', 'WordCount', 'Order', 'SeID', 'IsWord', 'TextLength', 'WoID', 'WoStatus', 'ParentWoID' ];
            foreach ($intkeys as $key) {
                $t->key = intval($t->$key);
            }
            $textitems[] = $t;
        }

        $textitems_by_sentenceid = array();
        foreach($textitems as $t) {
            $textitems_by_sentenceid[$t->SeID][] = $t;
        }

        $sentences = [];
        foreach ($textitems_by_sentenceid as $seid => $textitems)
            $sentences[] = new Sentence($seid, $textitems);

        return $sentences;
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
