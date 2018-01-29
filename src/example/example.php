<?php
use Search;
use think\Controller;
/**
 * Created by PhpStorm.
 * User: xietaotao
 * Date: 2018/1/29
 * Time: 17:20
 */
class example extends Controller
{

    public function index(){

        //实例化
        $search = new Search(url(),input('param.', ''));
        //添加一个单一的文本框
        $search->addTextItem('id','version_id','like');
        //添加一个单一的下拉
        $options = ['a'=>1,'a'=>2,'c'=>3];
        $search->addType('status','version_status','eq',$options);
        //添加一个单一的时间选项
        $search->addTime('create_time','create_time','gt');
        //添加一个时间区间选项
        $search->addTimeBetween('create_time');
        //添加一个关联的下拉选项
        $search->addAssoc('search_type','keyword',[
            'info'=>[
                'alias'=>'version_info',
                'name'=>'版本描述',
                'op'=>'eq'
            ],
            'version_no'=>[
                'alias'=>'version_no',
                'name'=>'版本号',
                'op'=>'eq'
            ],
            'content'=>[
                'alias'=>'version_content',
                'name'=>'内容',
                'op'=>'eq'
            ],
        ]);
        //获取where语句
        $where = $search->getWhere();
        //获取search数据，用于模板渲染
        $searchVars = $search->getSearchData();
        $this->assign('search', $searchVars);
        return $this->fetch('__searchBox__');

    }

}