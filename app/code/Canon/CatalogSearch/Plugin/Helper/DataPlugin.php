<?php
namespace Canon\CatalogSearch\Plugin\Helper;

use Magento\CatalogSearch\Helper\Data as SearchHelper;
use Magento\Framework\App\RequestInterface;
use Magento\Search\Model\QueryFactory;

class DataPlugin
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
     * Bypass minimum query length error note when search query is wildcard or empty
     *
     * @param SearchHelper $subject
     * @param bool $result
     * @return bool
     */
    public function afterIsMinQueryLength(SearchHelper $subject, $result)
    {
        $queryText = $this->request->getParam(QueryFactory::QUERY_VAR_NAME);
        if ($queryText === '*' || $queryText === null || trim((string)$queryText) === '') {
            return false;
        }
        return $result;
    }
}
