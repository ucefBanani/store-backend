<?php

namespace App\Controller\Api;

use App\Entity\Category;
use App\Entity\Product;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/products', name: 'api_products_')]
class ProductController extends AbstractController
{
    #[Route('', methods: ['GET'], name: 'list')]
    public function list(EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {

        $products = $em->getRepository(Product::class)->findAll();
        $data = $serializer->serialize($products, 'json', ['groups' => 'product:read']);

        return new JsonResponse($data, 200, [], true);
    }

    #[Route('', name: 'create_product', methods: ['POST'])]
    public function createProduct(
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {

        $this->denyAccessUnlessGranted('create', new Product());

        $product = new Product();

        // Extract text fields from FormData
        $product->setName($request->request->get('name', ''));
        $product->setDescription($request->request->get('description', ''));
        $product->setPrice((float) $request->request->get('price', 0));
        $product->setStock((int) $request->request->get('stock', 0));

        // Handle the category
        $categoryId = $request->request->get('category');
        if ($categoryId) {
            $category = $entityManager->getRepository(Category::class)->find($categoryId);
            if (!$category) {
                return $this->json(['error' => 'Invalid category ID'], Response::HTTP_BAD_REQUEST);
            }
            $product->setCategory($category);
        }

        // Handle file upload
        $imageFile = $request->files->get('imageFile');
        if ($imageFile) {
            $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/images';
            $fileName = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move($uploadDirectory, $fileName);
            $product->setImage($fileName);
        }

        // Validate the product
        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        // Persist the product to the database
        $entityManager->persist($product);
        $entityManager->flush();

        // Return the newly created product
        return $this->json($product, Response::HTTP_CREATED, [], ['groups' => 'product:read']);
    }


    #[Route('/{id}', methods: ['GET'], name: 'show')]
    public function show(int $id, EntityManagerInterface $em, SerializerInterface $serializer): JsonResponse
    {
        $product = $em->getRepository(Product::class)->find($id);
        if (!$product) {
            return new JsonResponse(['error' => 'Product not found'], 404);
        }

        $data = $serializer->serialize($product, 'json', ['groups' => 'product:read']);
        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/{id}', name: 'edit_product', methods: ['PUT', 'POST'])]
    public function editProduct(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ): JsonResponse {
        // Retrieve the product by ID
        $product = $entityManager->getRepository(Product::class)->find($id);

        // If the product is not found, return an error
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }
        // Update the product fields from the request
        $product->setName($request->get('name', $product->getName()));
        $product->setDescription($request->get('description', $product->getDescription()));
        $product->setPrice((float) $request->get('price', $product->getPrice()));
        $product->setStock((int) $request->get('stock', $product->getStock()));

        // Handle category update
        $categoryId = $request->get('category');
        if ($categoryId) {
            $category = $entityManager->getRepository(Category::class)->find($categoryId);
            if (!$category) {
                return $this->json(['error' => 'Invalid category ID'], Response::HTTP_BAD_REQUEST);
            }
            $product->setCategory($category);
        }
        // Handle file upload for image update
        $imageFile = $request->files->get('imageFile');
        if ($imageFile) {
            $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/images';
            $fileName = uniqid() . '.' . $imageFile->guessExtension();
            $imageFile->move($uploadDirectory, $fileName);
            $product->setImage($fileName);
        }

        // Validate the updated product
        $errors = $validator->validate($product);
        if (count($errors) > 0) {
            return $this->json($errors, Response::HTTP_BAD_REQUEST);
        }

        // Persist the changes to the database
        $entityManager->flush();

        // Return the updated product
        return $this->json($product, Response::HTTP_OK, [], ['groups' => 'product:read']);
    }
    #[Route('/{id}', name: 'delete_product', methods: ['DELETE'])]
    public function deleteProduct(int $id, EntityManagerInterface $entityManager): JsonResponse
    {
        // Retrieve the product by ID
        $product = $entityManager->getRepository(Product::class)->find($id);

        // If the product is not found, return a 404 error
        if (!$product) {
            return $this->json(['error' => 'Product not found'], Response::HTTP_NOT_FOUND);
        }

        // Remove the product from the database
        $entityManager->remove($product);
        $entityManager->flush();

        // Return a success response
        return $this->json(['message' => 'Product deleted successfully'], Response::HTTP_OK);
    }
}
