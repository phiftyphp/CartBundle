<?php
namespace CartBundle\Action;
use ActionKit\Action;
use ActionKit\RecordAction\CreateRecordAction;
use MemberBundle\CurrentMember;
use ProductBundle\Model\ProductType;
use CartBundle\Cart;
use CartBundle\Model\OrderItem;
use CartBundle\Model\Order;
use CartBundle\Email\OrderCreatedEmail;

class Checkout extends CreateRecordAction
{
    public $recordClass = 'CartBundle\\Model\\Order';

    public function schema() {
        $this->useRecordSchema();

        // we don't trust amount fields from outside
        $this->filterOut(
            'paid_amount',
            'total_amount',
            'discount_amount',
            'shipping_cost',
            'member_id',
            'payment_status',
            'payment_type'
        );
    }

    public function run()
    {
        $currentMember = new CurrentMember;
        if ( ! $currentMember->isLogged() ) {
            return $this->error( _('請先登入會員') );
        }

        if ( $t = $this->arg('invoice_type') ) {
            if ( intval($t) == 3 ) {
                $this->requireArgs('utc','utc_title','utc_name','utc_address');
                if ( $this->result->hasInvalidMessages() ) {
                    return $this->error(_('您選擇了三聯式發票，請確認欄位填寫喔。'));
                }
                if ( strlen(trim($this->arg('utc'))) != 8 ) {
                    $this->invalidField('utc', _('統一編號必須是八碼，再麻煩您檢查一下') );
                    return $this->error(_('發票欄位填寫錯誤'));
                }
            }
        }

        $cart = Cart::getInstance();
        $orderItems = $cart->getOrderItems();

        $shippingCost    = $cart->calculateShippingCost();
        $origTotalAmount = $cart->calculateTotalAmount();
        $totalAmount     = $cart->calculateDiscountedTotalAmount();
        $discountAmount  = $cart->calculateDiscountAmount();

        // Use Try-Cache to cache exceptions and process fallbacks.
        $this->setArgument('paid_amount', 0);
        $this->setArgument('shipping_cost', $shippingCost);
        $this->setArgument('total_amount', $totalAmount);
        $this->setArgument('discount_amount', $discountAmount);

        if ( $coupon = $cart->loadSessionCoupon() ) {
            $this->setArgument('coupon_code', $coupon->coupon_code);
        }

        kernel()->db->beginTransaction();

        try {
            if ( ! parent::run() ) {
                throw new Exception( _('無法建立訂單') );
            }
            if ( ! $this->record->id ) {
                throw new Exception( _('無法建立訂單項目') );
            }

            // kernel()->db->query("LOCK TABLES " . OrderItem::table . " AS oi READ");

            foreach( $orderItems as $orderItem ) {
                $orderItem->setAlias('oi');
                $ret = $orderItem->update([
                    'order_id' => $this->record->id,
                    'shipping_status' => 'unpaid',
                ]);

                if ( $ret->success ) {
                    kernel()->db->query("LOCK TABLES " . ProductType::table . " AS t WRITE");
                    $stmt = kernel()->db->prepare("UPDATE " . ProductType::table . " t SET quantity = quantity - ? WHERE id = ?");
                    $stmt->execute([ $orderItem->quantity, $orderItem->type_id ]);
                    kernel()->db->query("UNLOCK TABLES");
                } else {
                    if ( $ret->exception ) {
                        throw $ret->exception;
                    }
                    throw new Exception($ret->message);
                }
            }

            $cart->cleanUp();
            kernel()->db->commit();

            $this->success(_('訂單建立成功，導向中.. 請稍待'));

            $email = new OrderCreatedEmail($currentMember->getRecord(), $this->getRecord());
            $email->send();

            return $this->redirectLater('/order/view?' . http_build_query([
                'o' => $this->record->id,
                't' => $this->record->token,
            ]), 2);
        } catch ( Exception $e ) {
            kernel()->db->rollback();
            return $this->error( $e->getMessage() );
        }
        return $this->error('訂單建立失敗');
    }

}
