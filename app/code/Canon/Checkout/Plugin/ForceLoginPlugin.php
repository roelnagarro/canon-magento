<?php
declare(strict_types=1);

namespace Canon\Checkout\Plugin;

use Magento\Customer\Model\Context as CustomerContext;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Area;
use Magento\Framework\App\FrontControllerInterface;
use Magento\Framework\App\Http\Context as HttpContext;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\State as AppState;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\UrlInterface;

class ForceLoginPlugin
{
    /**
     * Exact path prefixes allowed for non-authenticated guests
     */
    private const ALLOWED_PATH_PREFIXES = [
        'customer/account/login',
        'customer/account/loginpost',
        'customer/account/create',
        'customer/account/createpost',
        'customer/account/forgotpassword',
        'customer/account/forgotpasswordpost',
        'customer/account/resetpassword',
        'customer/account/resetpasswordpost',
        'customer/account/confirmation',
        'customer/account/logout',
        'customer/account/logoutsuccess',
        'customer/section/load',
        'loginascustomer',
        'captcha',
        'rest',
        'graphql',
        'pub',
        'static',
        'media',
        'robots.txt',
        'canon-admin'
    ];

    private CustomerSession $customerSession;
    private HttpContext $httpContext;
    private RedirectFactory $resultRedirectFactory;
    private UrlInterface $urlBuilder;
    private HttpResponse $response;
    private AppState $appState;

    public function __construct(
        CustomerSession $customerSession,
        HttpContext $httpContext,
        RedirectFactory $resultRedirectFactory,
        UrlInterface $urlBuilder,
        HttpResponse $response,
        AppState $appState
    ) {
        $this->customerSession = $customerSession;
        $this->httpContext = $httpContext;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->urlBuilder = $urlBuilder;
        $this->response = $response;
        $this->appState = $appState;
    }

    /**
     * Intercept front controller dispatch to require customer login across the storefront
     *
     * @param FrontControllerInterface $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @return mixed
     */
    public function aroundDispatch(
        FrontControllerInterface $subject,
        callable $proceed,
        RequestInterface $request
    ) {
        if (!($request instanceof HttpRequest)) {
            return $proceed($request);
        }

        // Only enforce in storefront area
        try {
            if ($this->appState->getAreaCode() !== Area::AREA_FRONTEND) {
                return $proceed($request);
            }
        } catch (\Exception $e) {
            // Area code not set yet, continue check
        }

        // Check if customer is authenticated or admin is logged in as customer
        $isLoggedIn = (bool)$this->httpContext->getValue(CustomerContext::CONTEXT_AUTH)
            || $this->customerSession->isLoggedIn()
            || (bool)$this->customerSession->getCustomerId()
            || (bool)$this->customerSession->getLoggedAsCustomerAdminId();

        if ($isLoggedIn) {
            return $proceed($request);
        }

        // Check if current path is allowed for guests
        $pathInfo = strtolower(trim($request->getPathInfo(), '/'));

        foreach (self::ALLOWED_PATH_PREFIXES as $allowed) {
            if ($pathInfo === $allowed || str_starts_with($pathInfo, $allowed . '/')) {
                return $proceed($request);
            }
        }

        // Allow direct customer/section ajax requests and loginascustomer module
        $module = strtolower((string)$request->getModuleName());
        $controller = strtolower((string)$request->getControllerName());
        if ($module === 'loginascustomer') {
            return $proceed($request);
        }
        if ($module === 'customer' && in_array($controller, ['section'], true)) {
            return $proceed($request);
        }
        if ($module === 'captcha') {
            return $proceed($request);
        }

        // Guest user attempting to access protected storefront page -> redirect to login
        $loginUrl = $this->urlBuilder->getUrl('customer/account/login');

        if ($request->isAjax() || $request->isXmlHttpRequest()) {
            $this->response->setStatusCode(401);
            $this->response->setHeader('Content-Type', 'application/json', true);
            $this->response->setBody((string)json_encode([
                'error' => true,
                'message' => 'Please log in to continue.',
                'redirect_url' => $loginUrl
            ]));
            return $this->response;
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($loginUrl);
        return $resultRedirect;
    }
}
