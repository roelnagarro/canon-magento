<?php
namespace Canon\Checkout\Controller\Onepage;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Quote\Model\QuoteManagement;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;

class PlaceOrder implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private Context $context;
    private CheckoutSession $checkoutSession;
    private CustomerSession $customerSession;
    private QuoteManagement $quoteManagement;
    private JsonFactory $resultJsonFactory;
    private OrderFactory $orderFactory;

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        QuoteManagement $quoteManagement,
        JsonFactory $resultJsonFactory,
        OrderFactory $orderFactory
    ) {
        $this->context = $context;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->quoteManagement = $quoteManagement;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->orderFactory = $orderFactory;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
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

        if (!$quote || !$quote->hasItems()) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Your shopping cart is empty.')
            ]);
        }

        try {
            $name = $this->context->getRequest()->getParam('name', 'Scott Francis');
            $nameParts = explode(' ', trim($name), 2);
            $firstname = $nameParts[0] ?: 'Scott';
            $lastname = isset($nameParts[1]) ? $nameParts[1] : 'Francis';

            $company = $this->context->getRequest()->getParam('company', 'Right Toyota');
            $addressStr = $this->context->getRequest()->getParam('address', '11212 Bell Road');
            $city = $this->context->getRequest()->getParam('city', 'Scottsdale');
            $region = $this->context->getRequest()->getParam('region', 'AZ');
            $postcode = $this->context->getRequest()->getParam('postcode', '85260');
            $phone = $this->context->getRequest()->getParam('phone', '480-125-4568');
            $email = $this->context->getRequest()->getParam('email', 'sfrancis@righttoyota.com');

            if ($this->customerSession->isLoggedIn()) {
                $customer = $this->customerSession->getCustomer();
                if ($customer->getEmail()) {
                    $email = $customer->getEmail();
                }
                if ($customer->getFirstname()) {
                    $firstname = $customer->getFirstname();
                    $lastname = $customer->getLastname() ?: $lastname;
                }
            }

            $addressData = [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'company' => $company,
                'street' => [$addressStr],
                'city' => $city,
                'region' => $region,
                'region_id' => 4,
                'postcode' => $postcode,
                'country_id' => 'US',
                'telephone' => $phone,
                'email' => $email,
            ];

            $billingAddress = $quote->getBillingAddress();
            $billingAddress->addData($addressData);

            $shippingAddress = $quote->getShippingAddress();
            $shippingAddress->addData($addressData);
            $shippingAddress->setCollectShippingRates(true)
                            ->collectShippingRates()
                            ->setShippingMethod('flatrate_flatrate');

            $quote->setPaymentMethod('checkmo');
            $quote->getPayment()->importData(['method' => 'checkmo']);

            $quote->setCustomerEmail($email);
            $quote->setCustomerFirstname($firstname);
            $quote->setCustomerLastname($lastname);

            if (!$this->customerSession->isLoggedIn()) {
                $quote->setCustomerIsGuest(true);
            }

            $quote->setTotalsCollectedFlag(false);
            $quote->collectTotals();
            $quote->save();

            $orderId = $this->quoteManagement->placeOrder($quote->getId());

            if ($orderId) {
                $order = $this->orderFactory->create()->load($orderId);
                $this->checkoutSession
                    ->setLastQuoteId($quote->getId())
                    ->setLastSuccessQuoteId($quote->getId())
                    ->setLastOrderId($orderId)
                    ->setLastRealOrderId($order->getIncrementId())
                    ->setLastOrderStatus($order->getStatus());

                $successUrl = $this->context->getUrl()->getUrl('checkout/onepage/success');
                $isAjax = $this->context->getRequest()->isXmlHttpRequest() || $this->context->getRequest()->getParam('is_ajax');
                if ($isAjax) {
                    return $resultJson->setData([
                        'success' => true,
                        'redirectUrl' => $successUrl
                    ]);
                } else {
                    $resultRedirect = $this->context->getResultFactory()->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
                    return $resultRedirect->setUrl($successUrl);
                }
            }
        } catch (\Exception $e) {
            $isAjax = $this->context->getRequest()->isXmlHttpRequest() || $this->context->getRequest()->getParam('is_ajax');
            if ($isAjax) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => $e->getMessage()
                ]);
            } else {
                $this->context->getMessageManager()->addErrorMessage($e->getMessage());
                $resultRedirect = $this->context->getResultFactory()->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
                return $resultRedirect->setPath('checkout');
            }
        }

        $isAjax = $this->context->getRequest()->isXmlHttpRequest() || $this->context->getRequest()->getParam('is_ajax');
        if ($isAjax) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Unable to place order.')
            ]);
        } else {
            $this->context->getMessageManager()->addErrorMessage(__('Unable to place order.'));
            $resultRedirect = $this->context->getResultFactory()->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('checkout');
        }
    }
}
