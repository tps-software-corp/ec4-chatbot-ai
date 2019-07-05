<?php

namespace Plugin\TPSChatbotAI\Controller;

use Eccube\Controller\AbstractController;
use Plugin\TPSChatbotAI\Form\Type\Admin\ConfigType;
use Plugin\TPSChatbotAI\Service\ConvensionService;
use Plugin\TPSChatbotAI\Repository\ConfigRepository;
use Plugin\TPSChatbotAI\Repository\TPSChatbotAIProductRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Common\EccubeConfig;


class ConvensionController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;
    private $baseUri = 'http://192.168.50.109:5005/';
    private $webhook = 'http://192.168.50.109:5005/webhooks/rest/webhook';
    private $client;
    private $convensionService;

    /**
     * ConvensionController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository
    , ConvensionService $convensionService
    , TPSChatbotAIProductRepository $TPSChatbotAIProductRepository
    )
    {
        $this->configRepository = $configRepository;
        $this->productRepository = $TPSChatbotAIProductRepository;
        $this->convensionService = $convensionService;
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
        $resData = $this->convensionService->process($sender, $request);
        return $this->json($resData);
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
}