<?php
namespace Canon\CatalogSearch\Plugin\Block;

use Magento\CatalogSearch\Block\Result as ResultBlock;

class ResultPlugin
{
    /**
     * Change heading from "Search results for: '*'" to "All Products" when query is wildcard or empty
     *
     * @param ResultBlock $subject
     * @param \Magento\Framework\Phrase|string $result
     * @return \Magento\Framework\Phrase|string
     */
    public function afterGetSearchQueryText(ResultBlock $subject, $result)
    {
        $request = $subject->getRequest();
        $q = $request->getParam('q');
        if ($q === '*' || $q === null || trim((string)$q) === '') {
            return __('All Products');
        }
        return $result;
    }
}
