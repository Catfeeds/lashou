// pages/test/test.js
var api = require('../../api.js');
var app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {},

  formSubmit: function (e) {
    console.log(e);
    app.saveFormId(e.detail.formId);

  },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
      getApp().pageOnLoad(this);

      wx.getSetting({
        success:function(res){
          console.log('setting is ', res);
        }
      })  
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
    return {
      path: "/pages/ls-activity/ls-activity?user_id=222&from_activity=1",
      success: function (e) {
        console.log("share success", e);
      },
      imageUrl: "https://api.anmeila.com.cn/statics/images/activity/cover-share.jpg",
      title: "拉手平台 - 全球首届粉丝节重磅来袭"
    };
  },
  send:function(){
      app.request({
          url:'http://cje.tunnel.qydev.com/we7offical/addons/zjhj_mall/core/web/index.php?store_id=1&r=api/user/test',
          success:function(res){
            console.log(11);
          }
      });
  },
  opensetting:function(e){
    wx.openSetting({
      success:function(res){
        console.log("openSetting ", res);
      }
    })
    /*wx.authorize({
      scope: 'scope.writePhotosAlbum',
      success:function(e) {
        console.log('authorize ', e);
      }
    })*/
  }
})