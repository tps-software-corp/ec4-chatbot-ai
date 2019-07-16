<?php

namespace Plugin\TPSChatbotAI\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Controller\AbstractShoppingController;
use Eccube\Controller\CartController;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\OrderStatus;
use Plugin\TPSChatbotAI\Form\Type\Admin\ConfigType;
use Plugin\TPSChatbotAI\Service\ConvensionService;
use Plugin\TPSChatbotAI\Repository\ConfigRepository;
use Plugin\TPSChatbotAI\Repository\TPSChatbotAIProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Common\EccubeConfig;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Repository\ProductRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\ClassNameRepository;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;



class ConvensionController extends CartController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;
    private $baseUri = 'http://192.168.50.109:5005/';
    private $webhook = 'http://192.168.50.109:5005/webhooks/rest/webhook';
    private $client;
    private $convensionService;
    private $productRepository;
    private $mailService;

    /**
     * ConvensionController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository
    , ConvensionService $convensionService
    , ProductRepository $productRepository
    , ProductClassRepository $productClassRepository
    , PrefRepository $prefRepository
    , OrderHelper $orderHelper
    , MailService $mailService
    , ClassNameRepository $classNameRepository
    , CartService $cartService
    , PurchaseFlow $cartPurchaseFlow
    , TPSChatbotAIProductRepository $TPSChatbotAIProductRepository
    )
    {
        $this->mailService = $mailService;
        $this->configRepository = $configRepository;
        $this->productRepository = $TPSChatbotAIProductRepository;
        $this->convensionService = $convensionService;
        $this->purchaseFlow = $cartPurchaseFlow;
        $this->cartService = $cartService;
        $this->orderHelper = $orderHelper;
        $this->productRepository = $productRepository;
        $this->productClassRepository = $productClassRepository;
        $this->classNameRepository = $classNameRepository;
        $this->prefRepository = $prefRepository;
        $this->client = new \GuzzleHttp\Client([
            'headers' => [
                'Accept' => 'application/x-www-form-urlencoded'
            ]
        ]);
    }

    /**
     * @Route("/tps_chatbot_ai/conv/room", name="tps_chatbot_ai_convension_room", methods={"POST"})
     */
    public function room(Request $request)
    {
        $config = $this->configRepository->get();
        $sender = $config->getUid();
        $sender .= '__' . ($this->getUser() ? $user->getId() : $request->getSession()->getId());
        $resData = $this->convensionService->process($sender, $request, $this);
        return $this->json($resData[0]);
    }

    /**
     * @Route("/tps_chatbot_ai/search", name="tps_chatbot_ai_search", methods={"GET"})
     */
    public function saerch(Request $request)
    {
        foreach($this->productRepository->searchProduct(['la']) as $p) {
            foreach($p->getProductImage() as $image) {
                dump('/upload/save_image/' . $image->getFileName());
            }
        }
        die;
    }

    /**
     * @Route("/tps_chatbot_ai/data/{uid}", name="tps_chatbot_ai_data", methods={"GET"})
     */
    public function provideStoreData(Request $request)
    {
        $config = $this->configRepository->findOneBy(['uid' => $request->get('uid')]);
        if ($config) {
            $data = [];
            $className = $this->classNameRepository->findAll();
            foreach($className as $class) {
                foreach($class->getClassCategories() as $name) {
                    $data[$class->getBackendName()][] = $name->getBackendName();
                }
            }
            return $this->json($data);
        }
        throw $this->createNotFoundException('Your uid key is not valid in our system');
    }

    /**
     * @Route("/demo/product_list", name="demo_product_list", methods={"GET"})
     */
    public function product_list(Request $request)
    {
        $data = [];
        $limit = $request->get('limit', 20);
        $page = $request->get('page', 0);
        for($i = 0; $i < $limit; $i++)
        {
            $data[] = $this->__generateSingleProduct();
        }
        return $this->json($data);
    }

    /**
     * @Route("/demo/product_detail/{id}", requirements={"id" = "\d+"}, name="demo_product_detail", methods={"GET"})
     */
    public function product_detail(Request $request)
    {
        return $this->json($this->__generateSingleProduct());
    }

    private function __generateSingleProduct()
    {
        $faker = \Faker\Factory::create();
        return [
            'id' => $faker->randomNumber(3  ),
            'name' => $faker->text(20),
            'desc' => $faker->text(500),
            'price01' => $faker->randomNumber(6),
            'price02' => $faker->randomNumber(6),
            'colors' => [
                $faker->colorName,
                $faker->colorName,
            ],
            'images' => [
                $faker->imageUrl(),
                $faker->imageUrl(),
                $faker->imageUrl(),
                $faker->imageUrl(),
                $faker->imageUrl(),
            ]
        ];
    }

    public function execPurchaseFlow($details)
    {
        $this->cartService->clear();
        if (empty($details)) {
            return;
        }
        $email = isset($details['shipping_address']['recipient_email']) ? $details['shipping_address']['recipient_email'] : null;
        $name01 = $details['shipping_address']['recipient_name'];
        $name02 = '';
        $phoneNumber = $details['shipping_address']['phone_number'];
        $addr01 = $details['shipping_address']['region'];
        $addr02 = $details['shipping_address']['street'];
        $shippingMessage = $details['shipping_address']['shipping_note'];
        $pref = $this->prefRepository->findOneBy(['name' => $details['shipping_address']['city']]);
        $names = explode(' ', $name01);
        if (count($names) > 1) {
            $name01 = array_pop($names);
            $name02 = implode(' ', $names);
        }
        $paymentMethod = $details['payment_method'];
        $productIds  = $details['product_ids'];
        $shippingAddress  = $details['shipping_address'];
        $productClasses = $this->productClassRepository->findBy(['id' => $details['product_ids']]);

        $Customer = $this->getUser();
        if (!$Customer) {
            $Customer = new Customer();
            $Customer
                ->setName01($name01)
                ->setName02($name02)
                ->setKana01('イーシーキューブ')
                ->setKana02('イーシーキューブ')
                ->setCompanyName(null)
                ->setEmail($email)
                ->setPhonenumber($phoneNumber)
                ->setPostalcode(null)
                ->setPref($pref)
                ->setAddr01($addr01)
                ->setAddr02($addr02);
        }
        $this->session->set(OrderHelper::SESSION_NON_MEMBER, $Customer);

        foreach($productClasses as $productClass) {
            $this->cartService->addProduct($productClass, 1);
        }
        $Cart = $this->cartService->getCart();
        $this->purchaseFlow->validate($Cart, new PurchaseContext());
        $this->cartService->save();
        if ($paymentMethod !== 'cod') {
            return $this->generateUrl('cart_buystep', ['cart_key' => $Cart->getCartKey()]);
        }
        $Order = $this->orderHelper->initializeOrder($Cart, $Customer);
        $Order->setMessage($shippingMessage);
        $OrderStatus = $this->entityManager->find(OrderStatus::class, OrderStatus::NEW);
        $Order->setOrderStatus($OrderStatus);
        $flowResult = $this->purchaseFlow->validate($Order, new PurchaseContext(clone $Order, $Order->getCustomer()));
        if ($Customer->getEmail()) {
            $this->mailService->sendOrderMail($Order);
        }
        $this->entityManager->flush();

        $errors = [];
        foreach ($flowResult->getWarning() as $warning) {
            $errors[] = $warning->getMessage();
        }
        foreach ($flowResult->getErrors() as $error) {
            $errors[] = $error->getMessage();
        }

        if ($flowResult->hasError()) {
            return $errors;
        }

        if ($flowResult->hasWarning()) {
            return $errors;
        }

        return null;
    }
}