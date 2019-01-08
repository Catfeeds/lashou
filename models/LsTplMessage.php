<?php
/**
 * Created by PhpStorm.
 * User: ZhanGuan
 * Date: 2018/6/6
 * Time: 17:57
 */

namespace app\models;


class LsTplMessage
{
    //签到成功模板消息
    const MESSAGE_TYPE_QIANDAO = 'qiandao';
    /**
     * @param LsWechatFormId $formId
     * @return boolean
     *
     */
    public static function send_tpl_message_by_qiandao(LsWechatFormId $formId, $qiandao_date_time, $remark){
        $template_id = 'adu2kiyRIoM4xwLAqc2jcCyW_M9c0-tt2Q0qsUfJkiE';
        $data = [
            'keyword1' => [
                'value' => $qiandao_date_time,
            ],
            'keyword2' => [
                'value' => $remark,
            ]
        ];

        $message_form = new LsTplMessageForm($formId->open_id, $formId->form_id, $template_id, $data);
        return $message_form->submit();
    }

    //新人红包
    const MESSAGE_TYPE_NEWER_BONUS = 'newer_bonus';
    public static function send_tpl_message_by_newer_bonus(LsWechatFormId $formId, $date_time){
        $template_id = '1jzzjaRnj_Oxk6amE0YFxtUzAYVGxl1lRbj4EnVHxrc';
        $data = [
            'keyword1' => [
                'value' => "新人红包",
            ],
            'keyword2' => [
                'value' => "5元现金",
            ],
            'keyword3' => [
                'value' => "满10元即可提现",
            ],
            'keyword4' => [
                'value' => $date_time,
            ]
        ];

        $message_form = new LsTplMessageForm($formId->open_id, $formId->form_id, $template_id, $data);
        return $message_form->submit();
    }
}