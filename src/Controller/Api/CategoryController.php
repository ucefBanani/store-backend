<?php

// src/Controller/CategoryController.php

namespace App\Controller\Api;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/categories', name: 'api_categories_')]
class CategoryController extends AbstractController
{
    // Create Category
    #[Route('', name: 'create_category', methods: ['POST'])]
    public function createCategory(Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, SerializerInterface $serializer): JsonResponse
    {
        $this->denyAccessUnlessGranted('create_category');

        $data = $request->getContent();
        $category = $serializer->deserialize($data, Category::class, 'json');

        // Validate category data
        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->persist($category);
        $entityManager->flush();

        return $this->json(['message' => 'Category created successfully', 'category' => $category], Response::HTTP_CREATED);
    }

    // Get All Categories
    #[Route('', name: 'get_categories', methods: ['GET'])]
    public function getCategories(EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $categories = $entityManager->getRepository(Category::class)->findAll();
        $jsonCategories = $serializer->serialize($categories, 'json', ['groups' => 'category:read']);
        return new JsonResponse($jsonCategories, Response::HTTP_OK, [], true);
    }

    // Get Single Category
    #[Route('/{id}', name: 'get_category', methods: ['GET'])]
    public function getCategory(int $id, EntityManagerInterface $entityManager, SerializerInterface $serializer): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);

        if (!$category) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $jsonCategory = $serializer->serialize($category, 'json', ['groups' => 'category:read']);
        return new JsonResponse($jsonCategory, Response::HTTP_OK, [], true);
    }

    // Update Category
    #[Route('/{id}', name: 'update_category', methods: ['PUT'])]
    public function updateCategory(int $id, Request $request, EntityManagerInterface $entityManager, ValidatorInterface $validator, SerializerInterface $serializer): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);

        if (!$category) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $data = $request->getContent();
        $updatedCategory = $serializer->deserialize($data, Category::class, 'json');

        // Merge updated data with the existing category
        $category->setName($updatedCategory->getName());

        // Validate updated category data
        $errors = $validator->validate($category);
        if (count($errors) > 0) {
            return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
        }

        $entityManager->flush();

        return $this->json(['message' => 'Category updated successfully', 'category' => $category], Response::HTTP_OK);
    }

    // Delete Category
    #[Route('/{id}', name: 'delete_category', methods: ['DELETE'])]
    public function deleteCategory(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        $category = $entityManager->getRepository(Category::class)->find($id);

        if (!$category) {
            return $this->json(['error' => 'Category not found'], Response::HTTP_NOT_FOUND);
        }

        $entityManager->remove($category);
        $entityManager->flush();

        return $this->json(['message' => 'Category deleted successfully'], Response::HTTP_OK);
    }
}
