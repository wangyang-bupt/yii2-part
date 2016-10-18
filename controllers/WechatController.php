<?php
namespace app\controllers;
require_once('../models/wechat.class.php');


use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\OfficialAccount;
use app\models\DanmuWechatData;

class WechatController extends Controller
{
     public $hasAppid;
     public $appid;
     public $oaObj;
     public $dataObj;
     public $wechatObj;
     public $enableCsrfValidation = false;

     public function actionRespond(){
     	$result=yii::$app->request->get('appid');
     	$this->hasAppid=isset($result);
     	$this->appid=$this->hasAppid?yii::$app->request->get('appid'):'wx12bbdac6752209e3';
     	$this->oaObj=new OfficialAccount($this->appid);

     	$oaAttr=$this->oaObj->getMsgAttr();
		if($oaAttr){
			$options=array('token'=>$oaAttr['token'],'encodingaeskey'=>$oaAttr['encodingaeskey'],'appid'=>$oaAttr['appid']);
		}else{
			echo "调用失败";
		}
		//******************************************************************
        //namespace可否与require同时调用？
		//******************************************************************
		$this->wechatObj=new \Wechat($options);   
		$this->wechatObj->valid();
		$this->wechatObj->getRev();
		$this->dataObj=new DanmuWechatData($this->wechatObj->getRevFrom());
		$userState=$this->dataObj->userState;
		$msg=$this->wechatObj->getRevContent();
		if($this->wechatObj->getRevType()==$this->wechatObj->MSGTYPE_TEXT){

		//进入系统，欢迎词
			if($msg=='弹幕'&&($userState=='notyet'||$userState=='exit')){
				if($this->hasAppid){
					$isSelected=$this->dataObj->initUser($this->appid);
				}else{
					$this->dataObj->initUser(false);
				}
				$idNum=$this->dataObj->getUserIdNum();
				if(isset($isSelected)&&$isSelected){
					$this->reply("欢迎进入弹幕系统！您的Id为:$idNum,现在已经可以发送弹幕了！申请房间请登录官网www.danmakupie.com,输入“退出”以退出系统");
				}
				if($idNum){
					$this->reply("欢迎进入弹幕系统！您的Id为:$idNum,请输入【房间号码】以进入房间~申请房间请登录官网www.danmakupie.com,输入“退出”以退出系统");
				}else{
					$this->reply('欢迎进入弹幕系统！请输入【房间号码】以进入房间~申请房间请登录官网www.danmakupie.com,输入“退出”以退出系统');
				}
			}

			//用户退出
			if($msg=='退出'&&($userState=='sender'||$userState=='create')){
				$info=$this->dataObj->userExit();
				if($info){
					$this->reply('系统退出成功，再次使用请输入“弹幕”,您可以登录www.danmakupie.com反馈使用体验给我们，以便我们提供更好的服务，谢谢您的使用！');
				}else{
					$this->reply('退出异常，请联系管理员，或登录官网www.danmakupie.com给我们反馈');
				}
			}

			//选择房间
			if($userState=='create'){
				if(is_numeric($msg)){
					$room=$this->dataObj->selectRoom($msg);
					if(!$room){
							$this->reply('房间不存在，请重新输入~');
					}else{
							$this->reply("选择房间成功！现在已经可以发送弹幕了！输入“退出”以退出房间");
					}
				}else{
					$this->reply('输入有误，请重新输入房间号~');
				}
			}

			//发送弹幕	
			if($userState=='sender'){
				$ret=$this->dataObj->sendMessage($msg);
				// reply('弹幕发送成功');
				$this->reply($ret);
			}
	   }

	//其余没有覆盖到的操作
	//*************************************************************
	//能否在function命名时不加action?
	//*************************************************************
	$this->reply('若要开始，请发送“弹幕”，之后按提示进行操作,如果有什么问题或建议，欢迎进入www.danmakupie.com向我们反馈~');
	     	}
	  public function reply($rep){
		global $wechatObj;
		$this->wechatObj->text($rep)->reply();
		die();
      }
}