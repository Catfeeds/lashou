var api = require('../../api.js');
var ls_hongbao = {
  _page: null,
  _hongbao: null,
  _is_show: false,
  _closeCallback:null,
  init: function (page, closeCallback) {
    this._page = page;
    console.log("hongbao init page", page);

    if (typeof this._page.options.user_id != "undefined"){
      if (this._page.options.user_id > 0){
        this.needed_share_get = false;
      }
    }

    this._closeCallback = closeCallback;

    this._refreshUi();
    this._bindEvents();
  },
  setHongbao:function(hongbao, is_show){
    this._hongbao = hongbao;
    if(typeof is_show != "undefined"){
      this._is_show = is_show;
    }else{
      this._is_show = !(hongbao == null || typeof hongbao == "undefined");
    }
    this._refreshUi();
  },
  setIsShow:function(isShow){
    this._is_show = isShow;
    this._refreshUi();
  },
  onShareFriend:function(options){
    console.log("lashow hongbao share", options);
    var user_info = wx.getStorageSync("user_info");
    var context = this;
    return {
      path: "/pages/index/index?user_id=" + user_info.id,
      success: function (e) {
        console.log("share success", e);
        if (typeof e.shareTickets == "undefined"){
          wx.showToast({
            title: '请分享到群',
          })
          return;
        }
        context.setIsShow(false);
        getApp().request({
          url: api.hongbao.getNewerHongbao,
          data: null,
          success: function (res) {
            if (res.code == 0) {
              console.log("share hongbao success", e);
              wx.navigateTo({
                url: '/pages/ls-hongbao-shared/ls-hongbao-shared',
              })
            } else {
              wx.showToast({
                title: res.msg,
                duration: 2000
              })
            }

          },
        });
      },
      imageUrl:"https://api.anmeila.com.cn/statics/images/ssds/wx_share_hongbao.png",
      title: "拉手平台 - 红包活动 全新玩法"
    };
  },
  _refreshUi: function () {
    var context = this;
    context._page.setData({
      ls_hongbao: { hongbao: context._hongbao, is_show: context._is_show, needed_share_get: context.needed_share_get},
    })
  },
  _bindEvents:function(){
    var context = this;
    this._page.lsCloseHongbao = function(e){
      console.log("you click lashou close hongbao", e);
      context.setIsShow(false);
      if (context._closeCallback != null && typeof context._closeCallback != "undefined"){
        context._closeCallback();
      }
    }

    this._page.lsGetHongbao = function(e){
      context.setIsShow(false);
      getApp().request({
        url: api.hongbao.getNewerHongbao,
        data: {form_id:e.detail.formId},
        success: function (res) {
          if (res.code == 0) {
            wx.navigateTo({
              url: '/pages/ls-hongbao-shared/ls-hongbao-shared',
            })
          } else {
            wx.showToast({
              title: res.msg,
              duration: 2000
            })
          }
        },
      });
    }
  },

  needed_share_get:true,
}
module.exports = ls_hongbao;