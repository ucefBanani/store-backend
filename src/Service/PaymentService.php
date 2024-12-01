<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\Payment;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\PaymentIntent;

class PaymentService
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
        Stripe::setApiKey('sk_test_51QQSaZKjFA5v1AzgiVbSIDU5BUOtxo2k11lycnUw4vdlbLodCegTTQYwe6yg0ZquGPMdWeYOnZzxY5zDg3ZkUYnM00PBwS8P4p');
    }

    public function createPaymentIntent(Order $order): PaymentIntent
    {
        // Create a payment intent with the amount
        $paymentIntent = PaymentIntent::create([
            'amount' => $order->getTotal() * 100,  // Stripe expects the amount in cents
            'currency' => 'usd',  // You can set the appropriate currency
            'metadata' => ['order_id' => $order->getId()]
        ]);

        // Save the payment intent ID in the order
        $order->setPaymentIntentId($paymentIntent->id);

        // Persist the updated order with the paymentIntentId
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $paymentIntent;
    }

    public function handlePaymentSuccess(Order $order, string $paymentIntentId): Payment
    {
        // Payment was successful
        $payment = new Payment();
        $payment->setOrder($order);
        $payment->setPaymentStatus(Payment::STATUS_COMPLETED);
        $payment->setPaymentMethod('Stripe');
        $payment->setAmount($order->getTotal());
        $payment->setTransactionId($paymentIntentId);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        // Update the order status to 'paid'
        $order->setStatus('paid');
        $this->entityManager->persist($order);
        $this->entityManager->flush();

        return $payment;
    }

    public function handlePaymentFailure(Order $order, string $paymentIntentId): void
    {
        // Log failure or notify the user
        $payment = new Payment();
        $payment->setOrder($order);
        $payment->setPaymentStatus(Payment::STATUS_FAILED);
        $payment->setAmount($order->getTotal());
        $payment->setPaymentMethod('Stripe');
        $payment->setTransactionId($paymentIntentId);

        $this->entityManager->persist($payment);
        $this->entityManager->flush();

        // Update the order status to 'failed'
        $order->setStatus('failed');
        $this->entityManager->persist($order);
        $this->entityManager->flush();
    }
}
