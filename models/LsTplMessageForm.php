<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/6/6
 * Time: 18:05
 */

namespace app\models;


use luweiss\wechat\Wechat;

/**
 * Class LsTplMessageForm
 * @package app\models
 *
 * @property Wechat $wechat
 *
 */

class LsTplMessageForm
{
    private $touser; //必填
    private $template_id; //必填
    private $page;
    private $form_id; //必填
    private $data; //必填

    private $color; //废弃
    private $emphasis_keyword; //需要放大的关键词

    private $wechat;

    public function __construct($touser, $form_id, $template_id, $data)
    {
        $this->touser = $touser;
        $this->form_id = $form_id;
        $this->template_id = $template_id;
        $this->data = $data;

        $this->wechat = $this->getWechat();
    }

    public function setPage($page){
        $this->page = $page;
    }

    public function setEmphasisKeyword($keywords){
        $this->emphasis_keyword = $keywords;
    }


    /**
     *
     * @return boolean
     *
     */
    public function submit(){
        if($this->wechat == null){
            return false;
        }

        //构造请求数据
        $post_data = [
            'touser' => $this->touser,
            'template_id' => $this->template_id,
            'form_id' => $this->form_id,
            'data' => $this->data,
        ];
        if($this->page){
            $post_data['page'] = $this->page;
        }
        if($this->emphasis_keyword){
            $post_data['emphasis_keyword'] = $this->emphasis_keyword;
        }

        //发送消息
        $access_token = $this->wechat->getAccessToken();
        $api = "https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token={$access_token}";
        $post_data = json_encode($post_data, JSON_UNESCAPED_UNICODE);
        $this->wechat->curl->post($api, $post_data);
        $post_res = json_decode($this->wechat->curl->response, true);
        if (!empty($post_res['errcode']) && $post_res['errcode'] != 0) {
            return false;
        }

        return true;
    }

    /**
     * @return Wechat
     *
     */
    private function getWechat()
    {
        return empty(\Yii::$app->controller->wechat) ? null : \Yii::$app->controller->wechat;
    }
}