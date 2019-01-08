// pages/anmeila/anmeila.js
var app = getApp();
var api = require('../../api.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    user_info:null,
    user_level:"普通会员",
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    app.pageOnLoad(this);
    var page = this;
    app.request({
      url: api.user.index,
      success: function (res) {
        if (res.code == 0) {
          console.log("user info", res);
          page.setData({
            user_info:res.data.user_info
          });

          var user_info = res.data.user_info;
          if (user_info.is_distributor == 1) {
            var user_level = user_info.level + "级 分销商";
            page.setData({
              user_level: user_level
            });
          }
        }
      }
    });

    app.request({
      url: api.anmeila.team,
      success: function (res) {
        if (res.code == 0) {
          console.log("team info", res);
          page.setData({
            team: res.data
          });
        }
      }
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
  onShareAppMessage: function () {
    return {
      path: "/pages/anmeila/anmeila",
      imageUrl: "https://api.anmeila.com.cn/statics/images/ssds/wx_share_plateform.png",
      success: function (e) {
      },
      title: "拉手平台-安美拉邀请您激活"
    };
  },
  removebd:function(){
    wx.showModal({
      title: '提示',
      content: '您确定要解除激活吗？',
      success: function (res) {
        if (res.confirm) {
          console.log('用户点击确定')
          //这里处理清空openid的操作
          app.request({
            url: api.act.removebd,
            success: function (res) {
              if (res.code == 0) {
                wx.showToast({
                  title: res.msg,
                  icon: 'none',
                  duration: 2000
                })
                
              }else{
                wx.showToast({
                  title: res.msg,
                  icon: 'none',
                  duration: 2000
                })
              }
              wx.clearStorage();
              try {
                wx.clearStorageSync()
              } catch (e) {
                // Do something when catch error
              }
              wx.redirectTo({
                url: '/pages/login/login',
              })
            }
          });
        } else if (res.cancel) {
          console.log('用户点击取消')
        }
      }
    })
  }
})