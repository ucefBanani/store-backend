<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\EmailService;
use App\Service\PaymentService;
use App\Service\OrderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class OrderController extends AbstractController
{
    private OrderService $orderService;
    private PaymentService $paymentService;

    private EmailService $emailService;


    public function __construct(OrderService $orderService, PaymentService $paymentService , EmailService $emailService)
    {
        $this->orderService = $orderService;
        $this->paymentService = $paymentService;
        $this->emailService = $emailService;
    }

    #[Route('/api/orders', name: 'create_order', methods: ['POST'])]
    public function create(Request $request, Security $security): JsonResponse
    {
        // Retrieve the currently authenticated user
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['items'])) {
            return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Create a new Order
        $order = new Order();
        $order->setUser($user); // Associate the order with the user

        try {
            // Call the service to handle order creation and retrieve the order ID
            $orderId = $this->orderService->createOrder($order, $data['items']);

            // Call the payment service to handle payment intent creation
            // Here, we assume the total price is already set on the Order entity
            $paymentIntent = $this->paymentService->createPaymentIntent($order);

            // Delete products from the cart
            $this->orderService->deleteCartItems($user, $data['items']);  // Delete the products from the cart

                 // Send order confirmation email
                $this->emailService->sendEmail(
                    $user->getEmail(),
                    'Order Confirmation',
                    'emails/order_confirmation.html.twig',
                    ['order' => $order, 'user' => $user]
                );

            // Return a success response with the order ID and payment intent client secret
            return new JsonResponse([
                'id' => $orderId,
                'status' => 'Order created',
                'client_secret' => $paymentIntent->client_secret // Send this to the frontend to complete the payment
            ], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            // Catch exceptions and return a JSON response with the error message
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_BAD_REQUEST);
        }
    }


    #[Route('/api/orders', name: 'get_user_orders', methods: ['GET'])]
    public function getOrders(Security $security, OrderRepository $orderRepository): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'User not authenticated'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $orders = $orderRepository->findBy(['user' => $user]);

        return $this->json($orders, JsonResponse::HTTP_OK, [], [
            AbstractNormalizer::GROUPS => ['order:read'],
        ]);
    }

    #[Route('/api/orders/confirm-payment', name: 'confirm_payment', methods: ['POST'])]
    public function confirmPayment(Request $request, OrderRepository $orderRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $paymentIntentId = $data['paymentIntentId'] ?? null;
        $paymentIntentStatus = $data['status'] ?? null;

        if (!$paymentIntentId || !$paymentIntentStatus) {
            return new JsonResponse(['error' => 'Invalid data'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // You can now confirm the payment intent status (successful or failed)
        $order = $orderRepository->findOneBy(['paymentIntentId' => $paymentIntentId]);

        if ($order) {
            if ($paymentIntentStatus === 'succeeded') {
                $this->paymentService->handlePaymentSuccess($order, $paymentIntentId);
                return new JsonResponse(['status' => 'Payment completed']);
            } else {
                $this->paymentService->handlePaymentFailure($order, $paymentIntentId);
                return new JsonResponse(['status' => 'Payment failed']);
            }
        }

        return new JsonResponse(['error' => 'Order not found'], JsonResponse::HTTP_NOT_FOUND);
    }
}
