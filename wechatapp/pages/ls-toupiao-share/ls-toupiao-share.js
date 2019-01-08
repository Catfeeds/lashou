var app = getApp();
var api = require('../../api.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    user_id:0,
    link_url: '/pages/bundles/bundles',
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    getApp().pageOnLoad(this);
    this.data.user_id = options.user_id;
    if (options.user_id == undefined){
       var scene = decodeURIComponent(options.scene);
        options.user_id = scene
        options.from_type = 3
        this.data.user_id = scene
    }
    
    //this.data.user_id = 471;
    wx.showToast({
      title: 'uid' + this.data.user_id,
    })

    if (options.user_id){

      app.request({
        url: api.ssds.share_from,
        data: { from_id: options.user_id, from_type: options.from_type },
        method: 'POST',
        success: function (res) {
         
        },
      });







    }

    var page = this;
    app.request({
      url: api.ssds.info,
      data: {player_user_id:page.data.user_id},
      success: function (res) {
        if (res.code == 0) {
          page.setData({
            player_info: res.data.player_info,
            player_count: res.data.player_count,
          });

          if (res.data.current_player_info != null){
            page.setData({
              link_url: '/pages/ls-toupiao-playercenter/ls-toupiao-playercenter',
            });
          }
        } else {
          wx.showToast({
            title: res.msg,
            duration: 2000
          })
        }
      },
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

  vote:function(){
    var page = this;
    app.request({
      url: api.ssds.vote,
      data: { player_user_id:page.data.user_id},
      success: function (res) {
        if (res.code == 0) {
          if(typeof res.data.hongbao != "undefined"){
            wx.showToast({
              title: "恭喜您获得" + res.data.hongbao.val + "元 红包",
              duration: 2000
            })
          }
        } else {
          wx.showToast({
            title: res.msg,
            duration: 2000
          })
        }
      },
    });
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    var page = this;
    var res = {
      path: "/pages/ls-toupiao-share/ls-toupiao-share?user_id=" + page.data.user_id,
      success: function (e) {
      },
      title: "我正在参加瘦身大赛赢得3000万奖金，快来帮我投一票！"
    };

    if (typeof page.data.player_info != "undefined" && page.data.player_info != null && typeof page.data.player_info.img != "undefined" && page.data.player_info.img != null && page.data.player_info.img.length > 0){
      console.log("share center has img", page.data.player_info);
      res.imageUrl = page.data.player_info.img[0];
    }else{
      console.log("share center not has img");
      res.imageUrl = "https://api.anmeila.com.cn/statics/images/ssds/wx_share_ssds.png?v=0525";
    }

    return res;
  },
  goIndex:function(e){
    wx.redirectTo({
      url: '/pages/index/index',
    })
  }
})