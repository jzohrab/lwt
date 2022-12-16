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

            $updateti2sql = "UPDATE textitems2
              SET Ti2WoID = :woid WHERE Ti2LgID = :lgid AND Ti2TextLC = :text";
            $params = [
                "woid" => $entity->getID(),
                "lgid" => $entity->getLanguage()->getLgID(),
                "text" => $entity->getTextLC()
            ];
            $this->getEntityManager()
                ->getConnection()
                ->executeQuery($updateti2sql, $params);
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


    /** Returns data for ajax paging. */
    public function getDataTablesList($parameters) {

        $base_sql = "SELECT
w.WoID as WoID, LgName, WoText as WoText, ifnull(tags.taglist, '') as TagList, w.WoStatus
FROM
words w
INNER JOIN languages L on L.LgID = w.WoLgID

LEFT OUTER JOIN (
  SELECT WtWoID as WoID, GROUP_CONCAT(TgText ORDER BY TgText SEPARATOR ', ') AS taglist
  FROM
  wordtags wt
  INNER JOIN tags t on t.TgID = wt.WtTgID
  GROUP BY WtWoID
) AS tags on tags.WoID = w.WoID
";

        $conn = $this->getEntityManager()->getConnection();
        
        return DataTablesMySqlQuery::getData($base_sql, $parameters, $conn);
    }
}
