<?php

namespace CartBundle\Model;

use LazyRecord\Schema\SchemaDeclare;

class TransactionSchema extends SchemaDeclare
{
    public function schema()
    {
        $this->table('ftxs');

        $this->column('order_id')
            ->integer()
            ->required()
            ->label(_('訂單編號'))
            ->refer('CartBundle\\Model\\OrderSchema')
            ;

        $this->column('type')
            ->varchar(32)
            ->label(_('交易類型'))
            ->required()
            ->validValues([
                '信用卡' => 'cc',
                'ATM' => 'atm',
                '貨到付款' => 'pod', // pay on delivery,
            ])
            ;

        $this->column('amount')
            ->integer()
            ->label(_('交易金額'))
            ->required()
            ;

        $this->column('result')
            ->boolean()
            ->default(false)
            ->label(_('交易結果'))
            ->required()
            ;

        $this->column('message')
            ->varchar(128)
            ->label(_('訊息'))
            ;

        $this->column('reason')
            ->varchar(128)
            ->label(_('原因'))
            ;

        $this->column('code')
            ->varchar(8)
            ->label(_('銀行回傳碼'))
            ;

        $this->column('data')
            ->text()
            ->label(_('描述交易資料'))
            ;

        $this->column('raw_data')
            ->text()
            ->label(_('原始 API 交易資料'))
            ;

        /* the paid_date */
        $this->column('paid_date')
            ->datetime()
            ->null()
            ->renderAs('DateTimeInput')
            ->label(_('付款日期'))
            ->default(function () {
                return new \DateTime;
            })
            ;

        /* the record date */
        $this->column('created_on')
            ->timestamp()
            ->null()
            ->renderAs('DateTimeInput')
            ->label(_('建立時間'))
            ->default(function () {
                return new \DateTime;
            })
            ;

        $this->belongsTo('order', 'CartBundle\\Model\\OrderSchema', 'id', 'order_id');
    }
}
