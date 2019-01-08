// pages/choujiang/choujiang.js
var api = require('../../api.js');
var MAX_SHARE_COUNT = 2;
Page({

  /**
   * 页面的初始数据
   * 
   * initChoujiang:prize list, prize, left share count, choujiang count
   * 抽奖完后 prize null; choujiang count -1 如果>0 获得下个奖品，真正结束本次抽奖
   * share get more prize:prize, left share count, choujiang count
   * 分享到群后 获得赠送抽奖次数和奖品 可能为空
   * get more prize:prize, left share count, choujiang count
   * 服务器控制奖品发放
   * active prize:prize_id return null;
   * 激活奖品
   * 
   */
  data: {
    max_share_count: MAX_SHARE_COUNT,//常量
    left_share_count:0,
    choujiang_count:0,
    prize_list:[],
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    console.log("times", 1);
    wx.updateShareMenu({
      withShareTicket: true,
    });
    //this.beginChoujiang();
    getApp().pageOnLoad(this);

    var context = this;
    //return;
    getApp().request({
      url: api.choujiang.index,
      data: null,
      success: function (res) {
        if (res.code == 0) {
          context.initChoujiang(res.data);
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
    var user_info = wx.getStorageSync("user_info");
    var context = this;
    return {
      path: "/pages/choujiang/choujiang?user_id=" + user_info.id,
      success: function (e) {
        console.log("share success", e);
        if (typeof e.shareTickets == "undefined") {
          wx.showToast({
            title: '请分享到群',
          })
          return;
        }
        context.shareGetMorePrize();
      },
      imageUrl: "https://api.anmeila.com.cn/statics/images/ssds/wx_share_hongbao.png",
      title: "拉手平台"
    };
  },

  /* 抽奖规则 */ 
  rule:"",
  showRule:function(){

  },
  hideRule:function(){

  },

  /* 获奖近况 */
  setupLatestLuckier:function(){

  },

  /* 超值推荐 */
  setupPromotions:function(promotions){

  },

  /*抽奖： 1.奖品池 及 奖品 */

  /**
   * PRIZE {
      prize_id: 502,
      prize_type: 2, //0.无 1.红包 2.积分
      prize_val: 50,
      image: "https://api.anmeila.com.cn/statics/images/choujiang/choujiang-bg.png"
    }
   * 
   */
  prize:null,
  prize_index:-1,

  /* 抽奖： 2.开始抽奖 */
  beginChoujiang:function(){
    if (this.prize == null || this.prize_index < 0) {
      return;
    }

    var context = this;

    context.effectChoujiang(function(){
      //TODO 激活奖品
      getApp().request({
        url: api.choujiang.active,
        data: {prize_id:context.prize.prize_id},
        success: function (res) {
          if (res.code == 0) {
            //抽奖次数
            context.setData({
              choujiang_count: Math.max(0, context.data.choujiang_count - 1),
            });

            //设置当前奖品
            context.prize = null;
            context.prize_index = -1;

            //进入下一个抽奖做准备
            if (context.data.choujiang_count > 0) {
              //网络请求奖品
              context._getMorePrize(null, function () {
                context.inChoujiang = false;
              });
            } else {
              context.inChoujiang = false;
            }
          } else {
            wx.showToast({
              title: res.msg,
              duration: 2000
            })
          }

        },
      });
    });
  },

  /* 抽奖： 3.抽奖动画效果 已封装 勿修改 */
  //dependices:prize not null, prize_index >= 0; data.prize_list 不为空, inChoujiang
  inChoujiang:false,
  effectChoujiang:function(callback){
    console.log("beginChoujiang");
    var page = this;
    if (this.inChoujiang){
      return;
    }

    this.inChoujiang = true;
    
    var speed = 100;
    var useless_times = 5;
    var prepare_times = 10;
    var roll_times = 0;
    var roll_timer = 0;
    var roll_index = -1;

    roll();
    function roll(){
      roll_index += 1;
      if (roll_index > page.data.prize_list.length - 1) {
        roll_index = 0;
      }

      page.setData({
        roll_index: roll_index
      });

      roll_times += 1;
      if (roll_times > useless_times + prepare_times && roll_index == page.prize_index){
        clearTimeout(roll_timer);
        if(typeof callback != "undefined" && callback != null){
          setTimeout(function(){
            callback();
          },1);
        }
      }else{
        if(roll_times <= useless_times){
          speed -= 10;
        } else if (roll_times > useless_times + prepare_times && (page.prize_index == roll_index + 1 || (page.prize_index == 0 && roll_index == page.data.prize_list.length - 1))){
          speed += 110;
        } else{
          speed += 20;
        }

        speed = Math.max(40, speed);

        roll_timer = setTimeout(roll, speed);
      }

      console.log("speed", speed, "useless_times", useless_times, "prepare_times", prepare_times, "roll_times", roll_times, "roll_index", roll_index, page.inChoujiang ? "inChoujiang" : "not inChoujiang");
    }
  },

  initChoujiang: function (info){
    //TODO 网络请求该次抽奖的信息 {prize_list:[PRIZE], mine:{prize:PRIZE, left_share_count:2, choujiang_count:3}}
    var page = this;

    page.setData({
      prize_list: info.prize_list,
      left_share_count: info.mine.left_share_count,
      choujiang_count: info.mine.choujiang_count,
    });

    //设置我的抽奖信息
    if(typeof info.mine != "undefined" && info.mine != null){
      page.prize = info.mine.prize;
      page._resetPrizeIndex();
    }
  },

  /* 获取更多机会： share */
  shareGetMorePrize:function(){
    var context = this;
    context._getMorePrize({ is_share: true });
  },

  _resetPrizeIndex:function(){
    var page = this;

    if (page.data.prize_list == null || page.prize == null){
      page.prize_index = -1;
    }

    for (var i = 0; i < page.data.prize_list.length; i++) {
      var prize = page.data.prize_list[i];
      if (prize.prize_id == page.prize.prize_id) {
        page.prize_index = i;
        break;
      }
    }
  },

  //服务器获取奖品
  _getMorePrize:function(options, callback){
    var context = this;
    getApp().request({
      url: api.choujiang.more_prize,
      data: options,
      success: function (res) {
        //{prize:PRIZE nullable, left_share_count:2, choujiang_count:3}
        if (res.code == 0) {
          if (typeof res.data.prize != "undefined" && res.data.prize != null && context.prize == null) {
            context.prize = res.data.prize;
            context._resetPrizeIndex();
          }

          context.setData({
            left_share_count: res.data.left_share_count,
            choujiang_count: res.data.choujiang_count,
          });

          if(typeof callback != "undefined" && callback != null){
            callback();
          }
        } else {
          wx.showToast({
            title: res.msg,
            duration: 2000
          })
        }

      },
    });
  }
})