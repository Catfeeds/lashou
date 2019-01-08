// pages/ls-activity-team/ls-activity-team.js
var api = require('../../api.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    share_status:"",
    list:[],
    tree_titles:[],//nick name
    tree_ids:[]//user id
  },

  cachListDict:null,//{userid0:[], ...}

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {

    this.cachListDict = new Object;

    this.setUserId(0);

    this.setData({
      tree_titles: ['我'],//nick name
      tree_ids: [0]//user id
    });
  },

  setUserId:function(userId){
    var page = this;

    var key = "userid" + userId;
    if (typeof page.cachListDict[key] != 'undefined'){
      page.setData({
        list: page.cachListDict[key], 
      });

      return;
    }

    getApp().request({
      url: api.activity.team,
      data: {user_id:userId},
      success: function (res) {
        console.log("activity team", res);
        page.cachListDict[key] = res.data.list;
        page.setData({
          banner: res.data.banner,
          list: res.data.list,
          share_status: res.data.share_status,
        });
      },
    });
  },

  clickTabItem: function (e) {
    console.log("click tab item", e);
    var user_id = e.currentTarget.dataset.userId;
    var user_name = e.currentTarget.dataset.userName;

    var index = e.currentTarget.dataset.indexNum;

    var page = this;

    var tree_titles = page.data.tree_titles;
    tree_titles.splice(index + 1);    

    var tree_ids = page.data.tree_ids;
    tree_ids.splice(index + 1);

    page.setData({
      tree_titles: tree_titles,//nick name
      tree_ids: tree_ids//user id
    });

    page.setUserId(user_id);
  },

  clickListItem:function(e){
    console.log("click list item", e);
    var user_id = e.currentTarget.dataset.userId;
    var user_name = e.currentTarget.dataset.userName;

    var page = this;

    var tree_titles = page.data.tree_titles;
    tree_titles.push(user_name);

    var tree_ids = page.data.tree_ids;
    tree_ids.push(user_id);

    page.setData({
      tree_titles: tree_titles,//nick name
      tree_ids: tree_ids//user id
    });

    page.setUserId(user_id);
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
  
  }
})