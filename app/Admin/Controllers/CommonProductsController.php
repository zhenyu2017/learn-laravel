<?php
 
namespace App\Admin\Controllers;

use Encore\Admin\Controllers\AdminController;
use App\Models\Category;
use App\Models\Product;
use Encore\Admin\Grid;
use Encore\Admin\Form;

abstract class CommonProductsController extends AdminController
{

    abstract public function getProductType();

    protected function grid()
    {
        $grid = new Grid(new Product());

        $grid->model()->where('type', $this->getProductType())->orderBy('id', 'desc');
        //调用自定义的方法
        $this->customGrid($grid);

        $grid->actions(function($actions){
            $actions->disableView();
            $actions->disableDelete();
        });

        $grid->tools(function($tools){
            $tools->batch(function($batch){
                $batch->disableDelete();
            });
        });

        return $grid;
    }

    abstract protected function customGrid(Grid $grid);

    protected function form()
    {
        $form = new Form(new Product());
        $form->hidden('type')->value($this->getProductType());
        $form->text('title', '商品名称')->rules('required');
        $form->select('category_id', '类目')->options(function($id){
            $category = Category::find($id);
            return [$category->id => $category->full_name];
        })->ajax('/admin/api/categories?is_directory=0');
        $form->image('image', '封面图片')->rules('required|image');
        $form->quill('description', '商品描述')->rules('required');
        $form->radio('on_sale', '上架')->options(['1' => '是', '0' => '否'])->default('0');
        //调用自定义方法
        $this->customForm($form);

        $form->hasMany('skus', '商品 SKU', function(Form\NestedForm $form){
            $form->text('title', 'SKU 名称')->rules('required');
            $form->text('description', 'SKU 描述')->rules('required');
            $form->text('price', '单价')->rules('required|numeric|min:0.01');
            $form->text('stock', '库存')->rules(('required|numeric|min:0'));

        });

        $form->saving(function(Form $form){
            $form->modle()->price = collect($form->input('skus')->where(Form::REMOVE_FLAG_NAME, 0)->min('price')) ?: 0;
        });

        return $form;
    }

    abstract protected function customForm(Form $form);
}