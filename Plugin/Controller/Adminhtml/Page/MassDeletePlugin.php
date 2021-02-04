<?php

declare(strict_types=1);

namespace Mooore\WordpressIntegrationCms\Plugin\Controller\Adminhtml\Page;

use Magento\Cms\Controller\Adminhtml\Page\MassDelete as MassDeleteController;
use Magento\Framework\Exception\LocalizedException;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Cms\Model\ResourceModel\Page\CollectionFactory;
use Mooore\WordpressIntegrationCms\Model\RemotePageRepository;
use Mooore\WordpressIntegrationCms\Model\Config;
use Magento\Framework\Message\ManagerInterface as MessageManager;

class MassDeletePlugin
{
    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * @var RemotePageRepository
     */
    private $remotePageRepository;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var MessageManager
     */
    private $messageManager;

    public function __construct(
        Filter $filter,
        CollectionFactory $collectionFactory,
        RemotePageRepository $remotePageRepository,
        Config $config,
        MessageManager $messageManager
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->remotePageRepository = $remotePageRepository;
        $this->config = $config;
        $this->messageManager = $messageManager;
    }

    /**
     * This function calls to Wordpress to delete the Magento URL entry in a MassDelete action.
     *
     * @param MassDeleteController $subject
     * @return void
     */
    public function beforeExecute(MassDeleteController $subject)
    {
        foreach ($this->filter->getCollection($this->collectionFactory->create())->getData() as $page) {
            if (!array_key_exists('wordpress_page_id', $page) ||
                null === $page['wordpress_page_id'] ||
                !$this->config->magentoUrlPushBackEnabled()
            ) {
                continue;
            }

            $wordpressSiteAndPageId = explode('_', $page['wordpress_page_id']);
            try {
                $this->remotePageRepository->postMetaData(
                    (int) $wordpressSiteAndPageId[0],
                    (int) $wordpressSiteAndPageId[1],
                    'mooore_magento_cms_url',
                    ''
                );
            } catch (LocalizedException $exception) {
                $this->messageManager->addErrorMessage($exception->getMessage());
                continue;
            }
        }
    }
}
