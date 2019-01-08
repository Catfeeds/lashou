var app = getApp();
var api = require('../../api.js');
Page({
  /**
   * * TODO：
   * 每行2个
   * 
   * 切换 不同分类 的排行榜
   * 搜索
   * 
   * 是年度、季度、月度？？
   * 
   */

  /**
   * 页面的初始数据
   */
  data: {
    player_list:[],
    page: 1,
    keywords: "",
    vote_type: 0,
    hasmore:false
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    getApp().pageOnLoad(this);
    this.refresh();
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
    console.log("pull down refresh");
    this.refresh();
  },

  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    console.log("reach bottom");
    if (this.data.hasmore){
      this.setData({
        page: this.data.page + 1,
      })
      this.loadData();
    }
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function (options) {
      return getApp().onShareAppMessage(options);
  },

  loadData:function(){
    var page = this;
    console.log("page data", page.data);
    app.request({
      url: api.ssds.index,
      data: { page: page.data.page, vote_type: page.data.vote_type, keywords: page.data.keywords},
      success: function (res) {
        wx.stopPullDownRefresh();
        if (res.code == 0) {
          console.log("api.ssds.index", res);

          var hasMore = res.data.page_count > page.data.page;
          var player_count = typeof res.data.player_count != 'undefined' ? res.data.player_count : 0;
          page.setData({
            player_list: page.data.player_list.concat(res.data.list),
            hasmore:hasMore,
            player_count: player_count,
          });
        } else {
          wx.showToast({
            title: res.msg,
            duration: 2000
          })
        }
      },
    });
  },

  refresh:function(){
    this.setData({
      player_list: [],
      page: 1,
    })
    this.loadData();
  },
  
  vote:function(e){
    console.log("vote click", e);
    var playerUserId = e.currentTarget.dataset.playerUserId;
    var page = this;
    app.request({
      url: api.ssds.vote,
      data: {player_user_id:playerUserId},
      success: function (res) {
        if (res.code == 0) {
          if (typeof res.data.hongbao != "undefined") {
            wx.showToast({
              title: "恭喜您获得" + res.data.hongbao.val + "元 红包",
              duration: 2000
            })
          }
          page.refresh();
        } else {
          wx.showToast({
            title: res.msg,
            duration: 2000
          })
        }
      },
    });
  },
  toggleType:function(e){
    console.log(e);
    var vote_type = e.currentTarget.dataset.voteType;
    this.setData({
      vote_type:vote_type,
      page:1,
      player_list: [],
    })
    this.loadData();
  },
  doSearch:function(e){
    console.log(e.detail.value);
    var keywords = e.detail.value.keywords;
    this.setData({
      page: 1,
      player_list: [],
      keywords:keywords,
    })
    this.loadData();
  }
})