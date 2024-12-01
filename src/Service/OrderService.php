<?php


namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Repository\CartItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

class OrderService
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        readonly CartItemRepository $cartItemRepository
    ) {
        $this->entityManager = $entityManager;
    }

    public function createOrder(Order $order, array $orderItemsData)
    {
        $total = 0;

        foreach ($orderItemsData as $itemData) {
            // Get the product based on the item data
            $product = $this->entityManager->getRepository(Product::class)->find($itemData['productId']);
            if (!$product) {
                // If product is not found, throw an exception
                throw new \Exception('Product not found');
            }

            // Check if the quantity exceeds the available stock
            if ($itemData['quantity'] > $product->getStock()) {
                throw new \Exception('Not enough stock available for product ' . $product->getName());
            }

            // Create the OrderItem and associate it with the order
            $orderItem = new OrderItem();
            $orderItem->setProduct($product);
            $orderItem->setQuantity($itemData['quantity']);
            $orderItem->setPrice($product->getPrice() * $itemData['quantity']);
            $orderItem->setOrder($order);

            // Update the product stock
            $this->updateProductStock($product, $itemData['quantity']);

            // Add to the total order price
            $total += $orderItem->getPrice();

            // Persist the OrderItem
            $this->entityManager->persist($orderItem);
        }

        // Optionally, you could set the total price on the Order entity
        $order->setTotal($total);

        // Persist the order (if not already done)
        $this->entityManager->persist($order);

        // Flush all changes to the database
        $this->entityManager->flush();

        // Return the order ID for the controller to handle the response
        return $order->getId();
    }

    private function updateProductStock(Product $product, int $quantity): void
    {
        // Decrease the product stock by the quantity ordered
        $newStock = $product->getStock() - $quantity;

        // Ensure stock doesn't go negative
        if ($newStock < 0) {
            throw new \Exception('Not enough stock available');
        }

        // Update the stock in the Product entity
        $product->setStock($newStock);

        // Persist the updated product
        $this->entityManager->persist($product);
    }

 function deleteCartItems(User $user, array $items)
{
    // Loop through the items and delete them from the user's cart
    foreach ($items as $item) {
        // Assuming you have a Cart entity and a relationship to the User entity
        $cartItem = $this->cartItemRepository->findOneBy([
            'user' => $user,
            'product' => $item['productId']
        ]);

        if ($cartItem) {
            // Remove the item from the cart
            $this->entityManager->remove($cartItem);  // Use EntityManager to remove the entity
        }
    }

    // Persist the changes to the database
    $this->entityManager->flush();
}

}
