<?php

/**
Пример обновления URL REWRITES с сохранением старых.
Для продуктов лучше задать новые url_path & url_key, задать ключ `save_rewrites_history` и сохранить продукт
В этом случае сгенерятся новые урлы и создадутся редиректы на все старые 
*/



namespace Mygento\Seo\Command;

use Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RegenerateProductUrl extends \Symfony\Component\Console\Command\Command
{
    /**
     * @var OutputInterface
     */
    private $output;
    /**
     * @var InputInterface
     */
    private $input;
    /**
     * @var \Magento\Framework\App\State
     */
    private $state;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    private $collection;

    /**
     * @var \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator\Proxy
     */
    private $productUrlGenerator;

    /**
     * @var \Magento\UrlRewrite\Model\UrlPersistInterface\Proxy
     */
    private $urlPersist;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface\Proxy
     */
    private $storeManager;
    /**
     * @var \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator\Proxy
     */
    private $productUrlPathGenerator;
    /**
     * @var \Magento\UrlRewrite\Model\UrlRewriteFactory
     */
    private $urlRewriteFactory;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;
    /**
     * @var \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory
     */
    private $urlRewriteCollectionFactory;

    /**
     * @param \Magento\Framework\App\State $state
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collection
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator\Proxy $productUrlGenerator
     * @param \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator\Proxy $productUrlPathGenerator
     * @param \Magento\UrlRewrite\Model\UrlPersistInterface\Proxy $urlPersist
     * @param \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory
     * @param \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory $urlRewrCollFactory
     * @param \Magento\Store\Model\StoreManagerInterface\Proxy $storeManager
     */
    public function __construct(
        \Magento\Framework\App\State $state,
        \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $collection,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\CatalogUrlRewrite\Model\ProductUrlRewriteGenerator\Proxy $productUrlGenerator,
        \Magento\CatalogUrlRewrite\Model\ProductUrlPathGenerator\Proxy $productUrlPathGenerator,
        \Magento\UrlRewrite\Model\UrlPersistInterface\Proxy $urlPersist,
        \Magento\UrlRewrite\Model\UrlRewriteFactory $urlRewriteFactory,
        \Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollectionFactory $urlRewrCollFactory,
        \Magento\Store\Model\StoreManagerInterface\Proxy $storeManager
    ) {
        parent::__construct();
        $this->state                       = $state;
        $this->collection                  = $collection;
        $this->productUrlGenerator         = $productUrlGenerator;
        $this->urlPersist                  = $urlPersist;
        $this->storeManager                = $storeManager;
        $this->productUrlPathGenerator     = $productUrlPathGenerator;
        $this->urlRewriteFactory           = $urlRewriteFactory;
        $this->productRepository           = $productRepository;
        $this->urlRewriteCollectionFactory = $urlRewrCollFactory;
    }

    protected function configure()
    {
        $this->setName('seo:products:update:urlrewrites')
            ->setDescription('Regenerate url rewrites for products. ' .
                    'Add redirects from old paths to new request paths.')
            ->addOption(
                'store',
                's',
                InputOption::VALUE_REQUIRED,
                'Regenerate for one specific store view',
                \Magento\Store\Model\Store::DEFAULT_STORE_ID
            )
            ->addOption(
                'productId',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Product id. If you want to regenerate for single product',
                \Magento\Store\Model\Store::DEFAULT_STORE_ID
            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input  = $input;
        $this->output = $output;

        try {
            $this->state->getAreaCode();
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $this->state->setAreaCode('adminhtml');
        }

        $storeId   = $input->getOption('store');
        $productId = $input->getOption('productId');
        $stores    = $this->storeManager->getStores(false);

        foreach ($stores as $store) {
            // SKIP not selected stores
            if ($storeId != \Magento\Store\Model\Store::DEFAULT_STORE_ID &&
                $store->getId() != $storeId) {
                continue;
            }

            $collection = $this->collection->create()
                ->addStoreFilter($store->getId())
                ->setStoreId($store->getId());

            if ($productId) {
                $collection->addIdFilter($productId);
            }

            $collection->addAttributeToSelect(['*']);

            /** @var ProgressBar $progressBar */
            $progressBar = new ProgressBar($output, $collection->count());
            $progressBar->setFormat(
                '<info>%message%</info> %current%/%max% [%bar%] %percent:3s%% %elapsed% %memory:6s%'
            );
            $progressBar->setMessage('Url Rewrites updating started.');
            $output->writeln('<info>Updates url rewrites for products.</info>');
            $progressBar->start();
            $progressBar->display();

            $this->updateOldRewrites($collection, $progressBar);

            $progressBar->finish();
            $output->writeln('');
            $output->writeln('<comment>Don\'t forget to clear cache via `cache:flush` command</comment>');
        }
    }

    /**
     * @param \Magento\Catalog\Model\ResourceModel\Product\Collection $products
     * @param ProgressBar $progressBar
     * @throws \Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException
     */
    protected function updateOldRewrites($products, $progressBar)
    {
        foreach ($products as $product) {
            $oldUrls = $this->getOldUrlRewrites($product->getId());
            $newUrls = $this->productUrlGenerator->generate($product);
            $this->urlPersist->replace($newUrls);

            //Redirect from old url to new one
            foreach ($newUrls as $newUrl) {
                foreach ($oldUrls as $oldUrl) {
                    if ($oldUrl->getTargetPath() != $newUrl->getTargetPath()) {
                        continue;
                    }
                    if ($oldUrl->getRequestPath() === $newUrl->getRequestPath()) {
                        continue;
                    }

                    $this->urlRewriteFactory->create()
                        ->setStoreId($newUrl->getStoreId())
                        ->setEntityType($newUrl->getEntityType())
                        ->setEntityId($newUrl->getEntityId())
                        ->setRequestPath($oldUrl->getRequestPath())
                        ->setTargetPath($newUrl->getRequestPath())
                        ->save();
                }
            }
            $progressBar->setMessage('Product id: ' . $product->getId());
            $progressBar->advance();
        }
        $this->output->writeln('');
    }

    protected function getOldUrlRewrites($productId)
    {
        $rewrites = $this->urlRewriteCollectionFactory->create()
            ->addFilter('entity_id', $productId);

        return $rewrites->getItems();
    }
}
