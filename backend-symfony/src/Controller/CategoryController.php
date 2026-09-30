<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/categories')]
class CategoryController extends AbstractController
{
    #[Route('', name: 'api_categories_index', methods: ['GET'])]
    public function index(#[CurrentUser] User $user, CategoryRepository $categoryRepository): JsonResponse
    {
        $categories = $categoryRepository->findBy(['user' => $user], ['type' => 'ASC', 'name' => 'ASC']);

        $data = array_map(static fn(Category $c) => [
            'id' => $c->getId(),
            'name' => $c->getName(),
            'type' => $c->getType(),
            'color' => $c->getColor(),
            'icon' => $c->getIcon(),
        ], $categories);

        return $this->json($data);
    }

    #[Route('', name: 'api_categories_create', methods: ['POST'])]
    public function create(
        #[CurrentUser] User $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $name = trim($data['name'] ?? '');
        $type = strtoupper(trim($data['type'] ?? ''));
        $color = trim($data['color'] ?? '#6366F1');
        $icon = trim($data['icon'] ?? 'tag');

        if (!$name || !in_array($type, [Category::TYPE_INCOME, Category::TYPE_EXPENSE], true)) {
            return $this->json([
                'error' => 'Nombre y tipo válido (INCOME o EXPENSE) son obligatorios.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $category = new Category();
        $category->setName($name);
        $category->setType($type);
        $category->setColor($color);
        $category->setIcon($icon);
        $category->setUser($user);

        $entityManager->persist($category);
        $entityManager->flush();

        return $this->json([
            'id' => $category->getId(),
            'name' => $category->getName(),
            'type' => $category->getType(),
            'color' => $category->getColor(),
            'icon' => $category->getIcon(),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', name: 'api_categories_delete', methods: ['DELETE'])]
    public function delete(
        int $id,
        #[CurrentUser] User $user,
        CategoryRepository $categoryRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $category = $categoryRepository->findOneBy(['id' => $id, 'user' => $user]);

        if (!$category) {
            return $this->json(['error' => 'Categoría no encontrada.'], Response::HTTP_NOT_FOUND);
        }

        if ($category->getTransactions()->count() > 0) {
            return $this->json([
                'error' => 'No se puede eliminar una categoría que ya tiene transacciones registradas.'
            ], Response::HTTP_CONFLICT);
        }

        $entityManager->remove($category);
        $entityManager->flush();

        return $this->json(['message' => 'Categoría eliminada correctamente.']);
    }
}