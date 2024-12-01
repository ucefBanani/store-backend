<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractController
{
    #[Route('', name: 'app_dashboard')]
    public function index(OrderRepository $orderRepository, ProductRepository $productRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $ordersLivre = $orderRepository->count(['status' => 'livre']);
        $ordersPaid = $orderRepository->count(['status' => 'paid']);
        $ordersPending = $orderRepository->count(['status' => 'pending']);

        // Fetch total products in any category
        $totalProducts = $productRepository->count([]);

        return $this->render('dashboard/index.html.twig', [
            'ordersLivre' => $ordersLivre,
            'ordersPaid' => $ordersPaid,
            'ordersPending' => $ordersPending,
            'totalProducts' => $totalProducts,
        ]);
    }
}
