// pages/xptj/xptj.js
var api = require('../../api.js');
var app = getApp();
var is_loading_more = false;
var is_no_more = false;
Page({

  /**
   * 页面的初始数据
   */
  data: {
    goods_list: [],
    page: 1,
    cat_id: 24,
    hasmore: false
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
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    var page = this;
    if (is_no_more)
      return;
    page.loadMoreGoodsList();
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function (options) {
    return getApp().onShareAppMessage(options);
  },

  reloadGoodsList: function () {
    var page = this;
    is_no_more = false;
    page.setData({
      page: 1,
      goods_list: [],
      show_no_data_tip: false,
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
          if (res.data.list.length == 0)
            is_no_more = true;
          page.setData({ page: (p + 1) });
          page.setData({ goods_list: res.data.list });
        }
        page.setData({
          show_no_data_tip: (page.data.goods_list.length == 0),
        });
      },
      complete: function () {
        //wx.hideNavigationBarLoading();
      }
    });
  },

  loadMoreGoodsList: function () {
    var page = this;
    if (is_loading_more)
      return;
    page.setData({
      show_loading_bar: true,
    });
    is_loading_more = true;
    var cat_id = page.data.cat_id;
    var p = page.data.page;
    app.request({
      url: api.default.goods_list,
      data: {
        page: p,
        cat_id: cat_id,
      },
      success: function (res) {
        if (res.data.list.length == 0)
          is_no_more = true;
        var goods_list = page.data.goods_list.concat(res.data.list);
        page.setData({
          goods_list: goods_list,
          page: (p + 1),
        });
      },
      complete: function () {
        is_loading_more = false;
        page.setData({
          show_loading_bar: false,
        });
      }
    });
  }
})