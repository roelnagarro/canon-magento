<?php
namespace Canon\DeleteOrder\Plugin\Adminhtml;

use Magento\Sales\Block\Adminhtml\Order\View as OrderViewBlock;

class AddDeleteButtonToOrderView
{
    public function beforeSetLayout(OrderViewBlock $subject)
    {
        $orderId = $subject->getOrderId();
        $message = __('Are you sure you want to delete this order? This action cannot be undone.');
        $deleteUrl = $subject->getUrl('canon_deleteorder/order/delete', ['order_id' => $orderId]);

        $subject->addButton(
            'canon_delete_order_button',
            [
                'label' => __('Delete Order'),
                'class' => 'delete',
                'onclick' => "confirmSetLocation('{$message}', '{$deleteUrl}')"
            ],
            -1
        );
    }
}
