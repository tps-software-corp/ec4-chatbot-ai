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

class ConvensionService
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

    private $client;
    private $webhook = 'http://192.168.50.109:5005/webhooks/rest/webhook';

    public function __construct(
        TPSChatbotAIProductRepository $productRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->baseinfo = $entityManager->getRepository('Eccube\Entity\BaseInfo')->get();
        $this->productRepository = $productRepository;
        $this->client = new \GuzzleHttp\Client([
            'headers' => [
                'Accept' => 'application/x-www-form-urlencoded'
            ]
        ]);
    }

    protected function _send($sender, $message)
    {

        $data = [
            'sender' => $sender,
            'message' => $message
        ];
        // var_dump($sender); die;
        $response = $this->client->request('POST', $this->webhook, ['body' => json_encode($data)]);
        $resData = json_decode((string)$response->getBody(), true);
        return $resData;
    }

    protected function _processData($data)
    {
        $actions = [];
        foreach($data as $con) {
            $custom = $con['custom'];
            $buttons = isset($custom['buttons']) ? $custom['buttons'] : null;
            $action = isset($custom['action']) ? $custom['action'] : '';
            $message = isset($custom['text']) ? $custom['text'] : '';
            $products = [];
            switch($action) {
                case 'eccube_give_location':
                    $message = implode(', ', [$this->baseinfo->getAddr01(), $this->baseinfo->getAddr02(), $this->baseinfo->getPref(), $this->baseinfo->getPostalCode()]);
                    break;
                case 'eccube_search_product':
                    $criterias = [];
                    $details = $custom['details'];
                    foreach($details as $d) {
                        foreach($d as $att => $val) {
                            $criterias[] = $val;
                        }
                    }
                    $products = $this->_transferProducts($this->productRepository->searchProduct($criterias));
                    break;
            }
            // $products = $this->_transferProducts($this->productRepository->searchProduct(['Cô']));
            $actions[] = [
                'text' => $action,
                'message' => $message ? $message : $action,
                'buttons' => $buttons,
                'products' => $products,
                '__raw' => $data,
            ];
        }
        return $actions;
    }

    protected function _transferProducts($products)
    {
        $data = [];
        foreach($products as $p) {
            $images = [];
            foreach($p->getProductImage() as $image) {
                $images[] = '/html/upload/save_image/' . $image->getFileName();
            }
            $data[] = [
                'name' => $p->getName(),
                'price01' => number_format($p->getPrice01Min(), 0),
                'price02' => number_format($p->getPrice02Min(), 0),
                'images' => $images ,
                'product_id' => $p->getId(),
            ];
        }
        return $data;
    }

    public function process($sender, $request)
    {
        $data = $this->_send($sender, $request->request->get('message'));
        return $this->_processData($data);
    }
}