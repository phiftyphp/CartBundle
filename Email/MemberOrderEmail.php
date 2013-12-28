<?php
namespace CartBundle\Email;
use Phifty\Message\Email;
use MemberBundle\Email\MemberEmail;

/**
 * Order Shipping Notification Email
 */
class MemberOrderEmail extends MemberEmail
{
    public $order;

    public function __construct($member, $order)
    {
        parent::__construct($member);
        $this->order = $this['order'] = $order;
    }
}





