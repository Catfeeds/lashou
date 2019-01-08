var api = require('../../api.js');
var app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    scene:"scene",
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    getApp().pageOnLoad(this);

    if (!getApp().isLogin()) {
      getApp().login();
    }

    var scene = decodeURIComponent(options.scene)

    this.setData({
      scene:scene
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
      return getApp().onShareAppMessage(options);
  },

  activeCode:function(){
    getApp().pageOnLoad(this);
    var page = this;
    console.log("wooran code");
    getApp().request({
      url: api.ssds.send298,
      data: { code: page.data.scene},
      success: function (res) {
        if (res.code == 0) {
          wx.showToast({
            title: res.msg,
            duration: 2000
          });
          wx.redirectTo({
            url: '/pages/index/index',
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

  gotoIndex:function(){
    wx.redirectTo({
      url: '/pages/index/index',
    })
  }
})