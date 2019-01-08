// pages/share/index.js
var api = require('../../../api.js');
var app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    total_price: 0,
    price: 0,
    cash_price: 0,
    total_cash: 0,
    team_count: 0,
    order_money: 0,
    partner:[]
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    app.pageOnLoad(this);
    var page = this;
    var user_info = wx.getStorageSync("user_info");
    console.log(user_info)
    page.setData({
      custom: wx.getStorageSync('custom')
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
    var page = this;
    var user_info = wx.getStorageSync("user_info");
    if (user_info.is_partner != 1) {
      wx.redirectTo({
        url: '/pages/partner/reg/reg',
      })
    } else {
      page.setData({
        user_info: user_info,
      });
      wx.showLoading({
        title: "正在加载",
        mask: true,
      });
      app.request({
        url: api.partner.get_info,
        success: function (res) {
          console.log(res);
          if (res.code == 0) {
            page.setData({
              total_price: res.data.price.total_price,
              price: res.data.price.price,
              partner: res.data.partner,
              cash_price: res.data.price.cash_price,
              total_cash: res.data.price.total_cash,
              team_count: res.data.team_count,
              order_money: res.data.order_money,
              custom: res.data.custom
            });
            wx.setStorageSync('custom', res.data.custom);
            console.log(res.data.custom)
          }
        },
        complete: function () {
          wx.hideLoading();
        }
      });

    }
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

})