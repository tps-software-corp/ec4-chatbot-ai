<?php
namespace Plugin\TPSChatbotAI\Service;

use Eccube\Repository\ProductRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\ClassCategoryRepository;
use Eccube\Entity\Shipping;
use Eccube\Form\Type\Front\CustomerLoginType;
use Eccube\Form\Type\Front\ShoppingShippingType;
use Eccube\Form\Type\Shopping\CustomerAddressType;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\OrderRepository;
use Eccube\Service\CartService;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\TPSChatbotAI\Repository\TPSChatbotAIProductRepository;

class TPSChatbotAICartService
{
    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    protected $productRepository;
    protected $productClassRepository;
    protected $classCategoryRepository;
    protected $entityManager;
    protected $baseinfo;

    public function __construct(
        TPSChatbotAIProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->baseinfo = $entityManager->getRepository('Eccube\Entity\BaseInfo')->get();
        $this->productRepository = $productRepository;
    }

    public function createOrder($productIds, $shippingAdress, $paymentMethod, $userId = null)
    {

    }
}