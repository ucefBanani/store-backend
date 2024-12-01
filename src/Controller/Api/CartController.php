<?php

namespace App\Controller\Api;

use App\Entity\CartItem;
use App\Repository\CartItemRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class CartController extends AbstractController
{
    #[Route('/api/cart', name: 'api_cart', methods: ['GET'])]
    public function getCartItems(
        CartItemRepository $repository,
        SerializerInterface $serializer
    ): JsonResponse {
        // Get the authenticated user
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['message' => 'Unauthorized'], 401);
        }

        // Find cart items for the authenticated user
        $cartItems = $repository->findBy(['user' => $user]);

        // Serialize the data
        $data = $serializer->serialize(
            $cartItems,
            'json',
            ['groups' => ['cart:read']]
        );

        return new JsonResponse($data, 200, [], true);
    }

    #[Route('/api/cart/add', name: 'api_cart_add', methods: ['POST'])]
    public function addToCart(
        Request $request,
        ProductRepository $productRepository,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();
        $product = $productRepository->find($data['productId']);

        // Check if the cart item already exists for the user and product
        $existingItem = $em->getRepository(CartItem::class)->findOneBy(['product' => $product, 'user' => $user]);

        if ($existingItem) {
            // Update quantity if item already exists
            $existingItem->setQuantity($existingItem->getQuantity() + 1);
            $em->flush();
        } else {
            // Add a new cart item
            $cartItem = new CartItem();
            $cartItem->setProduct($product);
            $cartItem->setUser($user);
            $cartItem->setQuantity(1);
            $cartItem->setPrice($product->getPrice() * $cartItem->getQuantity());

            $em->persist($cartItem);
            $em->flush();
        }


        return $this->json(['message' => 'Item added to cart'], 201);
    }

    #[Route('/api/cart/remove', name: 'api_cart_remove', methods: ['POST'])]
    public function removeFromCart(
        Request $request,
        EntityManagerInterface $em,
        CartItemRepository $cartItemRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $user = $this->getUser();

        // Check if productId is passed
        if (!isset($data['productId'])) {
            return $this->json(['message' => 'Product ID is required'], 400);
        }

        $productId = $data['productId'];

        // Find the cart item for the user and product
        $cartItem = $cartItemRepository->findOneBy(['id' => $productId, 'user' => $user]);

        if (!$cartItem) {
            return $this->json(['message' => 'Cart item not found'], 404);
        }

        // Remove the cart item
        $em->remove($cartItem);
        $em->flush();

        return $this->json(['message' => 'Item removed from cart'], 200);
    }
}
