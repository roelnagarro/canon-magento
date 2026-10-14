<?php
declare(strict_types=1);

namespace Canon\Checkout\Plugin;

use Magento\Customer\Model\Account\Redirect as AccountRedirect;
use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Framework\UrlInterface;

class CustomerRedirectHomepagePlugin
{
    private CustomerSession $customerSession;
    private HttpContext $httpContext;
    private UrlInterface $urlBuilder;

    public function __construct(
        CustomerSession $customerSession,
        HttpContext $httpContext,
        UrlInterface $urlBuilder
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * Intercept AccountRedirect getRedirect
     *
     * @param AccountRedirect $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterGetRedirect(AccountRedirect $subject, $result)
    {
        return $this->redirectIfLoggedIn($result);
    }

    /**
     * Intercept customer login/register controller execution
     *
     * @param mixed $subject
     * @param mixed $result
     * @return mixed
     */
    public function afterExecute($subject, $result)
    {
        return $this->redirectIfLoggedIn($result);
    }

    /**
     * If user is authenticated and response is a redirect, redirect to homepage
     *
     * @param mixed $result
     * @return mixed
     */
    private function redirectIfLoggedIn($result)
    {
        if (!($result instanceof Redirect)) {
            return $result;
        }

        $isLoggedIn = (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)
            || $this->customerSession->isLoggedIn()
            || (bool)$this->customerSession->getCustomerId()
            || (bool)$this->customerSession->getLoggedAsCustomerAdminId();

        if ($isLoggedIn) {
            $result->setUrl($this->urlBuilder->getUrl(''));
        }

        return $result;
    }
}
