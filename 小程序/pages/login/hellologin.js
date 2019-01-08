var api = require('../../api.js');
var app = getApp();
var util = require('../../utils/utils.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    encryptedData: "",
    errMsg: '',
    iv: '',
    user_info:0
    
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    
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

  onGotUserInfo:function(e){
    var page = this;
    page.onGetUserInfo2(e,2);
  },
  getPhoneNumber: function (e) {
    var page = this;
    console.log(e.detail.errMsg)
    console.log(e.detail.iv)
    console.log(e.detail.encryptedData)
    page.setData({
        errMsg: e.detail.errMsg,
        iv: e.detail.iv,
        encryptedData: e.detail.encryptedData,
        user_info:1
    });
    console.log(page.data.errMsg);
    
  },
  onGetUserInfo2: function (e, first, oldphone='', olduser='') {
    var context = this;
    console.log("onGetUserInfo2", e);
    var pages = getCurrentPages();
    var page = pages[(pages.length - 1)];
    console.log("current pages 1", pages);
    // return;
    wx.showLoading({
      title: "正在登录",
      mask: true,
    });
    wx.login({
      success: function (res) {
        if (res.code) {
          var code = res.code;
          wx.getUserInfo({
            success: function (res) {
              //console.log(res);
              getApp().request({
                url: api.passport.login,
                method: "post",
                data: {
                  code: code,
                  user_info: res.rawData,
                  encrypted_data: res.encryptedData,
                  iv: res.iv,
                  signature: res.signature,
                  first: first,
                  oldphone: oldphone,
                  olduser: olduser,
                },
                success: function (res) {
                  wx.hideLoading();
                  // console.log(code)
                  if (res.code == 0) {
                    var context = getApp();

                    wx.setStorageSync("access_token", res.data.access_token);
                    wx.setStorageSync("user_info", res.data);

                    var parent_id = 0;
                    if (context.currentPage != null){
                      if (typeof context.currentPage.options.user_id != 'undefined' && context.currentPage.options.user_id != null){
                        parent_id = context.currentPage.options.user_id;
                      } else if (typeof context.currentPage.options.scene != 'undefined' && context.currentPage.options.scene != null){
                        parent_id = context.currentPage.options.scene;
                      }
                    }
                    getApp().bindParent({
                      parent_id: parent_id || 0
                    });
                    
                    /*
                    var p = getCurrentPages();
                    console.log("current pages 2", p);
                    var parent_id = 0;
                    if (p[0].options.user_id != undefined) {
                      var parent_id = p[0].options.user_id;
                    }
                    else if (p[0].options.scene != undefined) {
                      var parent_id = p[0].options.scene;
                    }
                    
                    getApp().bindParent({
                      parent_id: parent_id || 0
                    });
                    */

                    if (context.currentPage != null) {
                      var options = context.currentPage.options;
                      options.redirect_bundle = 1;
                      wx.redirectTo({
                        url: "/" + context.currentPage.route + "?" + util.objectToUrlParams(context.currentPage.options),
                        fail: function () {
                          wx.switchTab({
                            url: "/" + context.currentPage.route,
                          });
                        },
                      });
                    } else {
                      wx.navigateTo({
                        url: "/pages/index/index?redirect_bundle=1",
                      })
                    }
                  }
                  else {
                    wx.showToast({
                      title: res.msg
                    });
                  }
                }
              });
            },
            fail: function (res) {
              wx.hideLoading();
              getApp().getauth({
                content: '需要获取您的用户信息授权，请到小程序设置中打开授权',
                cancel: true,
                success: function (e) {
                  if (e) {
                    getApp().login();
                  }
                },
              });
            }
          });
        } else {
          //console.log(res);
        }

      }
    });
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
  }
})