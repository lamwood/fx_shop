<?php
/**
 * BaseController.class.php
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
use Think\Controller;
use Home\Model as HM;
class BaseController extends Controller {
    
    protected $config;
    
    public function _initialize(){
        try{
            $this->config = $this->getInit();
        }catch(\Exception $e){
            $this->config = [
                'plan' => [
                    'ip_waf' => '1',
                    'area_waf' => '1',
                    'web_cache' => '1',
                    'web_zip' => '1',
                    'order_md5' => '1',
                    'order_own' => '0',
                    'plan_own' => '0',
                    'ip_local' => '0',
                    'ip138_on' => '1',
                ],
                'base' => [
                    'connect' => 'off',
                    'cache_time' => '600',
                    'order_tips' => '提交成功！工作人员已收到你的订单信息，谢谢！',
                    'order_limit' => '60',
                    'order_filter' => '测试,哈哈',
                    'order_deny' => '尼玛',
                ],
            ];
            trace($e->getMessage(), '', 'ERR');
        }
    }
    //读取数据库配置文件
    protected function getInit(){
        $config = S('init_set');
        if($config === false){
            $config = [];
            $plan = M('plan_set')->field(['var','value'])->select();
            if($plan){
                foreach($plan as $key => $row){
                    $config['plan'][$row['var']] = $row['value'];
                }
            }
            $base = M('base_set')->field(['var','value'])->select();
            if($base){
                foreach($base as $key => $row){
                    $config['base'][$row['var']] = $row['value'];
                }
            }
            if(isset($config['base']['cache_time'])){
                if(!is_numeric($config['base']['cache_time'])){
                    $config['base']['cache_time'] = C('DATA_CACHE_TIME');
                }
            }else{
                $config['base']['cache_time'] = 60;
            }
            if(isset($config['base']['order_limit'])){
                if(!is_numeric($config['base']['order_limit'])){
                    $config['base']['order_limit'] = 60;
                }
            }else{
                $config['base']['order_limit'] = 60;
            }
            if(isset($config['base']['order_filter'])){
                if(trim($config['base']['order_filter']) == ''){
                    $config['base']['order_filter'] = '测试';
                }
            }else{
                $config['base']['order_filter'] = '测试';
            }
            if(isset($config['base']['order_deny'])){
                if(trim($config['base']['order_deny']) == ''){
                    $config['base']['order_deny'] = '尼玛';
                }
            }else{
                $config['base']['order_deny'] = '尼玛';
            }
            $config['base']['connect'] = 'on';
            S('init_set', $config);
        }
        return $config;
    }
    //获取客户端IP地址
    protected function clientIP(){
        $http_ali_cdn_real_ip = I('server.HTTP_ALI_CDN_REAL_IP', '', 'validate_ip'); //阿里云CDN
        $http_x_forwarded_for = I('server.HTTP_X_FORWARDED_FOR', ''); //代理
        $http_remote_ip = I('server.HTTP_REMOTEIP', ''); //阿里云负载均衡7层
        if($http_ali_cdn_real_ip){
            return $http_ali_cdn_real_ip;
        }
        if($http_remote_ip){
            return $http_remote_ip;
        }
        if($http_x_forwarded_for){
            $ips = explode(',', $http_x_forwarded_for);
            $ip = $ips[0] ?? '';
            if($ip && !preg_match('^(10|172.16|192.168|127.0).', $ip)){
                return $ip;
            }
        }
        return get_client_ip();
    }
    //获取IP地址地理位置信息
    protected function ipLocation($ip){
        $location = [];
        $model = new HM\IplocationModel();
        if(isset($this->config['plan']['ip_local']) && $this->config['plan']['ip_local'] == '0'){
            $location = $model->search($ip);
        }else{
            $location = $model->search($ip, true);
        }
        return $location;
    }
    //
    public function _empty(){
        exit;
    }
}
