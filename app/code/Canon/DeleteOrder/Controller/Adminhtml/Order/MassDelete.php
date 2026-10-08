<?php
namespace Canon\DeleteOrder\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Registry;
use Magento\Framework\Controller\ResultFactory;

class MassDelete extends Action
{
    const ADMIN_RESOURCE = 'Magento_Sales::actions_edit';

    private Filter $filter;
    private CollectionFactory $collectionFactory;
    private OrderRepositoryInterface $orderRepository;
    private Registry $registry;

    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        OrderRepositoryInterface $orderRepository,
        Registry $registry
    ) {
        parent::__construct($context);
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
        $this->orderRepository = $orderRepository;
        $this->registry = $registry;
    }

    public function execute()
    {
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $deletedCount = 0;

            // Enable secure area to allow order deletion
            $this->registry->unregister('isSecureArea');
            $this->registry->register('isSecureArea', true);

            foreach ($collection as $order) {
                $this->orderRepository->delete($order);
                $deletedCount++;
            }

            if ($deletedCount > 0) {
                $this->messageManager->addSuccessMessage(__('A total of %1 order(s) have been deleted.', $deletedCount));
            } else {
                $this->messageManager->addNoticeMessage(__('No orders were selected for deletion.'));
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Error deleting order(s): %1', $e->getMessage()));
        }

        return $resultRedirect->setPath('sales/order/index');
    }
}

