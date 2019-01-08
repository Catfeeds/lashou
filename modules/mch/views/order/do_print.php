<?php
$order_info = $order_info->toArray();
$order_info['address_data'] = json_decode($order_info['address_data'], true);
//print_r($order_info['address_data']);

$order_express['order'] = json_decode($order_express['order'], true);
$order_express['sender'] = json_decode($order_express['sender'], true);
//print_r($order_express['sender']);
//print_r([$order_express, $order_info, $order_goods_list]);
?>
<!--100*150,90-->
<html>
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>面单打印</title>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/js/jquery.min.js"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/js/JsBarcode.all.min.js"></script>
    <script src="<?= Yii::$app->request->baseUrl ?>/statics/mch/js/qrcode.min.js?v=mine"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            font-family: "Microsoft YaHei";
            -webkit-text-size-adjust: none;
        }

        .print_paper {
            border: none;
            border-collapse: collapse;
            width: 375px;
            margin-top: -1px;
            table-layout: fixed;
        }

        .print_paper td {
            border: solid #000 1px;
            padding: 0 5px;
            overflow: hidden;
        }

        .table_first {
            margin-top: 0;
        }

        .print_paper .x1 {
            font-size: 12pt;
            font-family: "Microsoft YaHei";
            /*-webkit-transform-origin-x: 0;*/
            /* -webkit-transform: scale(0.67);*/
        }

        .print_paper .x4 {
            font-size: 22pt;
            font-weight: bold;
            font-family: "Microsoft YaHei";
            text-align: left;
        }

        .print_paper .xx8 {
            font-size: 16pt;
            text-align: left;
            font-family: "Microsoft YaHei";
        }

        .print_paper .xx10 {
            font-size: 9pt;
            font-weight: bold;
            font-family: "Microsoft YaHei";
            text-align: left;
            /*-webkit-transform-origin-x: 0;*/
            /* -webkit-transform: scale(0.67);*/
        }

        .print_paper .xx12 {
            font-size: 8pt;
            text-align: center;
            font-family: "Microsoft YaHei";
            /*-webkit-transform-origin-x: 0;*/
            /* -webkit-transform: scale(0.67);*/
        }

        .print_paper .xx14 {
            font-size: 8pt;
            text-align: left;
            font-family: "Microsoft YaHei";
            /*-webkit-transform-origin-x: 0;*/
            /* -webkit-transform: scale(0.67);*/
        }

        .print_paper .xx15 {
            font-size: 8pt;
            text-align: center;
            font-family: "Microsoft YaHei";
            /*-webkit-transform-origin-x: 0;*/
            /* -webkit-transform: scale(0.67);*/
        }

        .print_paper .xx16 {
            font-size: 6pt;
            text-align: left;
            font-family: "Microsoft YaHei";
            /*-webkit-transform-origin-x: 0;*/
            /* -webkit-transform: scale(0.67);*/
        }

        .print_paper .xx17 {
            font-size: 8pt;
            font-family: "Microsoft YaHei";
            text-align: left;
            /*-webkit-transform-origin-x: 0;*/
            /* -webkit-transform: scale(0.67);*/
        }

        .print_paper .xx18 {
            font-size: 8pt;
            font-weight: bold;
            font-family: "Microsoft YaHei";
            text-align: right;
            /*-webkit-transform-origin-x: 0;*/
            /* -webkit-transform: scale(0.67);*/
        }

        .no_border {
            width: 100%;
            height: 100%;
        }

        .no_border td {
            border: none;
            vertical-align: top;
        }

        .print_paper .fwb {
            font-weight: bold;
        }

        .print_paper .f24 {
            font-family: "Arial";
            font-size: 24pt;
        }

        .print_paper .p0 {
            padding: 0;
        }

        .print_paper .ovh {
            overflow: hidden;
        }

        .print_paper .ov {
            overflow: visible;
        }

        .wa {
            width: 100%;
        }

        .h30 {
            height: 18.9px;
            border-bottom: 1px solid #000;
            width: 91px;
        }

        .h31 {
            height: 94.5px;
            width: 80px;
            font-size: 7pt;
            font-family: "Microsoft YaHei";
        }

        .vt {
            vertical-align: top;
        }

        @media screen and (-webkit-min-device-pixel-ratio:0) {
            .guge {
                word-spacing: 0;
                letter-spacing: 0;
                line-height: 20px;
                font-size: 12px;
                height: 72px;
                margin-top: -19px;
                -webkit-transform-origin-x: 0;
                -webkit-transform: scaleX(0.70);
                -webkit-transform: scaleY(0.65);
            }
        }
    </style>
</head>
<body>
<table class="print_paper table_first" height="34">
    <tr>
        <td style="padding:0;">
            <table class="no_border">
                <tr>
                    <td style="vertical-align:middle;padding:0;" width="113.4"><img style="display: none;" class="logo" height="22.7" width="98.3" src=""></td>
                    <td style="vertical-align:middle;">&nbsp;</td>
                    <td style="vertical-align:middle;text-align:right;" class="x1">标准快递</td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<table class="print_paper">
    <tr>
        <td style="padding:0;" width="370.5" height="56.7" class="x4">
            <?= $order_express['order']['MarkDestination']?>
        </td>
    </tr>
</table>
<table class="print_paper" height="37.8">
    <tr>
        <td style="padding:0;" class="xx8">
            <?= $order_express['order']['PackageName']?>
        </td>
    </tr>
</table>
<table class="print_paper" style="position: relative">
    <!-- <div style="position: absolute;left:21px;top:115px;width: 270px;height: 120px;font-size: 80px;color:#666;opacity: 0.5;padding-left: 20px;font-weight:bold;"></div> -->
    <tr>
        <td style="padding:0;" width="19" height="68" class="xx15">收<br />件</td>
        <td width="261" class="xx10">
            <div style="height:66px;overflow:hidden;">
                <?= $order_info['name']?>&nbsp;<?= $order_info['mobile']?><br />
                <?= $order_info['address_data']['province']?> <?= $order_info['address_data']['city']?> <?= $order_info['address_data']['district']?> <?= $order_info['address_data']['detail']?>
            </div>
        </td>
        <td class="vt" style="padding:0;" rowspan="2">
            <div class="wa h30 xx12">服务</div>
            <div class="wa h31">
                付款方式：寄付月结<br />
            </div>
        </td>
    </tr>
    <tr>
        <td style="padding:0;" width="19" height="54" class="xx15">寄件</td>
        <td width="261" class="xx14">
            <div style="height:44px;overflow:hidden;">
                <?= $order_express['sender']['Name']?>&nbsp;<?= $order_express['sender']['Mobile']?><br />
                <?= $order_express['sender']['ProvinceName']?> <?= $order_express['sender']['CityName']?> <?= $order_express['sender']['ExpAreaName']?> <?= $order_express['sender']['Address']?>
            </div>
        </td>
    </tr>
</table>
<table class="print_paper" height="94.5">
    <tr>
        <td style="text-align:center;padding:0;">
            <img height="56" data-height="56" data-logistic-code="<?= $order_express['order']['LogisticCode']?>" width="270" src="" />
            <br />
            <?= $order_express['order']['LogisticCode']?>
        </td>
    </tr>
</table>
<table class="print_paper">
    <tr>
        <td width="218" style="padding:0;" class="xx16" height="71.8">
            <div class="guge">快件送达收件人地址，经收件人或收件人（寄件人）允许的代收人签字，视为送达。您的签字代表您已验收此包裹，并已确认商品信息无误、包装完好、没有划痕、破损等表面质量问题。</div>
        </td>
        <td width="81.15" style="padding:0;" class="xx17" height="71.8">
            签收人：<br /><br />时间:
        </td>
        <td width="71.15" style="padding:0; text-align: center" class="xx17" height="71.8">
            <div class="qrcode" id="qrcode" data-val="<?= $order_info['order_no']?>"></div>
        </td>
    </tr>
</table>
<table class="print_paper table_first">
    <tr>
        <td style="padding:0;">
            <table class="no_border">
                <tr>
                    <td style="vertical-align:middle;padding:0;" width="113.4" height="55"><img style="display: none;" class="logo" height="22" width="98.3" src="" alt="" /></td>
                    <td style="vertical-align:middle;padding:0;" height="55">&nbsp;</td>
                    <td style="vertical-align:middle;text-align:center;padding:0;" height="55" width="257">
                        <img height="30" data-height="30"  data-logistic-code="<?= $order_express['order']['LogisticCode']?>" width="176" src="" />
                        <?= $order_express['order']['LogisticCode']?>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<table class="print_paper ">
    <tr>
        <td style="padding:0;" width="20" height="45" class="xx15">收件</td>
        <td width="342" height="45.5" class="xx14">
            <div style="height:44px;overflow:hidden;">
                <?= $order_info['name']?>&nbsp;<?= $order_info['mobile']?><br />
                <?= $order_info['address_data']['province']?> <?= $order_info['address_data']['city']?> <?= $order_info['address_data']['district']?> <?= $order_info['address_data']['detail']?>
            </div>
        </td>
    </tr>
    <tr>
        <td style="padding:0;" width="20" height="45" class="xx15">寄件</td>
        <td width="342" height="45.5" class="xx14">
            <div style="height:44px;overflow:hidden;">
                <?= $order_express['sender']['Name']?>&nbsp;<?= $order_express['sender']['Mobile']?><br />
                <?= $order_express['sender']['ProvinceName']?> <?= $order_express['sender']['CityName']?> <?= $order_express['sender']['ExpAreaName']?> <?= $order_express['sender']['Address']?>

            </div>
        </td>
    </tr>
    <tr>
        <td height="108" colspan="2" style="padding:0;">
            <table class="no_border">
                <tr>
                    <td style="padding:0; overflow: hidden" height="90%" width="370" class="xx10">
                        <?php
                        foreach($order_goods_list as $order_goods){
                            $attr = [];
                            foreach ($order_goods['attr_list'] as $attr_item){
                                $attr[] = $attr_item->attr_name;
                            }

                            //echo '<div class="f8" style="font-weight:normal;">' . $order_goods['num'] . ' * ' . $order_goods['name'] . '【' . implode(',', $attr) . '】</div>';
                        }
                        ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0;" width="370" class="xx18">
                        已检视
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<script>
    $(function () {
        $('img').each(function () {
            var logisticCode = $(this).attr('data-logistic-code');
            var height = parseInt($(this).attr('data-height'));
            if(typeof logisticCode == 'undefined' || logisticCode == null || logisticCode.length <= 0){
                console.log("not logistic code");
                return;
            }
            console.log("is logistic code");
            $(this).JsBarcode(logisticCode, {
                displayValue:false,
                height: height,
                margin:0,
                width:2,
            });
        });

        var qrcode = new QRCode(document.getElementById("qrcode"), {
            text: $('#qrcode').attr('data-val'),
            width: 60,
            height: 60,
            colorDark : "#000000",
            colorLight : "#ffffff",
            correctLevel : QRCode.CorrectLevel.H
        });
    });
</script>
</body>
</html>