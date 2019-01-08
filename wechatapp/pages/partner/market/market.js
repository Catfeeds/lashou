// pages/partner/market/market.js

// pages/share/index.js
var api = require('../../../api.js');
var app = getApp();
var p = 0
var GetList = function (that) {
  wx.showLoading({
    title: '数据加载中',
  })

  that.setData({
    hidden: false
  });
  app.request({
    url: api.partner.market,
    method: 'GET',
    data: {
      page: p
    },
    success: function (res) {
      console.log(res)
      //wx.stopPullDownRefresh()
      var l = that.data.list
     if(res.code == 0){
      for (var i = 0; i < res.partnerOrders.length; i++) {
        l.push(res['partnerOrders'][i])
      }
      that.setData({
         list:l,
         hidden: true
      });
      p++;
      
     } else {

       wx.showToast({
         title: res.msg,
         icon: 'none',
         duration: 2000
       })

     }
     wx.hideLoading();
    }
  
  });
}


Page({

  /**
   * 页面的初始数据
   */
  data: {
    list: [] 
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    getApp().pageOnLoad(this);
  
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
    }else{
      GetList(page)  
    }
  },

  /**
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    p = 0
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
    p = 0
  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {
    //下拉  
    console.log("下拉");
    p = 0;
    this.setData({
      list: [],
    });
    var that = this
    GetList(that)

  },
  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    //上拉  
    console.log("上拉")
    var that = this
    GetList(that)
  },
  orderConfirm : function(e){
    wx.showLoading({
      title: '数据加载中',
    })

    var page = this;

    var index = e.currentTarget.dataset.index;
    var l = page.data.list
    console.log(api.partner.order_grab)
    app.request({
      url: api.partner.order_grab,
      method: 'POST',
      data: {
        order_id: e.currentTarget.dataset.order_id
      },
      success: function (res) {
        console.log(res);
        if (res.code == 0) {
          l[index]['is_delete'] = 1;
          l[index]['subName'] = '抢单成功';
          page.setData({
            list: l
          })
          wx.showToast({
            title: '抢单成功',
            duration: 2000
          })

        } else {
          wx.showToast({
            title: res.msg,
            icon: 'none',
          })

        }
        wx.hideLoading();
      }

    });

















  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function (options) {
      return getApp().onShareAppMessage(options);
  }
})