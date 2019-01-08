// pages/choujiang/choujiang.js
var api = require('../../api.js');
var MAX_SHARE_COUNT = 2;
Page({

  data: {
    max_share_count: MAX_SHARE_COUNT,//常量
    left_share_count:0,
    choujiang_count:0,
    prize_list:[],
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    console.log("times", 1);
    wx.updateShareMenu({
      withShareTicket: true,
    });
    //this.beginChoujiang();
    getApp().pageOnLoad(this);

    console.log("choujiang onload", options, typeof options.scene == 'undefined' ? "" : decodeURIComponent(options.scene));
    this.setData({
      code: decodeURIComponent(options.scene)
    })
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    var user_info = wx.getStorageSync("user_info");
    var context = this;
    return {
      path: "/pages/choujiang/choujiang?user_id=" + user_info.id,
      /*success: function (e) {
        console.log("share success", e);
        if (typeof e.shareTickets == "undefined") {
          wx.showToast({
            title: '请分享到群',
          })
          return;
        }
        context.shareGetMorePrize();
      },*/
      imageUrl: "https://api.anmeila.com.cn/statics/images/ssds/wx_share_hongbao.png",
      title: "拉手平台"
    };
  },
})