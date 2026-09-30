<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Transaction;
use App\Entity\User;
use App\Repository\CategoryRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/transactions')]
class TransactionController extends AbstractController
{
    #[Route('', name: 'api_transactions_index', methods: ['GET'])]
    public function index(
        #[CurrentUser] User $user,
        Request $request,
        TransactionRepository $transactionRepository
    ): JsonResponse {
        $startDateStr = $request->query->get('startDate');
        $endDateStr = $request->query->get('endDate');
        $type = $request->query->get('type');
        $categoryId = $request->query->get('categoryId') ? (int) $request->query->get('categoryId') : null;

        $startDate = $startDateStr ? new \DateTimeImmutable($startDateStr) : null;
        $endDate = $endDateStr ? new \DateTimeImmutable($endDateStr) : null;

        $transactions = $transactionRepository->findByFilters($user, $startDate, $endDate, $type, $categoryId);

        $data = array_map([$this, 'serializeTransaction'], $transactions);

        return $this->json($data);
    }

    #[Route('/summary', name: 'api_transactions_summary', methods: ['GET'])]
    public function summary(
        #[CurrentUser] User $user,
        Request $request,
        TransactionRepository $transactionRepository
    ): JsonResponse {
        // Por defecto toma el primer y último día del mes actual
        $startDateStr = $request->query->get('startDate', (new \DateTimeImmutable('first day of this month'))->format('Y-m-d'));
        $endDateStr = $request->query->get('endDate', (new \DateTimeImmutable('last day of this month'))->format('Y-m-d'));

        $startDate = new \DateTimeImmutable($startDateStr);
        $endDate = new \DateTimeImmutable($endDateStr);

        return $this->json([
            'period' => [
                'startDate' => $startDate->format('Y-m-d'),
                'endDate' => $endDate->format('Y-m-d'),
            ],
            'totals' => $transactionRepository->getSummaryByDateRange($user, $startDate, $endDate),
            'byCategory' => $transactionRepository->getBreakdownByCategory($user, $startDate, $endDate),
        ]);
    }

    #[Route('', name: 'api_transactions_create', methods: ['POST'])]
    public function create(
        #[CurrentUser] User $user,
        Request $request,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $amount = (float) ($data['amount'] ?? 0);
        $description = trim($data['description'] ?? '');
        $dateStr = $data['transactionDate'] ?? date('Y-m-d');
        $categoryId = (int) ($data['categoryId'] ?? 0);

        if ($amount <= 0 || !$categoryId) {
            return $this->json([
                'error' => 'El monto debe ser mayor a 0 y debes seleccionar una categoría válida.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $category = $categoryRepository->findOneBy(['id' => $categoryId, 'user' => $user]);
        if (!$category) {
            return $this->json(['error' => 'Categoría no válida para este usuario.'], Response::HTTP_NOT_FOUND);
        }

        $transaction = new Transaction();
        $transaction->setAmount($amount);
        // El tipo se sincroniza automáticamente con el tipo de la categoría (INCOME o EXPENSE)
        $transaction->setType($category->getType());
        $transaction->setDescription($description ?: $category->getName());
        $transaction->setTransactionDate(new \DateTimeImmutable($dateStr));
        $transaction->setCategory($category);
        $transaction->setUser($user);

        $entityManager->persist($transaction);
        $entityManager->flush();

        return $this->json($this->serializeTransaction($transaction), Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_transactions_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        #[CurrentUser] User $user,
        TransactionRepository $transactionRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $transaction = $transactionRepository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$transaction) {
            return $this->json(['error' => 'Transacción no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($transaction);
        $entityManager->flush();

        return $this->json(['message' => 'Transacción eliminada correctamente.']);
    }

    private function serializeTransaction(Transaction $t): array
    {
        $category = $t->getCategory();
        return [
            'id' => $t->getId(),
            'amount' => (float) $t->getAmount(),
            'type' => $t->getType(),
            'description' => $t->getDescription(),
            'transactionDate' => $t->getTransactionDate()?->format('Y-m-d'),
            'createdAt' => $t->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'category' => [
                'id' => $category?->getId(),
                'name' => $category?->getName(),
                'color' => $category?->getColor(),
                'icon' => $category?->getIcon(),
                'type' => $category?->getType(),
            ],
        ];
    }
}