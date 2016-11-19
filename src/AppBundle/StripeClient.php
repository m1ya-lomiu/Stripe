<?php

namespace AppBundle;

use AppBundle\Entity\User;
use Doctrine\ORM\EntityManager;
use Stripe\Customer;

class StripeClient
{
    /** @var EntityManager  */
    private $em;

    public function __construct($secretKey, EntityManager $em)
    {
        $this->em = $em;
        \Stripe\Stripe::setApiKey($secretKey);

    }

    public function createCustomer(User $user, $token)
    {
        $customer = Customer::create(array(
            'email' => $user->getEmail(),
            "source" => $token // obtained with Stripe.js
        ));

        $user->setStripeCustomerId($customer->id);
        $this->em->persist($user);
        $this->em->flush();

        return $customer;
    }

    public function updateCustomerCard(User $user, $token)
    {
        $customer = Customer::retrieve($user->getStripeCustomerId());
        $customer->source = $token;
        $customer->save();

        return $customer;
    }

    public function createInvoiceItem($amount, User $user, $description)
    {
        return \Stripe\InvoiceItem::create(array(
            "amount" => $amount,
            "currency" => "aud",
            'customer' => $user->getStripeCustomerId(),
            "description" => $description
        ));
    }

    public function createInvoice(User $user, $payImmediately = true)
    {
        $invoice = \Stripe\Invoice::create([
            'customer' => $user->getStripeCustomerId()
        ]);

        if ($payImmediately) {
            $invoice->pay();
        }

        return $invoice;
    }
}