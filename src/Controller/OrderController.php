<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\EmailService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/orders')]
class OrderController extends AbstractController
{

    private EmailService $emailService;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager, EmailService $emailService)
    {
        $this->entityManager = $entityManager;
        $this->emailService = $emailService;
    }

    #[Route('', name: 'admin_order_list', methods: ['GET'])]
    public function listOrders(Request $request, OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        // Get filter parameters from request
        $status = $request->query->get('status');
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        // Start building the query
        $qb = $orderRepository->createQueryBuilder('o');

        // Apply filters based on the presence of query parameters
        if ($status) {
            $qb->andWhere('o.status = :status')
                ->setParameter('status', $status);
        }

        if ($startDate) {
            $qb->andWhere('o.createdAt >= :start_date')
                ->setParameter('start_date', new \DateTime($startDate));
        }

        if ($endDate) {
            $qb->andWhere('o.createdAt <= :end_date')
                ->setParameter('end_date', new \DateTime($endDate));
        }

        // Execute the query to get the filtered orders
        $orders = $qb->getQuery()->getResult();

        return $this->render('order/index.html.twig', [
            'orders' => $orders,
        ]);
    }


    #[Route('/{id}', name: 'admin_order_details', methods: ['GET'])]
    public function showOrderDetails(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        return $this->render('order/show.html.twig', [
            'order' => $order,
        ]);
    }

    #[Route('/{id}/change-status', name: 'admin_order_change_status', methods: ['POST'])]
    public function changeOrderStatus(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($order->getStatus() === Order::STATUS_DELIVERED) {
            $this->addFlash('warning', 'Order is already delivered.');
            return $this->redirectToRoute('admin_order_list');
        }

        $this->emailService->sendEmail(
            $order->getUser()->getEmail(),
            'Order Delivered',
            'emails/order_delivered.html.twig',
            ['order' => $order]
        );

        $order->setStatus(Order::STATUS_DELIVERED);
        $this->entityManager->flush();

        $this->addFlash('success', 'Order status updated to "livré".');
        return $this->redirectToRoute('admin_order_list');
    }
}
