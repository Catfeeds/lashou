// pages/xptj/xptj.js
var api = require('../../api.js');
var app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    goods_list: [],
    cat_id: 26,
    page:1,
    random: Math.random(),
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    app.pageOnLoad(this);
    this.reloadGoodsList();
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
   * 用户点击右上角分享
   */
  onShareAppMessage: function (options) {
    return getApp().onShareAppMessage(options);
  },

  reloadGoodsList: function () {
    var page = this;
    page.setData({
      page: 1,
      goods_list: [],
    });
    var cat_id = page.data.cat_id;
    var p = page.data.page || 1;
    //wx.showNavigationBarLoading();
    app.request({
      url: api.default.goods_list,
      data: {
        cat_id: cat_id,
        page: p,
      },
      success: function (res) {
        if (res.code == 0) {
            page.setData({ page: (p + 1) });
            page.setData({ goods_list: res.data.list });
        }
      },
      complete: function () {
        //wx.hideNavigationBarLoading();
      }
    });
  },
})