var app = getApp();
var api = require('../../api.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    show_cash_error: false,
    show_rules: false,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    getApp().pageOnLoad(this);
    var page = this;
    app.request({
      url: api.hongbao.log,
      data: null,
      success: function (res) {
        if (res.code == 0) {
          page.setData({ left_amount: res.data.hongbao_left });
          page.setData({ guessLike: res.data.guess_like });
          page.setData({ cash_tips: res.data.cash_tips });
          page.setData({ share_tips: res.data.share_tips });
          page.setData({ can_cash: res.data.can_cash });
          page.setData({ rules: res.data.rules });
        } else {
          wx.showToast({
            title: res.msg,
            duration: 2000
          })
        }
      },
    });



    var page = this;
    app.request({
      url: api.ssds.index,
      data: null,
      success: function (res) {
        if (res.code == 0) {
          console.log("api.ssds.index", res);
          page.setData({
            player_list: res.data.list.slice(0, 9),
          });
        } else {
          wx.showToast({
            title: res.msg,
            duration: 2000
          })
        }
      },
    });
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {

  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {

  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {

  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {

  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {

  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {

  },

  viewHongbaoLog: function () {
    wx.redirectTo({
      url: '/pages/ls-hongbao-log/ls-hongbao-log',
    });
  },
  viewToupiaoHome: function () {
    wx.navigateTo({
      url: '/pages/ls-toupiao-join/ls-toupiao-join',
    })
  },

  onCash: function () {
    var page = this;
    app.request({
      url: api.hongbao.cash,
      data: null,
      success: function (res) {
        if (res.code == 0) {
          wx.showToast({
            title: "您的提现申请已经提交",
            duration: 2000
          })
        } else {
          page.setData({
            cash_error: res.msg,
            show_cash_error: true
          });
        }
      },
    });
  },
  closeCashError: function () {
    var page = this;
    page.setData({
      show_cash_error: false
    });
  },
  onShareAppMessage: function (options) {
    var user_info = wx.getStorageSync("user_info");
    return {
      path: "/pages/index/index?user_id=" + user_info.id,
      success: function (e) {
        console.log("share hongbao success", e);
      },
      imageUrl: "https://api.anmeila.com.cn/statics/images/ssds/wx_share_hongbao.png",
      title: "拉手平台 - 红包活动 全新玩法"
    }
  },
  openrule: function () {
    this.setData({ show_rules: true });
  },
  closeRules: function () {
    this.setData({ show_rules: false });
  }
})