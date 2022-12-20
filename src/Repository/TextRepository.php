<?php

namespace App\Repository;

use App\Entity\Text;
use App\Entity\Sentence;
use App\Entity\TextItem;
use App\Domain\Parser;
use App\Domain\ExpressionUpdater;
use App\Domain\TextStatsCache;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


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
            TextStatsCache::markStale([$entity->getID()]);

            if (! $entity->isArchived() ) {
                $langid = $entity->getLanguage()->getLgID();

                if ($parseTexts) {
                    Parser::parse($entity);
                    ExpressionUpdater::associateExpressionsInText($entity);
                    TextStatsCache::refresh();
                }
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
