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
    const PAYLOAD_SEARCH_PRODUCT_EMPTY = '/no_result';
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

    private $sender;
    private $cartController;

    public function __construct(
        TPSChatbotAIProductRepository $productRepository,
        TPSChatbotAICartService $cartService,
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
        $this->cartService = $cartService;
        $this->baseinfo = $entityManager->getRepository('Eccube\Entity\BaseInfo')->get();
        $this->productRepository = $productRepository;
        $this->client = new \GuzzleHttp\Client([
            'headers' => [
                'Accept' => 'application/x-www-form-urlencoded'
            ]
        ]);
    }
    protected function _send($message)             
    {
        $resData = [];
        switch ($message) {
            case '/inform/cod':
                $message = '/inform{"payment_method":"cod"}';
                break;
            case '/inform/other':
                $message = '/inform{"payment_method":"other"}';
                break;
            default:
                break;
        }
        $data = [
            'sender' => $this->sender,
            'message' => $message
        ];
        $response = $this->client->request('POST', $this->webhook, ['body' => json_encode($data)]);
        $resData = json_decode((string)$response->getBody(), true);
        return $resData;
    }

    protected function _processData($data)
    {
        // var_dump($data); die;
        $actions = [];
        foreach($data as $con) {
            $custom = $con['custom'];
            $buttons = isset($custom['buttons']) ? $custom['buttons'] : null;
            $action = isset($custom['action']) ? $custom['action'] : '';
            $message = isset($custom['text']) ? $custom['text'] : '';
            $details = isset($custom['details']) ? $custom['details'] : null;
            $products = [];
            $redirect = null;
            $table = [];
            switch($action) {
                case 'eccube_give_location':
                    $message = implode(', ', [$this->baseinfo->getAddr01(), $this->baseinfo->getAddr02(), $this->baseinfo->getPref(), $this->baseinfo->getPostalCode()]);
                    break;
                case 'eccube_search_product':
                    $criterias = [];
                    foreach($details as $d) {
                        foreach($d as $att => $val) {
                            $criterias[] = $val;
                        }
                    }
                    $products = $this->_transferProducts($this->productRepository->searchProduct($criterias));
                    if (empty($products )) {
                        $resData = $this->_send(self::PAYLOAD_SEARCH_PRODUCT_EMPTY);
                        return $this->_processData($resData);
                    }
                    break;
                case 'eccube_ask_criteria':
                    $buttons = null;
                    break;
                case 'eccube_confirm_cart':
                    $buttons = [
                        [
                            'payload' => 'có',
                            'title' => 'Có, tôi muốn thanh toán',
                        ],
                        [
                            'payload' => 'không',
                            'title' => 'Không, tôi muốn tìm món hàng khác'
                        ],
                    ];
                    break;
                case 'eccube_ask_payment_method':
                    $buttons = [
                        [
                            'payload' => '/inform/cod',
                            'title' => 'COD',
                        ],
                        [
                            'payload' => '/inform/other',
                            'title' => 'Khác'
                        ],
                    ];
                    break;
                case 'eccube_order_create':
                    $result = $this->cartController->execPurchaseFlow($details);
                    $message = 'Đơn hàng của bạn đã được tạo. Vui lòng kiểm tra email để xem thêm thông tin chi tiết đơn hàng';
                    if ($result) {
                        if (is_array($result)) {
                            $message = implode(', ', $result);
                        }
                        if (!is_array($result) && strpos($result, '/cart') === 0) {
                            $message = 'Bạn vui lòng chờ chuyển đến tranh thanh toán....';
                            $buttons = null;
                            $redirect = $result;
                        }
                    }
                    $buttons = [
                        [
                            'payload' => '/greet',
                            'title' => 'Tiêp tục mua hàng',
                        ]
                    ];
                    break;
            }
            $actions[] = [
                'text' => $action,
                'message' => $message ? $message : $action,
                'buttons' => $buttons,
                'products' => $products,
                'table' => $table,
                'redirect' => $redirect,
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

    public function process($sender, $request, $controller)
    {
        $this->sender = $sender;
        $this->cartController = $controller;
        $data = $this->_send($request->request->get('message'));
        return $this->_processData($data);
    }
}