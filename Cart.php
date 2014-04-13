<?php
namespace CartBundle;
use ProductBundle\Model\Product;
use ProductBundle\Model\ProductType;
use CartBundle\Model\OrderItem;
use CartBundle\Model\Order;
use CartBundle\Exception\CartException;
use CouponBundle\Model\Coupon;
use ShippingBundle\Model\Company as ShippingCompany;
use Exception;
use RuntimeException;
use ArrayIterator;
use IteratorAggregate;

/**
 * Contains the logics of Cart
 */
class Cart extends CartBase
{
    public $shippingCompany = 'default';


    public function __construct() {
        // TODO: provide options to specify storage engine.
        $this->storage = new SessionCartStorage;
        $this->validateItems();
    }

    public function removeItem($id)
    {
        if ( $this->deleteOrderItem($id) ) {
            $this->storage->remove($id);
            return true;
        }
        return false;
    }

    public function addItem($productId, $typeId, $quantity = 1)
    {
        $product = new Product( intval($productId) );
        if ( ! $product->id ) {
            throw new CartException(_("找不到商品"));
        }

        $foundType = null;
        foreach( $product->types as $type ) {
            if ( intval($type->id) === intval($typeId) ) {
                $foundType = $type;
                break;
            }
        }
        if ( ! $foundType ) {
            throw new CartException(_("此產品無此類型"));
        }

        if ( $foundType->quantity < $quantity ) {
            // XXX: warning...
        }

        // Create the order item with session here....
        $quantity = intval($quantity);


        // find the same product and type, 
        // if it's the same, we should simply update the quantity instead of creating new items
        if ( $items = $this->getOrderItems() ) {
            foreach( $items as $item ) {
                if ( $item->product_id == $product->id && $item->type_id == $foundType->id ) {
                    $item->update(array(
                        'quantity' => intval($item->quantity) + $quantity,
                    ));
                    return true;
                }
            }
        }

        $item = $this->createOrderItem($product, $foundType, $quantity);
        $this->storage->add( $item->id );
        return true;
    }

    public function calculateTotalQuantity() {
        if ( $collection = $this->getOrderItems() ) {
            return $collection->calculateTotalQuantity();
        }
        return 0;
    }

    /**
     * Return the sum of amount from all order items, this method does not count shipping cost in.
     *
     * @return int The total amount
     */
    public function calculateOrderItemTotalAmount() {
        if ( $collection = $this->getOrderItems() ) {
            return $collection->calculateTotalAmount();
        }
        return 0;
    }

    public function calculateTotalAmount() {
        $totalAmount = 0;
        $totalAmount += $this->calculateOrderItemTotalAmount();
        $totalAmount += $this->calculateShippingCost();
        return $totalAmount;
    }

    public function calculateDiscountAmount() {
        $discountedAmount = $this->calculateDiscountedTotalAmount();
        $totalAmount = $this->calculateTotalAmount();
        return $totalAmount - $discountedAmount;
    }

    public function calculateDiscountedTotalAmount() {
        $totalAmount = $this->calculateTotalAmount();
        if ( $coupon = $this->loadSessionCoupon() ) {
            return $coupon->calcualteDiscount($totalAmount);
        }
        return $totalAmount;
    }


    /**
     * Coupon related logics
     */
    public function applyCoupon($coupon) {
        // always validate coupon
        list($success, $reason) = $coupon->isValid($this);
        if ($success) {
            $_SESSION['coupon_code'] = $coupon->coupon_code;
            return true;
        }
        return false;
    }

    public function usingCoupon() {
        // the session is registered only when the coupon is validated..
        return isset($_SESSION['coupon_code']);
    }


    /**
     * check current coupon and re-validate the coupon.
     */
    public function loadSessionCoupon()
    {
        if ( isset($_SESSION['coupon_code']) ) {
            $coupon = new Coupon([ 'coupon_code' => $_SESSION['coupon_code'] ]);
            // always validate coupon
            list($success, $reason) = $coupon->isValid($this);
            if ($success) {
                return $coupon;
            }
            // if it's invalid coupon, just delete the sesssion
            unset($_SESSION['coupon_code']);
        }
    }

    public function cleanUp() {
        unset($_SESSION['coupon_code']);
        unset($_SESSION['items']);
        $this->storage->removeAll();
    }

    public function calculateShippingCost() {
        $bundle = kernel()->bundle('CartBundle');

        if ( $aboveAmount = $bundle->config('NoShippingFeeCondition.AboveAmount') ) {
            $orderItemAmount = $this->calculateOrderItemTotalAmount();
            if ( $orderItemAmount >= $aboveAmount ) {
                return 0;
            }
        }

        // Load default shipping method
        $company = new ShippingCompany([ 'handle' => $this->shippingCompany ]);
        if ( $company->id && $this->getOrderItems() ) {
            return $company->shipping_cost;
        }
        return 0;
    }


    /**
     * Return Cart Summary
     */
    public function getSummary() {
        return array(
            'orderitem_total_amount' => $this->calculateOrderItemTotalAmount(),
            'shipping_cost'     => $this->calculateShippingCost(),

            // the original total amount (including shipping cost)
            'total_amount'      => $this->calculateTotalAmount(),

            // discounted total amount
            'discounted_total_amount' => $this->calculateDiscountedTotalAmount(),

            // discount amount (from coupon)
            'discount_amount'   => $this->calculateDiscountAmount(),
        );
    }
}




