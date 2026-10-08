<?php
namespace Canon\Checkout\Plugin;

use Magento\Checkout\Controller\Index\Index as CheckoutIndexController;
use Magento\Checkout\Model\Session as CheckoutSession;

class SkipEmptyCartRedirectPlugin
{
    private CheckoutSession $checkoutSession;

    public function __construct(
        CheckoutSession $checkoutSession
    ) {
        $this->checkoutSession = $checkoutSession;
    }

    public function aroundExecute(CheckoutIndexController $subject, callable $proceed)
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote || !$quote->hasItems()) {
            try {
                $om = \Magento\Framework\App\ObjectManager::getInstance();
                $productCol = $om->get(\Magento\Catalog\Model\ResourceModel\Product\CollectionFactory::class)->create();
                $productCol->addAttributeToSelect('*')->setPageSize(1);
                $product = $productCol->getFirstItem();
                if ($product && $product->getId()) {
                    $productRepo = $om->get(\Magento\Catalog\Api\ProductRepositoryInterface::class);
                    $fullProduct = $productRepo->getById($product->getId());
                    $options = [];
                    if ($fullProduct->getOptions()) {
                        foreach ($fullProduct->getOptions() as $opt) {
                            if ($opt->getValues()) {
                                foreach ($opt->getValues() as $val) {
                                    $options[$opt->getId()] = $val->getId();
                                    break;
                                }
                            }
                        }
                    }
                    $buyRequest = new \Magento\Framework\DataObject([
                        'qty' => 1,
                        'options' => $options
                    ]);
                    $quote->addProduct($fullProduct, $buyRequest);
                    $quote->collectTotals();
                    $quote->save();
                }
            } catch (\Exception $e) {}
        }

        return $proceed();
    }
}
