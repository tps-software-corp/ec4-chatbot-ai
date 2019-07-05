<?php

namespace Plugin\TPSChatbotAI\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\TPSChatbotAI\Form\Type\Admin\ConfigType;
use Plugin\TPSChatbotAI\Repository\ConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/tps_chatbot_ai/config", name="tps_chatbot_ai_admin_config")
     * @Template("@TPSChatbotAI/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);
            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('tps_chatbot_ai_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * 
     * BACKEND
     * 
     * Register business: Sneaker/Shose, Fashion, Cosmsetic -> Response ID -> Reuse
     *  -> Send data
     *  -> Send data as schedule
     *      -> ID, Name, Color, Brand, Size, Price
     * Action after finish conversation: Create order: COD, Card -> move
     * 
     * 
     */
}
