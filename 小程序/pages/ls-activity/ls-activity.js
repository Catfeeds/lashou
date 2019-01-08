// pages/ls-activity/ls-activity.js
var api = require('../../api.js');
Page({

  /**
   * 页面的初始数据
   */
  data: {
    start_left_h:"00",
    start_left_i:"00",
    start_left_s:"00",
    left_label:'距开始',

    join_val: ["0", "0", "0", "0", "0", "0", "0"],

    share_val: ["0", "0", "0", "0", "0", "0", "0"],

    handle_title:"拼团即将开始...",
    handle_disabled:true,
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    getApp().pageOnLoad(this);
    console.log("la activity options 1 is ", options);
    getApp().track("粉丝狂欢节页", options);
    console.log("la activity options 2 is ", options);

    var share_user_id = 0;

    //记录分享点击
    if ((typeof options.user_id != 'undefined' && options.user_id != null) ){
      console.log("share user id", options.user_id);
      share_user_id = options.user_id;
      getApp().request({
        url: api.activity.clickShare,
        data: { share_user_id: options.user_id},
        success: function (res) {
          console.log("点击分享的活动页", res);
        },
      });
    }

    getApp().request({
      url: api.activity.add,
      data: { share_user_id: share_user_id },
      success: function (res) {
        console.log("add activity", res);
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
    var page = this;

    getApp().request({
      url: api.activity.info,
      data: null,
      success: function (res) {
        if (res.code == 0) {
          page.setStatus(res.data.status);

          page.startLeftTime = res.data.start_left_time;
          page.endLeftTime = res.data.end_left_time;

          page.pintuan_id = res.data.pintuan_id;

          page.timer = setInterval(function () {
            page.onTimer();
          }, 1000);
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
   * 生命周期函数--监听页面隐藏
   */
  onHide: function () {
    if (this.timer != null){
      clearInterval(this.timer);
    }
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
    getApp().request({
      url: api.activity.share,
      data: null,
      success: function (res) {
        console.log("用户分享活动页", res);
      },
    });
    
    var user_info = wx.getStorageSync("user_info");
    var context = this;
    return {
      path: "/pages/ls-activity/ls-activity?user_id=" + user_info.id + "&from=activity",
      imageUrl: "https://api.anmeila.com.cn/statics/images/activity/cover-share.jpg",
      title: "拉手平台 - 全球首届粉丝节重磅来袭"
    };
  },
  pintuan_id:0,
  clickPintuan:function(){
    console.log("clickPintuan");
    var page = this;
    wx.navigateTo({
      url: '/pages/pt/details/details?gid=' + page.pintuan_id + '&from=activity',
    })
  },

  /**
   * module page timer
   */
  timer:null,
  onTimer:function(){
    var page = this;
    if (page.status == 2){
      clearInterval(page.timer);
      return;
    }

    if (page.status == 0){
      page.startLeftTime--;
      page.setStartLeftTime();
      console.log("left time " + page.startLeftTime);
      if (page.startLeftTime <= 0) {
        page.setStatus(1);
      }
    }

    page.endLeftTime--;
    if(page.status == 1){
      page.setEndLeftTime();
      page.refreshPerson();
      if(page.endLeftTime <= 0){
        page.setStatus(2);
      }
    }

  },
  
  

  status: 0,
  setStatus:function(status){
    var page = this;
    page.status = status;
    console.log("set status " + status);
    switch(status){
      case 0:
        page.setData({
          handle_disabled: true,
          handle_title: "拼团即将开始..."
        });
        
        break;

      case 1:
        this.setData({
          handle_disabled:false,
          handle_title:"立即拼团"
        });

        break;
      case 2:
        this.setData({
          left_label: "已结束",
          handle_disabled: true,
          handle_title: "拼团已经结束"
        });
        break;
    }
  },

  /**
   * module status 0
   */
  startLeftTime: 10,//3600 * 24 *2 + 3600 * 5 + 60 * 20 + 52
  setStartLeftTime: function () {
    var h = "00";
    var i = "00";
    var s = "00";

    if (this.startLeftTime > 0) {
      //var days = parseInt(this.startLeftTime / 60 / 60 / 24, 10); //计算剩余的天数 
      var hours = parseInt(this.startLeftTime / 60 / 60, 10); //计算剩余的小时 
      var minutes = parseInt(this.startLeftTime / 60 % 60, 10);//计算剩余的分钟 
      var seconds = parseInt(this.startLeftTime % 60, 10);//计算剩余的秒数 
      //days = checkTime(days);
      h = checkTime(hours);
      i = checkTime(minutes);
      s = checkTime(seconds);

      console.log("start left time " + this.startLeftTime + ": " + h + " " + i + " " + s);
    }

    this.setData({
      start_left_h: h,
      start_left_i: i,
      start_left_s: s,
      left_label: "距开始"
    });

    function checkTime(i) { //将0-9的数字前面加上0，例1变为01 
      if (i < 10) {
        i = "0" + i;
      }
      return i;
    }
  },
  
  /**
   * module status 1
   */
  endLeftTime:20,
  setEndLeftTime: function () {
    var h = "00";
    var i = "00";
    var s = "00";

    if (this.endLeftTime > 0) {
      //var days = parseInt(this.endLeftTime / 60 / 60 / 24, 10); //计算剩余的天数
      var hours = parseInt(this.endLeftTime / 60 / 60, 10); //计算剩余的小时 
      var minutes = parseInt(this.endLeftTime / 60 % 60, 10);//计算剩余的分钟 
      var seconds = parseInt(this.endLeftTime % 60, 10);//计算剩余的秒数 
      //days = checkTime(days);
      h = checkTime(hours);
      i = checkTime(minutes);
      s = checkTime(seconds);

      console.log("end left time " + this.endLeftTime + ": " + h + " " + i + " " + s);
    }

    this.setData({
      start_left_h: h,
      start_left_i: i,
      start_left_s: s,
      left_label:"距结束"
    });

    function checkTime(i) { //将0-9的数字前面加上0，例1变为01 
      if (i < 10) {
        i = "0" + i;
      }
      return i;
    }
  },
  /**
   * module person
   */
  refreshPerson:function(){
    
    var page = this;
    getApp().request({
      url: api.activity.status,
      data: null,
      success: function (res) {
        if (res.code == 0) {
          console.log("refresh person " + res.data.join_person + " " + page.getArr(res.data.join_person));
          page.setData({
            join_val: page.getArr(res.data.join_person),
            share_val: page.getArr(res.data.share_person),
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

  getArr: function (num) {
    num = parseInt(num);
    var formatNum = 10000000 + num;
    formatNum = formatNum + "";

    var result = [];
    
    result.push(formatNum.substr(1, 1));
    result.push(formatNum.substr(2, 1));
    result.push(formatNum.substr(3, 1));
    result.push(formatNum.substr(4, 1));
    result.push(formatNum.substr(5, 1));
    result.push(formatNum.substr(6, 1));
    result.push(formatNum.substr(7, 1));

    return result;
  },
})