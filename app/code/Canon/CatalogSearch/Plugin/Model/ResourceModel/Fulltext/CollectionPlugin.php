<?php
namespace Canon\CatalogSearch\Plugin\Model\ResourceModel\Fulltext;

use Magento\CatalogSearch\Model\ResourceModel\Fulltext\Collection as FulltextCollection;
use Magento\Framework\App\RequestInterface;
use Magento\Search\Model\QueryFactory;

class CollectionPlugin
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
     * Clean up filters before rendering when search query is empty or wildcard
     *
     * @param FulltextCollection $subject
     * @param callable $proceed
     * @return void
     */
    public function around_renderFiltersBefore(FulltextCollection $subject, callable $proceed)
    {
        $proceed();
    }
}
