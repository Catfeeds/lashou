
//var _api_root = 'https://lashou.wtsgod.com/index.php?store_id=1&r=api/';
//var _api_root = 'https://api.anmeila.com.cn/index.php?store_id=1&r=api/';
//var _api_root = 'http://video.cc/index.php?store_id=1&r=api/';
var _api_root = 'http://127.0.0.1/index.php?store_id=1&r=api/';


var api = {
    index: _api_root + 'default/index',
    default: {
        store: _api_root + 'default/store',
        index: _api_root + 'default/index',
        goods_list: _api_root + 'default/goods-list',
        cat_list: _api_root + 'default/cat-list', 
        goods: _api_root + 'default/goods',
        district: _api_root + 'default/district',
        goods_attr_info: _api_root + "default/goods-attr-info",
        upload_image: _api_root + "default/upload-image",
        comment_list: _api_root + "default/comment-list",
        article_list: _api_root + "default/article-list",
        article_detail: _api_root + "default/article-detail",
        video_list: _api_root + "default/video-list",
        goods_qrcode: _api_root + "default/goods-qrcode",
        coupon_list: _api_root + "default/coupon-list",
        topic_list: _api_root + "default/topic-list",
        topic: _api_root + "default/topic",
        navbar: _api_root + "default/navbar",
        navigation_bar_color: _api_root + "default/navigation-bar-color",
        shop_list: _api_root + "default/shop-list",
        shop_detail: _api_root + "default/shop-detail",
        topic_type: _api_root + "default/topic-type",
        buy_data: _api_root + "default/buy-data",
        goods_recommend: _api_root + "default/goods-recommend",
        launch: _api_root + "default/launch",
    },
    cart: {
        list: _api_root + 'cart/list',
        add_cart: _api_root + 'cart/add-cart',
        delete: _api_root + 'cart/delete',
        cart_edit: _api_root + 'cart/cart-edit',
    },
    passport: {
        login: _api_root + 'passport/login',
        on_login: _api_root + 'passport/on-login',
    },
    order: {
        submit_preview: _api_root + 'order/submit-preview',
        submit: _api_root + 'order/submit',
        pay_data: _api_root + 'order/pay-data',
        list: _api_root + 'order/list',
        revoke: _api_root + 'order/revoke',
        confirm: _api_root + 'order/confirm',
        count_data: _api_root + 'order/count-data',
        detail: _api_root + 'order/detail',
        refund_preview: _api_root + 'order/refund-preview',
        refund: _api_root + 'order/refund',
        refund_detail: _api_root + 'order/refund-detail',
        comment_preview: _api_root + 'order/comment-preview',
        comment: _api_root + 'order/comment',
        express_detail: _api_root + 'order/express-detail',
        clerk: _api_root + "order/clerk",
        clerk_detail: _api_root + 'order/clerk-detail',
        get_qrcode: _api_root + 'order/get-qrcode',
        location: _api_root + 'order/location'
    },
    activity:{
      vote:{
        upLoadVideo: _api_root + 'activity/vote/up-video',
        edit: _api_root + 'activity/vote/edit',
        upLoadImage: _api_root + 'activity/vote/up-image',
        
      }
    },
    user: {
        address_list: _api_root + 'user/address-list',
        address_detail: _api_root + 'user/address-detail',
        address_save: _api_root + 'user/address-save',
        address_set_default: _api_root + 'user/address-set-default',
        address_delete: _api_root + 'user/address-delete',
        save_form_id: _api_root + "user/save-form-id",
        favorite_add: _api_root + "user/favorite-add",
        favorite_remove: _api_root + "user/favorite-remove",
        favorite_list: _api_root + "user/favorite-list",
        index: _api_root + "user/index",
        wechat_district: _api_root + "user/wechat-district",
        add_wechat_address: _api_root + "user/add-wechat-address",
        topic_favorite: _api_root + "user/topic-favorite",
        topic_favorite_list: _api_root + "user/topic-favorite-list",
        member: _api_root + "user/member",
        card: _api_root + "user/card",
        card_qrcode: _api_root + "user/card-qrcode",
        card_clerk: _api_root + "user/card-clerk",
    },
    share: {
        join: _api_root + 'share/join',
        check: _api_root + 'share/check',
        get_info: _api_root + 'share/get-info',
        get_price: _api_root + 'share/get-price',
        apply: _api_root + 'share/apply',
        cash_detail: _api_root + 'share/cash-detail',
        get_qrcode: _api_root + 'share/get-qrcode',
        shop_share: _api_root + 'share/shop-share',
        bind_parent: _api_root + 'share/bind-parent',
        get_team: _api_root + 'share/get-team',
        get_order: _api_root + 'share/get-order',

        get_detailed: _api_root + 'share/select-detailed',


        profit_detail: _api_root + 'share/select-detailed',

    },
    coupon: {
        index: _api_root + 'coupon/index',
        share_send: _api_root + 'coupon/share-send',
        receive: _api_root + 'coupon/receive',
    },
    miaosha: {
        list: _api_root + 'miaosha/list',
        goods_list: _api_root + 'miaosha/goods-list',
        details: _api_root + 'miaosha/details',
        submit_preview: _api_root + 'miaosha/submit-preview',
        submit: _api_root + 'miaosha/submit',
        pay_data: _api_root + 'miaosha/pay-data',
        order_list: _api_root + 'miaosha/order-list',
        order_details: _api_root + 'miaosha/order-details',
        order_revoke: _api_root + 'miaosha/revoke',
        express_detail: _api_root + 'miaosha/express-detail',
        confirm: _api_root + 'miaosha/confirm',
        comment_preview: _api_root + 'miaosha/comment-preview',
        comment: _api_root + 'miaosha/comment',
        refund_preview: _api_root + 'miaosha/refund-preview',
        refund: _api_root + 'miaosha/refund',
        refund_detail: _api_root + 'miaosha/refund-detail',
        comment_list: _api_root + "miaosha/comment-list",
        goods_qrcode: _api_root + "miaosha/goods-qrcode",
    },
    group: {
        index: _api_root + 'group/index/index',
        list: _api_root + 'group/index/good-list',
        details: _api_root + 'group/index/good-details',
        goods_attr_info: _api_root + "group/index/goods-attr-info",
        submit_preview: _api_root + 'group/order/submit-preview',
        submit: _api_root + 'group/order/submit',
        pay_data: _api_root + 'group/order/pay-data',
        order: {
            list: _api_root + 'group/order/list',
            detail: _api_root + 'group/order/detail',
            express_detail: _api_root + 'group/order/express-detail',
            comment_preview: _api_root + 'group/order/comment-preview',
            comment: _api_root + 'group/order/comment',
            confirm: _api_root + 'group/order/confirm',
            goods_qrcode: _api_root + 'group/order/goods-qrcode',
            get_qrcode: _api_root + 'group/order/get-qrcode',
            clerk: _api_root + 'group/order/clerk',
            clerk_order_details: _api_root + 'group/order/clerk-order-details',
            revoke: _api_root + 'group/order/revoke',
            refund_preview: _api_root + 'group/order/refund-preview',
            refund: _api_root + 'group/order/refund',
            refund_detail: _api_root + 'group/order/refund-detail',
        },
        group_info: _api_root + 'group/order/group',
        comment: _api_root + 'group/index/goods-comment',
        goods_qrcode: _api_root + 'group/index/goods-qrcode',
        search: _api_root + 'group/index/search',
    },
    book: {
        index: _api_root + 'book/index/index',
        list: _api_root + 'book/index/good-list',
        details: _api_root + 'book/index/good-details',
        submit_preview: _api_root + 'book/order/submit-preview',
        submit: _api_root + 'book/order/submit',
        order_list: _api_root + 'book/order/list',
        order_cancel: _api_root + 'book/order/cancel',
        order_pay: _api_root + 'book/order/pay-data',
        order_details: _api_root + 'book/order/order-details',
        shop_list: _api_root + 'book/index/shop-list',
        get_qrcode: _api_root + 'book/order/get-qrcode',
        clerk: _api_root + 'book/order/clerk',
        apply_refund: _api_root + 'book/order/apply-refund',
        comment_preview: _api_root + 'book/order/comment-preview',
        submit_comment: _api_root + 'book/order/comment',
        goods_comment: _api_root + 'book/index/goods-comment',
        goods_qrcode: _api_root + 'book/index/goods-qrcode',
        clerk_order_details: _api_root + 'book/order/clerk-order-details',

    },
    quick: {
      quick: _api_root + 'quick/quick/quick',
      quick_goods: _api_root + 'quick/quick/quick-goods',
      quick_car: _api_root + 'quick/quick/quick-car',
    },
    fxhb: {
        open: _api_root + 'fxhb/index/open',
        open_submit: _api_root + 'fxhb/index/open-submit',
        detail: _api_root + 'fxhb/index/detail',
        detail_submit: _api_root + 'fxhb/index/detail-submit',
    },
    recharge: {
        index: _api_root + 'recharge/index',
        list: _api_root + 'recharge/list',
        submit: _api_root + 'recharge/submit',
        submit_share: _api_root + 'recharge/submit_share',
        record: _api_root + 'recharge/record',
    },
    partner: {
        check: _api_root + 'partner/check',
        join: _api_root + 'partner/join',
        market: _api_root + 'partner/partner-by-order-get',
        order_grab: _api_root + 'partner/partner-by-order-grab',
        myOrders: _api_root + 'partner/partner-my-orders',
        order_sendGoods: _api_root + 'partner/partner-send-goods',
        get_info: _api_root + 'partner/get-info',
        detail: _api_root + 'partner/partner-by-detail',
    },
  
      
 
    hongbao:{
      getNewerHongbao: _api_root + 'hongbao/newer-get',
      cash: _api_root + 'hongbao/cash',
      log: _api_root + 'hongbao/log'
    },
    ssds:{
      join: _api_root + 'ssdsplayer/join',
      info: _api_root + 'ssdsplayer/info',
      vote: _api_root + 'ssdsplayer/vote',
      index: _api_root + 'ssdsplayer/index',
      update_weight: _api_root + 'ssdsplayer/update-weight',
      getPhoneNumber: _api_root + 'user/user-binding',
      send298: _api_root + 'default/send298',
      qiandao: _api_root + 'ssdsplayer/qiandao',
      qiandao_v2: _api_root + 'lashou/qiandao',
      check_on_line:_api_root + 'ssds/check-on-line',
      share: _api_root + 'ls-share/share',
      share_from: _api_root + 'ls-share/share-from',
      share_qr: _api_root + 'ls-share/get-qr'
    },

    anmeila:{
      team: _api_root + 'anmeila/team'
    },
    act: {
      sendsms: _api_root + 'act/sendsms',
      getphoneinfo: _api_root + 'act/getphoneinfo',
      getuserinfo: _api_root + 'act/getuserinfo',
	    removebd:_api_root+'act/removebd',
      giveorder: _api_root + 'act/giveorder',
      getexpress: _api_root + 'act/getexpress',
      ssdsqrcode: _api_root + 'act/ssdsqrcode',
    },
    choujiang:{
      index: _api_root + 'choujiang/index',
      more_prize: _api_root + 'choujiang/more-prize',
      active: _api_root + 'choujiang/active',
    },
    form_id:{
      add_newer_bonus: _api_root + 'lashou/add-form-id-newer-bonus',
    }
};
module.exports = api;