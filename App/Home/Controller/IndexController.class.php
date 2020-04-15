<?php
/**
 * IndexController.class.php
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
use Home\Controller\EmptyController;
class IndexController extends BaseController {
    //
    public function _initialize() {
        parent::_initialize();
        $this->tbname = 'domain';
    }
    //域名首页
    public function index(){
        $domain = I('server.HTTP_HOST', '', 'string,trim');
        $dArr = $this->domainInfo($domain);
        if(trim($dArr['tname']) != ''){
            $_SERVER['PATH_INFO'] = $dArr['tname'];
            $empty = new EmptyController();
            $empty->index();
            exit;
        }
        if($dArr){
            $tArr = $this->tplInfo($dArr['tdid']);
            $dArr['jscode'] = htmlspecialchars_decode($dArr['jscode']);
        }else{
            $tArr['tdno'] = 'deny';
        }
        $dArr['path'] = '/Tpl/Domain/'.$tArr['tdno'];
        $this->assign('tpl', $dArr);
        if(file_exists('./Tpl/Domain/'.$tArr['tdno'].'/default.html')){
            $this->display('Domain/'.$tArr['tdno'].'/default');
        }else{
            $this->display('Domain/'.$tArr['tdno'].'/index');
        }
    }
    //
    protected function tplInfo($tdid){
        $tArr = S('domain_tpl_'.$tdid);
        if($tArr === false){
            $tArr = M('tpl_domain')->where(['tdid'=>$tdid])->find();
            if(!$tArr){
                $tArr = [];
            }
            if($this->config['plan']['web_cache'] == '1'){
                S('domain_tpl_'.$tdid, $tArr, ['expire'=>$this->config['base']['cache_time']]);
            }
        }
        return $tArr;
    }
    //
    protected function domainInfo($domain){
        if(substr($domain, 0, 4) === 'www.'){
            $domain = substr($domain, 4);
        }
        $dArr = S('domain_'.$domain);
        if($dArr === false){
            $model = M($this->tbname);
            $dArr = $model->where(['domain'=>$domain])->find();
            if(!$dArr){
                $dArr = $model->where(['domain'=>'www.'.$domain])->find();
                if(!$dArr){
                    $dArr = [];
                }
            }
            if($this->config['plan']['web_cache'] == '1'){
                S('domain_'.$domain, $dArr, ['expire'=>$this->config['base']['cache_time']]);
            }
        }
        return $dArr;
    }
    //响应检测域名
    public function who(){
        exit('fxz');
    }
    //
    public function test(){
        //M()->query("select sleep(10)");
    }
}
