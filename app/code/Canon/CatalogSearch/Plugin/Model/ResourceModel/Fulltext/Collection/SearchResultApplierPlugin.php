<?php
namespace Canon\CatalogSearch\Plugin\Model\ResourceModel\Fulltext\Collection;

use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection\SearchResultApplierInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Search\Model\QueryFactory;

class SearchResultApplierPlugin
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @param RequestInterface $request
     */
    public function __construct(RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Bypass adding where('NULL') when search query is empty or wildcard
     *
     * @param SearchResultApplierInterface $subject
     * @param callable $proceed
     * @return void
     */
    public function aroundApply(SearchResultApplierInterface $subject, callable $proceed)
    {
        $fullActionName = $this->request->getFullActionName();

        if ($fullActionName === 'catalogsearch_result_index') {
            $queryText = $this->request->getParam(QueryFactory::QUERY_VAR_NAME);

            if ($queryText === null || trim((string)$queryText) === '' || $queryText === '*') {
                return;
            }
        }

        $proceed();
    }
}

