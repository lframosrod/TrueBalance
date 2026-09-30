<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api')]
class AuthController extends AbstractController
{
    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $name = trim($data['name'] ?? '');
        $email = strtolower(trim($data['email'] ?? ''));
        $password = $data['password'] ?? '';

        if (!$name || !$email || !$password) {
            return $this->json([
                'error' => 'Todos los campos (name, email, password) son obligatorios.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'error' => 'El formato del correo electrónico no es válido.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if (strlen($password) < 6) {
            return $this->json([
                'error' => 'La contraseña debe tener al menos 6 caracteres.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($userRepository->findOneBy(['email' => $email])) {
            return $this->json([
                'error' => 'Este correo electrónico ya está registrado.'
            ], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setName($name);
        $user->setEmail($email);
        $user->setPassword($passwordHasher->hashPassword($user, $password));

        $entityManager->persist($user);

        // Semilla automática de categorías iniciales para el nuevo usuario
        $defaultCategories = [
            ['name' => 'Salario', 'type' => Category::TYPE_INCOME, 'color' => '#10B981', 'icon' => 'briefcase'],
            ['name' => 'Freelance / Extra', 'type' => Category::TYPE_INCOME, 'color' => '#3B82F6', 'icon' => 'trending-up'],
            ['name' => 'Alimentación', 'type' => Category::TYPE_EXPENSE, 'color' => '#F59E0B', 'icon' => 'utensils'],
            ['name' => 'Transporte', 'type' => Category::TYPE_EXPENSE, 'color' => '#6366F1', 'icon' => 'car'],
            ['name' => 'Vivienda y Servicios', 'type' => Category::TYPE_EXPENSE, 'color' => '#EF4444', 'icon' => 'home'],
            ['name' => 'Ocio y Suscripciones', 'type' => Category::TYPE_EXPENSE, 'color' => '#8B5CF6', 'icon' => 'film'],
        ];

        foreach ($defaultCategories as $catData) {
            $category = new Category();
            $category->setName($catData['name']);
            $category->setType($catData['type']);
            $category->setColor($catData['color']);
            $category->setIcon($catData['icon']);
            $category->setUser($user);
            $entityManager->persist($category);
        }

        $entityManager->flush();

        return $this->json([
            'message' => 'Usuario registrado exitosamente.',
            'user' => [
                'id' => $user->getId(),
                'name' => $user->getName(),
                'email' => $user->getEmail(),
                'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
            ]
        ], Response::HTTP_CREATED);
    }

    #[Route('/me', name: 'api_me', methods: ['GET'])]
    public function me(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(['error' => 'No autenticado.'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'id' => $user->getId(),
            'name' => $user->getName(),
            'email' => $user->getEmail(),
            'roles' => $user->getRoles(),
            'createdAt' => $user->getCreatedAt()->format(\DateTimeInterface::ATOM),
        ]);
    }
}