<?php
/**
 * OrderController.class.php
 * 风行者广告推广系统
 * Copy right 2020-2030 风行者 保留所有权利。
 * 官方网址: https://fxz.nixi.win/
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用。
 * 任何企业和个人不允许对程序代码以任何形式任何目的再发布。
 * @author John Doe <john.doe@example.com>
 * @date 2020-01-20
 * @version v2.0.22
 */
namespace Home\Controller;
class OrderController extends BaseController {
    public function _initialize() {
        parent::_initialize();
        $this->tbname = 'order';
        $this->path = './';
    }
    //保存订单 Array ( [pid] => 5 [cname] => 索林 [telno] => 13416322257 [address] => 广州繁华路123号 [qq] => 5255555 [desc] => 你好吗! [aid] => 1 )
    public function add(){
        if(IS_POST){
            $ip = $this->clientIP();
            $data = $this->formData();
            $data['ip'] = $ip;
            $http_referer = I('server.HTTP_REFERER', '', 'validate_url,htmlspecialchars');
            $return = $this->isFloor($ip, $data);
            if($return){//下单限制
                $msg = $return === true ? '您已经被拉入黑名单，禁止再次下单!' : '您的订单我们已收到，无需重复提交表单!';
                $this->alert($msg, $http_referer);
                exit;
            }
            $advert = M('advert')->where(['aid'=>$data['aid']])->field(['mid','toid','telno','url','tips','mode','tnid','isuno'])->find();
            if($advert['isuno'] == 1){
                $this->alert('此链接已经禁止下单!', $http_referer);
                exit;
            }
            $data['mid'] = !$advert ? 1 : $advert['mid'];
            $advert['url'] = trim($advert['url']);
            if($advert['url'] != ''){
                if(strpos($advert['url'], 'http://') === false){
                    $advert['url'] = 'http://'.$advert['url'];
                }
            }
            if($advert['mode'] == '1'){
                //$http_referer = str_replace('skip=1', 'order=1x', $http_referer);
                $xarr = explode('?', $http_referer);
                isset($xarr[0]) ? $http_referer = $xarr[0].'?order=xxx' : null;
            }
            $pinfoArr = $this->productInfo($data['pids']);
            $data['pinfo'] = $pinfoArr['pinfo'];
            $data['pids'] = serialize($data['pids']);
            $data['link'] = $http_referer;
            $data['addtime'] = time();
            $model = M($this->tbname);
            $rules = [
                ['aid', 'require', '缺少推广ID'],
                ['cname', 'require', '姓名不能为空'],
                ['telno', 'require', '电话号码不能为空'],
                ['pinfo', 'require', '未选择商品'],
            ];
            if(!$model->validate($rules)->create($data)){
                $this->saveErrorOrder($data);
                $this->error('错误:'.$model->getError());
                exit;
            }
            $info = ['pinfo'=>$data['pinfo'],'addtime'=>$data['addtime'],'cname'=>$data['cname'],'telno'=>$data['telno'],'link'=>$data['link'],'address'=>$data['address']];
            $oid = $model->data($data)->add();
            if($oid){
                $this->saveSucessOrder($data);
                $this->yibu(trim($data['telno']), $data['pids'], $advert['tnid']);
                if($data['pay_method'] == '2'){//电脑端支付宝
                    //
                }elseif($data['pay_method'] == '3'){//手机端支付宝
                    //
                }elseif($data['pay_method'] == '4'){//手机端微信支付
                    $this->wxpayMsg($info, $advert, $pinfoArr, $oid);
                    exit;
                }
                $this->hdfkMsg($info, $advert, $oid);
                exit;
            }
            $this->saveFailOrder($data);
            $this->failMsg($info, $advert);
        }
    }
    //微信支付
    protected function wxpayMsg($data, $advert, $pinfoArr, $oid){
        $parm = [];
        $parm['body'] = $data['pinfo'];
        $parm['product_id'] = 0;
        $parm['out_trade_no'] = 'OID'.$oid;
        $fee = I('post.fee', 0);
        $parm['total_fee'] = is_numeric($fee) ? $fee : $pinfoArr['tt'];
        $parm['cname'] = $data['cname'];
        $parm['telno'] = $data['telno'];
        $parm['http_referer'] = $advert['url'] ? $advert['url'] : $data['link'];
        $parm['msg'] = '';
        $url = 'http://'.C('WX_PAY_DOMAIN').U('Wxpay/wap').'?'.http_build_query($parm);
        $url_h5 = 'http://'.C('WX_PAY_DOMAIN').U('Wxpay/h5b').'?'.http_build_query($parm);
        $this->alert('下单成功，现在跳转到微信支付页面!', $url, $url_h5);
    }
    //提交失败
    protected function failMsg($data, $advert){
        $oid = rand(1000,9999);
        $msg = ['cname'=>$data['cname'],'pinfo'=>$data['pinfo'],'oid'=>'OID'.$oid,'telno'=>$data['telno'],'address'=>$data['address']];
        $msg['tips'] = $advert['tips'] ? $advert['tips'] : $this->config['base']['order_tips'];
        $msg['link'] = $advert['url'] ? $advert['url'] : $data['link'];
        $this->sucMsg($msg, $advert['toid']);
    }
    //货到付款
    protected function hdfkMsg($data, $advert, $oid){
        $msg = ['cname'=>$data['cname'],'pinfo'=>$data['pinfo'],'oid'=>'OID'.$oid,'telno'=>$data['telno'],'address'=>$data['address']];
        $msg['tips'] = $advert['tips'] ? $advert['tips'] : $this->config['base']['order_tips'];
        $msg['link'] = $advert['url'] ? $advert['url'] : $data['link'];
        $msg['telnox'] = $advert['telno'];
        $this->sucMsg($msg, $advert['toid']);
    }
    //备份成功订单
    protected function saveSucessOrder($data){
        file_put_contents($this->path.'/Public/Data/Ok/'.date('Y-m-d').'.txt', implode("\t", $data)."\r\n", FILE_APPEND);
    }
    //保存错误订单
    protected function saveErrorOrder($data){
        file_put_contents($this->path.'/Public/Data/Error/'.date('Y-m-d').'.txt', implode("\t", $data)."\r\n", FILE_APPEND);
    }
    //保存失败订单
    protected function saveFailOrder($data){
        file_put_contents($this->path.'/Public/Data/Fail/'.date('Y-m-d').'.txt', implode("\t", $data)."\r\n", FILE_APPEND);
    }
    //获取订单中商品信息
    protected function productInfo($pids){
        $ptt = empty($pids) ? null : M('product')->where(['pid'=>['in',$pids]])->field(['pname','price'])->select();
        $pinfo = '';
        if($ptt){
            $tarr = [];
            $tt = 0;
            foreach($ptt as $row){
                $tarr[] = $row['pname'];//.':'.(float)$row['price'];
                $tt = $tt + (float)$row['price'];
            }
            $pinfo = implode(' | ', $tarr);
        }
        return ['pinfo'=>$pinfo, 'tt'=>$tt];
    }
    //是否恶意下单
    protected function isFloor($ip, $data){
        $xarr = explode(',', $this->config['base']['order_filter']);
        foreach($xarr as $key => $row){
            if(trim($row) == ''){
                unset($xarr[$key]);
            }
        }
        if($xarr && in_array($data['cname'], $xarr)){//内部测试订单不受限制
            return false;
        }
        $yarr = explode(',', $this->config['base']['order_deny']);
        foreach($yarr as $key => $row){
            if(trim($row) == ''){
                unset($yarr[$key]);
            }
        }
        if($yarr && (in_array($data['cname'], $yarr) || in_array($data['telno'], $yarr))){
            return true;
        }
        $row = M('block_ip')->where(['ip'=>$ip])->find();
        if($row){
            return true;
        }
        $tmp = S('order_'.$ip);
        if($tmp === false){
            S('order_'.$ip, ['count'=>1, 'time'=>time(), 'var'=>md5($data['aid'].$data['cname'].$data['telno'])], 86400);
            return false;
        }
        $var = md5($data['aid'].$data['cname'].$data['telno']);
        if($var == $tmp['var'] && (time() - $tmp['time'] < $this->config['base']['order_limit'])){//客户下单的时间限制
            return 1;
        }
        if(time() - $tmp['time'] <= 60){
            $tmp['count'] = $tmp['count'] + 1;
        }else{
            $tmp['count'] = 1;
            $tmp['time'] = time();
        }
        S('order_'.$ip, $tmp, 86400);
        if($tmp['count'] > 10){//每分钟10个订单
            return true;
        }
        return false;
    }
    //获取表单数据
    protected function formData(){
        $data = [];
        $data['aid'] = I('post.aid/d', 1);
        $data['cname'] = I('post.cname');
        $data['telno'] = I('post.telno');
        $data['qq'] = I('post.qq', '');
        $data['address'] = I('post.address', '');
        $city = I('post.city', '');
        if(trim($city)){
            $data['address'] = $city.' '.$data['address'];
        }
        $data['desc'] = I('post.desc', '');
        $pid = I('post.pid', []);
        if(!is_array($pid)){
            $pid = [$pid];
        }
        $data['pids'] = $pid;
        $data['pay_method'] = I('post.pay_method', '1');
        return $data;
    }
    protected function sucMsg($msg, $toid){
        $tono = M('tpl_order')->where(['toid'=>$toid])->getField('tono');
        $tono = $tono ? $tono : '01';
        $tpl = 'Order/'.$tono.'/index';
        if(!file_exists('./Tpl/Order/'.$tono.'/index.html')){
            $this->error('所选的订单模板不存在');
        }
        $msg['path'] = '/Tpl/Order/'.$tono;
        $this->assign('tpl', $msg);
        $this->display($tpl);
    }
    //提示信息
    protected function alert($msg, $url, $url_h5 = false){
        header("Content-type: text/html; charset=utf-8");
        if($url_h5 == false){
            echo '<script type="text/javascript">alert("'.$msg.'");window.location.href = "'.$url.'";</script>';
        }else{
            echo '<script type="text/javascript">var ua = navigator.userAgent.toLowerCase();var url = ua.match(/MicroMessenger/i) == "micromessenger" ? "'.$url.'" : "'.$url_h5.'";alert("'.$msg.'");setTimeout(function(){window.location.href = url;},2000)</script>';
        }
    }
    //异步调用短信发送接口
    protected function yibu($telno, $pids, $tnid){
        $pidArr = unserialize($pids);
        if(empty($pidArr)){
            return false;
        }
        $options = ['http'=>['method'=>'GET', 'timeout'=>1]];
        $context = stream_context_create($options);
        $tt = file_get_contents('http://localhost'.U('Msg/index',['telno'=>$telno,'pid'=>$pidArr[0],'tnid'=>$tnid]), false, $context);
    }
}
