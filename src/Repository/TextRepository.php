<?php

namespace App\Repository;

use App\Entity\Text;
use App\Entity\Sentence;
use App\Entity\TextItem;
use App\Domain\Parser;
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

    public function save(Text $entity, bool $flush = false, bool $parseTexts = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
            $this->removeSentencesAndWords($entity->getID());

            if (! $entity->isArchived() ) {
                $langid = $entity->getLanguage()->getLgID();

                if ($parseTexts) {
                    Parser::parse($entity);
                }

                $this->refreshStatsCache();
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


    /**
     * refresh records in textstatscache.
     *
     * When listing texts, it's far too slow to query and rebuild
     * stats all the time.
     */
    public function refreshStatsCache() {

        // TODO:storedproc Replace temp table with stored proc.
        //
        // Using a temp table to determine which texts to update.
        // I tried using left joins back to textstatscache, but it
        // was slow, despite indexing.  There is probably a better
        // way to do this, but this works for now.
        //
        // Ideally, this would be a temp table ... but then the stats update
        // query complains about "reopening the table", as it's used several
        // times in the query.
        //
        // This could be moved to a stored procedure, but this is good
        // enough for now.

        $sql = "
-- Temp table of textids.
drop table if exists TEMPupdateStatsTxIDs;
create table TEMPupdateStatsTxIDs (TxID int primary key);

insert into TEMPupdateStatsTxIDs
select t.TxID from texts t
left join textstatscache c on c.TxID = t.TxID
where c.TxID is null
and t.TxArchived = 0;

-- Load stats.
insert into textstatscache (
  TxID,
  wordcount,
  distinctterms,
  multiwordexpressions,
  sUnk,
  s1,
  s2,
  s3,
  s4,
  s5,
  sIgn,
  sWkn
)
SELECT
t.TxID As TxID,

wordcount.n as wordcount,
distinctterms.n as distinctterms,
coalesce(mwordexpressions.n, 0) as multiwordexpressions,
sUnk, s1, s2, s3, s4, s5, sIgn, sWkn

FROM texts t
inner join TEMPupdateStatsTxIDs u on u.TxID = t.TxID

LEFT OUTER JOIN (
  SELECT Ti2TxID as TxID, COUNT(*) AS n
  FROM textitems2
  inner join TEMPupdateStatsTxIDs u on u.TxID = textitems2.Ti2TxID
  WHERE Ti2WordCount = 1
  GROUP BY Ti2TxID
) AS wordcount on wordcount.TxID = t.TxID

LEFT OUTER JOIN (
  SELECT Ti2TxID as TxID, COUNT(distinct Ti2WoID) AS n
  FROM textitems2
  inner join TEMPupdateStatsTxIDs u on u.TxID = textitems2.Ti2TxID
  WHERE Ti2WoID <> 0
  GROUP BY Ti2TxID
) AS distinctterms on distinctterms.TxID = t.TxID

LEFT OUTER JOIN (
  SELECT Ti2TxID AS TxID, COUNT(DISTINCT Ti2WoID) as n
  FROM textitems2
  inner join TEMPupdateStatsTxIDs u on u.TxID = textitems2.Ti2TxID
  WHERE Ti2WordCount > 1
  GROUP BY Ti2TxID
) AS mwordexpressions on mwordexpressions.TxID = t.TxID

LEFT OUTER JOIN (

      SELECT TxID,
      SUM(CASE WHEN status=0 THEN c ELSE 0 END) AS sUnk,
      SUM(CASE WHEN status=1 THEN c ELSE 0 END) AS s1,
      SUM(CASE WHEN status=2 THEN c ELSE 0 END) AS s2,
      SUM(CASE WHEN status=3 THEN c ELSE 0 END) AS s3,
      SUM(CASE WHEN status=4 THEN c ELSE 0 END) AS s4,
      SUM(CASE WHEN status=5 THEN c ELSE 0 END) AS s5,
      SUM(CASE WHEN status=98 THEN c ELSE 0 END) AS sIgn,
      SUM(CASE WHEN status=99 THEN c ELSE 0 END) AS sWkn

      FROM (
      SELECT Ti2TxID AS TxID, WoStatus AS status, COUNT(*) as c
      FROM textitems2
      inner join TEMPupdateStatsTxIDs u on u.TxID = textitems2.Ti2TxID
      INNER JOIN words ON WoID = Ti2WoID
      WHERE Ti2WoID <> 0
      GROUP BY Ti2TxID, WoStatus

      UNION
      SELECT Ti2TxID as TxID, 0 as status, COUNT(*) as c
      FROM textitems2
      inner join TEMPupdateStatsTxIDs u on u.TxID = textitems2.Ti2TxID
      WHERE Ti2WoID = 0 AND Ti2WordCount = 1
      GROUP BY Ti2TxID
  
      ) rawdata
      GROUP BY TxID
) AS statuses on statuses.TxID = t.TxID;

drop table if exists TEMPupdateStatsTxIDs;
";

        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($sql);
        $res = $stmt->executeQuery();
    }

    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters, $archived = false) {

        // Required, can't interpolate a bool in the sql string.
        $archived = $archived ? 'true' : 'false';

        $base_sql = "SELECT
          t.TxID As TxID,
          LgName,
          TxTitle,
          TxArchived,
          tags.taglist AS TagList,
          CONCAT(c.distinctterms, ' / ', c.sUnk) as TermStats

          FROM texts t
          INNER JOIN languages on LgID = t.TxLgID
          LEFT OUTER JOIN textstatscache c on c.TxID = t.TxID

          LEFT OUTER JOIN (
            SELECT TtTxID as TxID, GROUP_CONCAT(T2Text ORDER BY T2Text SEPARATOR ', ') AS taglist
            FROM
            texttags tt
            INNER JOIN tags2 t2 on t2.T2ID = tt.TtT2ID
            GROUP BY TtTxID
          ) AS tags on tags.TxID = t.TxID

          WHERE t.TxArchived = $archived";

        $conn = $this->getEntityManager()->getConnection();
        
        return DataTablesMySqlQuery::getData($base_sql, $parameters, $conn);
    }


    public function getTextItems(Text $entity, int $woid = null) {
        $textid = $textid = $entity->getID();

        $where = [ "Ti2TxID = $textid" ];
        if ($woid != null)
            $where[] = "w.WoID = $woid";
        $where = implode(' AND ', $where);

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

           WHERE $where
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

        return $textitems;
    }

}
