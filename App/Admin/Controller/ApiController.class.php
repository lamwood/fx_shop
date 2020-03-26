<?php
/**
 * ApiController.class.php
 * 风行者广告推广系统
 * Copy right 2020-2030 风行者 保留所有权利。
 * 官方网址: https://fxz.nixi.win/
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * @author John Doe <john.doe@example.com>
 * @date 2020-01-20
 * @version v2.0.22
 */
namespace Admin\Controller;
use Think\Controller;
class ApiController extends Controller{
    //
    public function index(){
        $id = I('get.id/d', 0);
        if(!$id){
            $this->ajaxReturn([]);
        }elseif($id == 400){
            $id = intval(file_get_contents('/tmp/api.oid'));
        }
        $od = M('order')->where(['oid'=>['gt',$id]])->order('oid')->limit(1)->find();
        if(!$od){
            $this->ajaxReturn([]);
        }
        $ad = M('advert')->where(['aid'=>$od['aid']])->field(['xname'])->find();
        $data = [];
        $data['order_id'] = $od['oid'];
        $data['name'] = $od['cname'];
        $data['mobile'] = $od['telno'];
        $data['address'] = $od['address'];
        $data['qq'] = $od['qq'];
        $data['contents'] = $od['desc'];
        $data['payment_id'] = $od['pay_method'];// == '1' ? 0 : 1;
        $data['order_create_time'] = $od['addtime'];
        $data['od_info_from_url'] = $od['link'];
        $data['od_info_emp_name'] = M('member')->where(['mid'=>$od['mid']])->getField('nickname');
        $data['od_info_acnt_name'] = $ad['xname'];
        $data['od_info_goods_name'] = $od['pinfo'];
        $data['od_info_customer_ip'] = $od['ip'];
        foreach($data as $key => $row){
            $data[$key] = addslashes(str_replace("'", '‘',$row));
        }
        file_put_contents('/tmp/api.oid', $od['oid']);
        $this->ajaxReturn($data);
    }
    //
    public function _empty(){
        exit;
    }
}
