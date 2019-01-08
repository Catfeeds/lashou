var api = require('../../api.js');
var app = getApp();
Page({
  /**
   * 围观那里不要取手机号 投票时获取
   */
  
  /**
   * 页面的初始数据
   */
  data: {
    
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    getApp().pageOnLoad(this);
    console.log("toupiao join load");
    var page = this;
    app.request({
      url: api.ssds.info,
      data: null,
      success: function (res) {
        if (res.code == 0) {
          var pageData = { canJoin: true, has_viewer_mobile:false};

          if (res.data.player_info != null && typeof res.data.player_info != "undefined" && res.data.player_info.username != null && typeof res.data.player_info.username != "undefined") {
            pageData.canJoin = false;
          }

          if (res.data.has_viewer_mobile != null && typeof res.data.has_viewer_mobile != "undefined") {
            pageData.has_viewer_mobile = res.data.has_viewer_mobile;
          }

          page.setData(pageData);
        } else {

        }
      },
    });
  },
  
  getPhoneNumber: function (e) {
    var context = this;
    var params = { iv: e.detail.iv, encryptedData: e.detail.encryptedData };
    wx.showLoading({
      title: '加载中',
    });
    console.log(params)
    app.request({
      url: api.ssds.getPhoneNumber,
      method: "POST",
      data: params,
      success: function (res) {
        if(res.code == 0){
          context.setData({ has_viewer_mobile:true});
          wx.navigateTo({
            url: '/pages/ls-toupiao-list/ls-toupiao-list',
          })
        }else{
          wx.showToast({
            title: res.msg,
            icon: 'none'
          })
        }
        wx.hideLoading();
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
  onShareAppMessage: function (options) {
    var user_info = wx.getStorageSync("user_info");
    return {
      path: "/pages/index/index?user_id=" + user_info.id,
      success: function (e) {
        console.log("share hongbao success", e);
        wx.navigateTo({
          url: '/pages/ls-hongbao-shared/ls-hongbao-shared',
        })
      },
      imageUrl: "https://api.anmeila.com.cn/statics/images/ssds/wx_share_ssds.png?v=0525",
      title: "拉手平台"
    }
  },

  joinSsds:function(){
    wx.redirectTo({
      url: '/pages/bundles/bundles',
    })
  },

  gotoPlayerCenter:function(){
    wx.redirectTo({
      url: '/pages/ls-toupiao-playercenter/ls-toupiao-playercenter',
    })
  }
})