<?php

namespace App\Admin\Controllers;

use App\Models\Order;
use Encore\Admin\Controllers\AdminController;
use Encore\Admin\Form;
use Encore\Admin\Grid;
use Encore\Admin\Show;
use Encore\Admin\Layout\Content;

class OrdersController extends AdminController
{
    /**
     * Title for current resource.
     *
     * @var string
     */
    protected $title = '订单';

    /**
     * Make a grid builder.
     *
     * @return Grid
     */
    protected function grid()
    {
        $grid = new Grid(new Order());
        $grid->model()->whereNotNull('paid_at')->orderBy('paid_at', 'desc');

        $grid->no('订单号');
        $grid->column('user.name', '买家');
        $grid->total_amount('总金额')->sortable();
        $grid->paid_at('付款时间')->sortable();
        $grid->ship_status('物流')->display(function($value){
            return Order::$shipStatusMap[$value];
        });
        $grid->refund_status('退款')->display(function($value){
            return Order::$refundStatusMap[$value];
        });

        $grid->disableCreateButton();
        $grid->actions(function($actions){
            $actions->disableDelete();
            $actions->disableEdit();
        });

        $grid->tools(function($tools){
            $tools->batch(function($batch){
                $batch->disableDelete();
            });
        });

        

        return $grid;
    }

    /**
     * Make a show builder.
     *
     * @param mixed $id
     * @return Show
     */
    protected function show($id, Content $content)
    {
       
        return $content->header('查看订单')
        ->body(view('admin.orders.show', ['order' => Order::findOrFail($id)]));
    }

    /**
     * Make a form builder.
     *
     * @return Form
     */
    protected function form()
    {
        $form = new Form(new Order());

        $form->text('no', __('No'));
        $form->number('user_id', __('User id'));
        $form->textarea('address', __('Address'));
        $form->decimal('total_amount', __('Total amount'));
        $form->textarea('remark', __('Remark'));
        $form->datetime('paid_at', __('Paid at'))->default(date('Y-m-d H:i:s'));
        $form->text('payment_method', __('Payment method'));
        $form->text('payment_no', __('Payment no'));
        $form->text('refund_status', __('Refund status'))->default('pending');
        $form->text('refund_no', __('Refund no'));
        $form->switch('closed', __('Closed'));
        $form->switch('reviewed', __('Reviewed'));
        $form->text('ship_status', __('Ship status'))->default('pending');
        $form->textarea('ship_data', __('Ship data'));
        $form->textarea('extra', __('Extra'));

        return $form;
    }
}
