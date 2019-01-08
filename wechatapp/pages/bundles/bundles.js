var app = getApp();
var api = require('../../api.js');
var WxParse = require('../../wxParse/wxParse.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    ssds:false,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    getApp().pageOnLoad(this);
    console.log("wooran test", "page load");
    wx.setStorageSync("woroan", "wooran");

    var page = this;

    this.setData({
      ssds: (getApp().ssds == 1),
    });

    app.request({
      url: api.default.article_detail,
      data: {
        id: 'about_us',
      },
      success: function (res) {
        console.log('about us', res);
        if (res.code == 0) {
          WxParse.wxParse("content", "html", res.data.content, page);
        }
        if (res.code == 1) {
          wx.showModal({
            title: "提示",
            content: res.msg,
            showCancel: false,
            confirm: function (e) {
              if (e.confirm) {
                wx.navigateBack();
              }
            }
          });
        }
      }
    });
  },

  /**
   * 生命周期函数--监听页面初次渲染完成
   */
  onReady: function () {
    console.log("wooran test", "page ready");
  },

  /**
   * 生命周期函数--监听页面显示
   */
  onShow: function () {
    console.log("wooran test", "page show");
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

  buyBundle:function(){
    var page = this;
    wx.navigateTo({
      url: '/pages/goods/goods?id=205',
    })
  },

  buy216:function(){
    var page = this;
    wx.navigateTo({
      url: '/pages/goods/goods?id=199',
    })

/*
    app.request({
      url: api.ssds.join,
      data: null,
      success: function (res) {
        if (res.code == 0) {
          wx.navigateTo({
            url: '/pages/ls-toupiao-playercenter/ls-toupiao-playercenter',
          })
        } else {
          wx.showToast({
            title: res.msg,
            duration: 2000
          })
        }
      },
    });

*/


  },
  buy298: function () {
    var page = this;
    wx.navigateTo({
      url: '/pages/add-share/index',
    })
  }

})