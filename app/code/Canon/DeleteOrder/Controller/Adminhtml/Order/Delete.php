<?php
namespace Canon\DeleteOrder\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\Controller\ResultFactory;

class Delete extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::actions_edit';

    private OrderRepositoryInterface $orderRepository;
    private Registry $registry;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->registry = $registry;
    }

    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Order ID is required.'));
            return $resultRedirect->setPath('sales/order/index');
        }

        try {
            $order = $this->orderRepository->get($orderId);
            $incrementId = $order->getIncrementId();
            
            // Enable secure area to allow order deletion
            $this->registry->unregister('isSecureArea');
            $this->registry->register('isSecureArea', true);

            // Delete order via repository
            $this->orderRepository->delete($order);

            $this->messageManager->addSuccessMessage(__('Order #%1 has been deleted successfully.', $incrementId));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error deleting order: %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('sales/order/index');
    }
}

