<?php

namespace App\Repository;

use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }

    /**
     * Lista de movimientos filtrados por usuario, rango de fechas, tipo y categoría
     * @return Transaction[]
     */
    public function findByFilters(
        User $user,
        ?\DateTimeImmutable $startDate = null,
        ?\DateTimeImmutable $endDate = null,
        ?string $type = null,
        ?int $categoryId = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.category', 'c')
            ->addSelect('c')
            ->where('t.user = :user')
            ->setParameter('user', $user)
            ->orderBy('t.transactionDate', 'DESC')
            ->addOrderBy('t.id', 'DESC');

        if ($startDate) {
            $qb->andWhere('t.transactionDate >= :startDate')
                ->setParameter('startDate', $startDate->format('Y-m-d'));
        }

        if ($endDate) {
            $qb->andWhere('t.transactionDate <= :endDate')
                ->setParameter('endDate', $endDate->format('Y-m-d'));
        }

        if ($type) {
            $qb->andWhere('t.type = :type')
                ->setParameter('type', strtoupper($type));
        }

        if ($categoryId) {
            $qb->andWhere('c.id = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Obtiene el total de ingresos, gastos y balance neto en un rango de fechas
     */
    public function getSummaryByDateRange(
        User $user,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate
    ): array {
        $rows = $this->createQueryBuilder('t')
            ->select('t.type AS type, COALESCE(SUM(t.amount), 0) AS total, COUNT(t.id) AS count')
            ->where('t.user = :user')
            ->andWhere('t.transactionDate BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startDate->format('Y-m-d'))
            ->setParameter('end', $endDate->format('Y-m-d'))
            ->groupBy('t.type')
            ->getQuery()
            ->getArrayResult();

        $totalIncome = 0.0;
        $totalExpense = 0.0;
        $transactionCount = 0;

        foreach ($rows as $row) {
            if ($row['type'] === Transaction::TYPE_INCOME) {
                $totalIncome = (float) $row['total'];
            } elseif ($row['type'] === Transaction::TYPE_EXPENSE) {
                $totalExpense = (float) $row['total'];
            }
            $transactionCount += (int) $row['count'];
        }

        return [
            'totalIncome' => round($totalIncome, 2),
            'totalExpense' => round($totalExpense, 2),
            'netBalance' => round($totalIncome - $totalExpense, 2),
            'savingsRate' => $totalIncome > 0
                ? round((($totalIncome - $totalExpense) / $totalIncome) * 100, 1)
                : 0.0,
            'transactionCount' => $transactionCount,
        ];
    }

    /**
     * Agrupa los montos por categoría (ideal para gráficos Doughnut/Bar en Vue Chart.js)
     */
    public function getBreakdownByCategory(
        User $user,
        \DateTimeImmutable $startDate,
        \DateTimeImmutable $endDate,
        ?string $type = null
    ): array {
        $qb = $this->createQueryBuilder('t')
            ->innerJoin('t.category', 'c')
            ->select(
                'c.id AS categoryId',
                'c.name AS categoryName',
                'c.color AS color',
                'c.icon AS icon',
                'c.type AS type',
                'SUM(t.amount) AS total',
                'COUNT(t.id) AS count'
            )
            ->where('t.user = :user')
            ->andWhere('t.transactionDate BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $startDate->format('Y-m-d'))
            ->setParameter('end', $endDate->format('Y-m-d'))
            ->groupBy('c.id, c.name, c.color, c.icon, c.type')
            ->orderBy('total', 'DESC');

        if ($type) {
            $qb->andWhere('t.type = :type')
                ->setParameter('type', strtoupper($type));
        }

        $results = $qb->getQuery()->getArrayResult();

        return array_map(static fn(array $row) => [
            'categoryId' => (int) $row['categoryId'],
            'categoryName' => $row['categoryName'],
            'color' => $row['color'],
            'icon' => $row['icon'],
            'type' => $row['type'],
            'total' => round((float) $row['total'], 2),
            'count' => (int) $row['count'],
        ], $results);
    }
}