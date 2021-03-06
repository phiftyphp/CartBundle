<?php

namespace CartBundle;

use AdminUI\CRUDHandler;
use CartBundle\Controller\OrderFilterToolbarItemController;

class OrderCRUDHandler extends CRUDHandler
{
    /* CRUD Attributes */
    public $modelClass = 'CartBundle\Model\Order';

    public $crudId = 'order';

    public $listColumns = array(
        'id', 'sn', 'buyer_name', 'buyer_cellphone',
        'payment_type',
        'payment_status',
        'coupon_code',
        'total_amount',
        'discount_amount',
        'paid_amount',
        'created_on',
    );

    public $quicksearchFields = array('buyer_name', 'buyer_phone', 'buyer_cellphone', 'buyer_address', 'sn');

    public $filterColumns = array('payment_type', 'payment_status');

    public $canCreate = true;
    public $canUpdate = true;
    public $canDelete = true;

    public $canBulkEdit = true;
    public $canBulkDelete = true;
    public $canBulkCopy = false;
    public $canEditInNewWindow = false;

    // public $debug = true;

    // public $templatePage = '@CRUD/page.html';
    // public $actionViewClass = 'AdminUI\\Action\\View\\StackView';
    // public $pageLimit = 15;
    // public $defaultOrder = array('id', 'DESC');

    /*
    public function initToolbarControls()
    {
        parent::initToolbarControls();
        $this->addToolbarItem(new OrderFilterToolbarItemController());
    }
    */

    public function getCollection()
    {
        return parent::getCollection();
    }
}
