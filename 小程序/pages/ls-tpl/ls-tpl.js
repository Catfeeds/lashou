var app = getApp();
Page({

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    app.pageOnLoad(this);
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function (options) {
    return getApp().onShareAppMessage(options);
  }
})