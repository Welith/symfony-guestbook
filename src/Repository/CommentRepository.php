<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Conference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class CommentRepository
 * @package App\Repository
 */
class CommentRepository extends ServiceEntityRepository
{
    /**
     * CONST
     */
    private const DAYS_BEFORE_REJECTED_REMOVAL = 7;

    /**
     * CONST
     */
    public const PAGINATOR_PER_PAGE = 2;

    /**
     * CommentRepository constructor.
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function countOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()->select('COUNT(c.id)')->getQuery()->getSingleScalarResult();
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function deleteOldRejected(): int
    {
        return $this->getOldRejectedQueryBuilder()->delete()->getQuery()->execute();
    }

    /**
     * @return QueryBuilder
     * @throws \Exception
     */
    private function getOldRejectedQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.state = :state_rejected')
            #->andWhere('c.createdAt < :date')
            ->setParameters([
                'state_rejected' => 'rejected',
                #'date' => new \DateTime(-self::DAYS_BEFORE_REJECTED_REMOVAL.'days')
            ]);
    }

    /**
     * @param Conference $conference
     * @param int $offset
     * @return Paginator
     */
    public function getCommentPaginator(Conference $conference, int $offset)
    {
        $query = $this->createQueryBuilder('c')
            ->andWhere('c.conference = :conference')
            ->andWhere('c.state = :state')
            ->setParameter('conference', $conference)
            ->setParameter('state', 'published')
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(self::PAGINATOR_PER_PAGE)
            ->setFirstResult($offset)
            ->getQuery();
        return new Paginator($query);
    }
}
