<?php
namespace Canon\CatalogSearch\Plugin\Controller\Result;

use Magento\CatalogSearch\Controller\Result\Index as ResultIndex;
use Magento\Catalog\Model\Layer\Resolver;
use Magento\Search\Model\QueryFactory;
use Magento\CatalogSearch\Helper\Data as CatalogSearchHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\ViewInterface;

class IndexPlugin
{
    /**
     * @var Resolver
     */
    private $layerResolver;

    /**
     * @var QueryFactory
     */
    private $queryFactory;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CatalogSearchHelper
     */
    private $catalogSearchHelper;

    /**
     * @var ViewInterface
     */
    private $view;

    /**
     * @param Resolver $layerResolver
     * @param QueryFactory $queryFactory
     * @param StoreManagerInterface $storeManager
     * @param CatalogSearchHelper $catalogSearchHelper
     * @param ViewInterface $view
     */
    public function __construct(
        Resolver $layerResolver,
        QueryFactory $queryFactory,
        StoreManagerInterface $storeManager,
        CatalogSearchHelper $catalogSearchHelper,
        ViewInterface $view
    ) {
        $this->layerResolver = $layerResolver;
        $this->queryFactory = $queryFactory;
        $this->storeManager = $storeManager;
        $this->catalogSearchHelper = $catalogSearchHelper;
        $this->view = $view;
    }

    /**
     * Around execute to render search result layout directly when q is empty without redirecting
     *
     * @param ResultIndex $subject
     * @param callable $proceed
     * @return mixed
     */
    public function aroundExecute(ResultIndex $subject, callable $proceed)
    {
        $request = $subject->getRequest();
        $queryText = $request->getParam(QueryFactory::QUERY_VAR_NAME);

        if ($queryText === null || trim((string)$queryText) === '') {
            $this->layerResolver->create(Resolver::CATALOG_LAYER_SEARCH);

            $query = $this->queryFactory->get();
            $storeId = $this->storeManager->getStore()->getId();
            $query->setStoreId($storeId);
            $query->setNumResults(6);

            $this->catalogSearchHelper->checkNotes();

            $this->view->loadLayout(['default', 'catalogsearch_result_index']);
            $this->view->renderLayout();
            return null;
        }

        return $proceed();
    }
}
