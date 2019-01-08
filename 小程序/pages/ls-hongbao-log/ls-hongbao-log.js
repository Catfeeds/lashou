var app = getApp();
var api = require('../../api.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    show_cash_error: false,
    show_rules:false,
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
          page.setData({
            hongbao:res.data
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

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function (options) {
    console.log("share options", options);
    return getApp().onShareAppMessage(options);
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

  openrule:function(){
    this.setData({ show_rules:true});
  },
  closeRules: function () {
    this.setData({ show_rules: false});
  }
})