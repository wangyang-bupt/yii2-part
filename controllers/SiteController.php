<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\UploadForm;
use yii\web\UploadedFile;
use app\models\Update;
use app\models\TextForm;
use app\models\DanmuWebData;
use app\models\OfficialAccount;
use app\models\Sequence;

class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public $userName;
    public $passwdHash;
    public $dataObj;
    public $para;
    public $channel;
    public $rid;
    public $channel_url;

    /*public function init(){
      parent::init();
      
    }*/
    
    public function actionUpload()
    {
        $model = new UploadForm();
        $text = new TextForm();

        if (Yii::$app->request->isPost&& $text->load(Yii::$app->request->post())) {
            $model->file = UploadedFile::getInstance($model, 'file');

            if ($model->file && $model->validate() && $text->validate()) {                
                $model->file->saveAs('../../software/' . $model->file->baseName . '.' . $model->file->extension);
                $update=new Update();
                $update->new_edition=$model->file->baseName;
                $update->update_time=date("Y-m-d H:i:s", time() + 8 * 3600);
                $update->instruction=$text->textinfo;
                $update ->save();
                $str=$model->file->baseName;
                $str1=substr($str,12);
                $data_array = array(
                    array(
                    'AppName' => 'DanmakuPie',
                    'AppVersion' => "$str1",
                    'RequiredMinVersion' => "$str1",
                    'UpdateMode'=>"UpdateToNew",
                    'Description'=>'hhhhh',
                    ),
                    /*array(
                    'title' => 'title2',
                    'content' => 'content2',
                    'pubdate' => '2009-11-11',
                    )*/
                );
                $attribute_array = array(
                 //   'title' => array(
                 //   'size' => 1
                 //  )
                );
$string = <<<XML
<?xml version='1.0' encoding='utf-8'?>
<UpdateInfo>
</UpdateInfo>
XML;
                $xml = simplexml_load_string($string);
               foreach ($data_array as $data) {
              //$item = $xml->addChild('item');
                if (is_array($data)) {
                  foreach ($data as $key => $row) {
                  $node = $xml->addChild($key, $row);
                      if (isset($attribute_array[$key]) && is_array($attribute_array[$key]))
                        {
                          foreach ($attribute_array[$key] as $akey => $aval) {
                         //  设置属性值
                              $node->addAttribute($akey, $aval);
                        }
                      }
                    }
                }
            }
                echo $xml->asXML("../../update/update.xml"); 
                
            }
        }

        return $this->render('upload', ['model' => $model,'text'=>$text,]);
    }
    public function actionHalt($errMark){
        $controlError=array(
      '0'=>array('loginErr','noUserOrPasswd'),
      '1'=>array('funcErr','noSetFunction'),
      '2'=>array('validErr','validError'),
      '3'=>array('funcErr','noFunction'),
      '4'=>array('setAppErr','paraErr'),  
      '5'=>array('getQrErr','paraErr'),
      '6'=>array('replyErr','noReply'),
      '7'=>array('toggleShowErr','paraErr'),
      '8'=>array('seqNumErr','paraErr'),
      );
      $errType=$controlError[$errMark][0];
      $errName=$controlError[$errMark][1];
      $reply=array('errType'=>$errType,'errMsg'=>$errName,'errMark'=>$errMark);
      $this->actionReply($reply);
    }
    public function actionPreValid($iset=false){
        global $dataObj;
        global $passwdHash;
        if(!$this->userName&&!$this->passwdHash&&!$this->para){
          $this->userName=yii::$app->request->get('userName');
          $this->passwdHash=yii::$app->request->get('passwdHash');
          $this->para=yii::$app->request->get('para');
          if(!isset($this->userName)||!isset($this->passwdHash)){
              $this->actionHalt(0);
          }
        }
          $this->dataObj=new DanmuWebData($this->userName);
          // $this->$channel=new Saechannel();
          $this->rid=$this->dataObj->getRoomId();
        if(!$this->dataObj->validAdmin($this->passwdHash)){
          $this->actionHalt(2);
        }
        if($isRet){
          return true;
        }
    }
    public function actionGenQrCode($roomid){
      require_once('../models/phpqrcode.php');
      $qrcode=new \QRcode();
      $qrcode::png('http://danmu.zhengzi.me/');
    }

    public function actionReply($reply){
      if(isset($reply)){
        echo $replyJson=json_encode($reply,JSON_UNESCAPED_SLASHES);
      }else{
        $this->actionHalt(6);
      }
      exit();
    }


//****************前端直接访问的api 形式为?r=site/action*********************
    public function actionValid(){
      $reply=$this->actionPreValid(true);
      if(isset($reply)){
        //$this->channel_url=$this->channel->createChannel($rid,24*60*60);
        echo $replyJson=json_encode($reply,JSON_UNESCAPED_SLASHES);
      }else{
        $this->actionHalt(6);
      }
      //exit($this->channel_url);
    } 
    public function actionCreateadmin(){
      $this->passwdHash=yii::$app->request->get('passwdHash');
      $this->userName=yii::$app->request->get('userName');
      $this->dataObj=new DanmuWebData($this->userName);
      $this->actionReply($this->dataObj->createAdmin($this->passwdHash));
    }
    public function actionCreateroom(){
      $this->actionPreValid();
      $this->actionReply($this->dataObj->createRoom());
    }
    /*public function actionGetByChannel($seqid,$rid){
      if($this->rid==$rid){
       $msg=Sequence::find($seqid)->one();
       $message=['seq_id'=>"$seqid",'seq_time'=>date('Y-m-d H:i:s',$msg->time),'seq_content'=>$msg->content];
       $send = $this->channel->sendMessage($this->rid, $msg);
     }
    }*/
    public function actionGetstate(){
      $this->actionPreValid();
      $this->actionReply($this->dataObj->getState());
    }
    public function actionDestroyroom(){
      $this->actionPreValid();
      $this->actionReply($this->dataObj->destoryRoom());
    }
    public function actionBindoa(){
      $this->actionPreValid();
      @$appId=$this->para['appId'];
      @$appName=$this->para['appName'];
      @$appToken=$this->para['appToken'];
      @$appKey=$this->para['appKey'];
      if(!isset($appId)||!isset($appName)||!isset($appToken)||!isset($appKey)){
      $this->actionHalt(4);
      }
      $oaObj=new OfficialAccount($appId,$this->userName);
      $status=$oaObj->setAttr($appToken,$appKey,$appName);
      $this->actionReply($status);
    }
    public function actionGetqr(){
      @$roomId=yii::$app->request->get('para');
      if(!isset($roomId)){
        $this->actionHalt(5);
      }
      $this->actionGenQrCode($roomId);
    }
    public function actionTogglemode(){
      $this->actionPreValid();
      @$ctrl=yii::$app->request->get('ctrl');
      if(!isset($ctrl)){
        $this->actionHalt(7);
      }
      switch($ctrl){
        case 'ok':
          $mode='show';
          break;
        case 'show':
          $mode='review';
          break;
        case 'review':
          $mode='ok';
          break;
        default:
          $this->actionHalt(7);
      }
      $ret=$this->dataObj->toggleMode($mode);
      $this->actionReply($ret);
    }
    public function actionGetroomid(){
       $this->actionPreValid();
       $ret=$this->dataObj->getRoomId();
       $this->actionReply($ret);
    }
    public function actionSeqdel(){
      $this->actionPreValid();
     @$seqNum=yii::$app->request->get('seqNum');
     if(!isset($seqNum)){
       $this->actionHalt(8);
     }
     $ret=$this->dataObj->seqDel($seqNum);
     $this->actionReply($ret);     
    }
    public function actionSeqreview(){
      $this->actionPreValid();
      @$seqNum=yii::$app->request->get('seqNum');
      if(!isset($seqNum)){
        $this->actionHalt(8);
      }
      $ret=$this->dataObj->seqReview($seqNum);
      $this->actionReply($ret);
    }
}
