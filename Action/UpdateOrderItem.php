<?php
namespace CartBundle\Action;
use ActionKit\Action;
use ActionKit\RecordAction\UpdateRecordAction;

class UpdateOrderItem extends UpdateRecordAction
{
    public $recordClass = 'CartBundle\\Model\\OrderItem';

    public function runValidate() { 
        $cUser = kernel()->currentUser;
        if ( ! $cUser->isLogged() || ! $cUser->hasRole('admin') ) {
            return false;
        }
        return parent::runValidate();
    }
}
