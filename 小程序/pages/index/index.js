var api = require('../../api.js');
var app = getApp();
var share_count = 0;
var width = 260;
var int = 1;
var interval = 0;
var page_first_init = true;
var timer = 1;
var msgHistory = '';
var fullScreen = false;
var ls_hongbao = require('../../lashou/hongbao/hongbao.js');
var ls_ssds = require('../../lashou/ssds/ssds.js');
Page({
    catTabModule:{
      page:null,
      cache_list:{},//key:cat_id
      cat_id:0,
      show_category_tab:false,
      init:function(page){
        this.page = page;
        var context = this;

        context.bindUi();

        page.changeCatTab = function(e){
          console.log(e);

          var catId = e.currentTarget.dataset.catId;
          context.cat_id = catId;

          if(catId > 0){
            context.show_category_tab = true;
            if (typeof context.cache_list[context.cat_id] == 'undefined' || context.cache_list[context.cat_id] == null) {
              app.request({
                url: api.default.goods_list,
                data: {
                  cat_id: catId,
                  page: 1,
                },
                success: function (res) {
                  if (res.code == 0) {
                    context.cache_list[catId] = res.data.list;
                    context.bindUi();
                  }
                },
                complete: function () {
                }
              });
            }
          }else{
            context.show_category_tab = false;
          }

          context.bindUi();
        }
      },
      bindUi:function(){
        var context = this;

        var goods_list = [];
        if (typeof context.cache_list[context.cat_id] != 'undefined' && context.cache_list[context.cat_id] != null){
          goods_list = context.cache_list[context.cat_id];
        }

        console.log('cat tab', {
          show_category_tab: context.show_category_tab,
          cat_id: context.cat_id,
          goods_list: goods_list,
        });

        context.page.setData({
          cat_mod:{
            show_category_tab: context.show_category_tab,
            cat_id: context.cat_id,
            goods_list: goods_list,
          }
        });
      }
    },

    data: {
        x: wx.getSystemInfoSync().windowWidth,
        y: wx.getSystemInfoSync().windowHeight,
        left: 0,
        show_notice: false,
        animationData: {},
        play: -1,
        time: 0,
        buy_user: '',
        buy_address: '',
        buy_time: 0,
        buy_type: '',
        cat_list:[]
    },

    banner_pointer_module:{
      page:null,
      banner_pointer: { index_list: [], current_index: -1 },
      banner_list:[],
      init:function(banner_list, page){
        var context = this;
        context.banner_list = banner_list;
        context.page = page;

        context.page.changePointer = function (e) {
          console.log("change pointer ", e);
          context.banner_pointer.current_index = e.detail.current;
          context.page.setData({
            banner_pointer: context.banner_pointer
          });
        };

        context.banner_pointer.current_index = 0;
        context.banner_pointer.index_list = [];
        for(var i=0; i<banner_list.length; i++){
          context.banner_pointer.index_list.push(i);
        }
        context.page.setData({
          banner_pointer: context.banner_pointer
        });
      }
    },

    
    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
        app.pageOnLoad(this);

        app.track("首页", options);

        this.initQiandao();
        this.catTabModule.init(this);

        wx.updateShareMenu({
          withShareTicket:true,
        });
        console.log("wooranraooran", encodeURIComponent('code=123po'));
        /*wx.redirectTo({
          url: '/pages/ssds-player-join/ssds-player-join?scene=' + encodeURIComponent('code=123po'),
        });*/
        /*wx.redirectTo({
          url: '/pages/ssds-player-join/ssds-player-join?scene=code%3D0HKQ5T',
        })*/
      //wx.setStorageSync("access_token", "");
        //CKzToYfrBeCSZGjnoZHhaWkt2vGFFNGs
        //TclI6NeBLt0ISMTLkXmD_4KEaLyf1xw_ 头像黑
        //QDbSBAn-_lI26Aock9RsisDTGR6LcZf_ hu
        //OVW8OqeTHL-WcqogB6eXSTQc8jeQiPXQ zhang
        if (!app.isLogin()){
          getApp().login();
        }
        else{
          this.loadData(options);
          var page = this;
          var parent_id = 0;
          var user_id = options.user_id;
          var scene = decodeURIComponent(options.scene);
          if (user_id != undefined) {
              parent_id = user_id;
          }
          else if (scene != undefined) {
              parent_id = scene;
          }
          app.loginBindParent({ parent_id: parent_id });

          var cat_list_w = wx.getStorageSync("cat_list");
          console.log("cat list wooran", cat_list_w);
          page.setData({
            cat_list_w: cat_list_w
          });
        }
    },

    /**
     * 购买记录
     */
    suspension: function () {
        var page = this;

        interval = setInterval(function () {
            app.request({
                url: api.default.buy_data,
                data: { 'time': page.data.time },
                method: 'POST',
                success: function (res) {
                    if (res.code == 0) {
                        var inArray = false;

                        if (msgHistory == res.md5) {
                            inArray = true;
                        }
                        var cha_time = '';
                        var s = res.cha_time;
                        var m = Math.floor(s / 60 - Math.floor(s / 3600) * 60);
                        if (m == 0) {
                            cha_time = s % 60 + '秒';
                        } else {
                            cha_time = m + '分' + s % 60 + '秒';
                        };


                        var buy_type = '购买了';
                        var buy_url = '/pages/goods/goods?id=' + res.data.goods;
                        if (res.data.type === 2) {
                            buy_type = '预约了';
                            buy_url = '/pages/book/details/details?id=' + res.data.goods;
                        } else if (res.data.type === 3) {
                            buy_type = '秒杀了';
                            buy_url = '/pages/miaosha/details/details?id=' + res.data.goods;
                        } else if (res.data.type === 4) {
                            buy_type = '拼团了';
                            buy_url = '/pages/pt/details/details?gid=' + res.data.goods;
                        };

                        if (!inArray && res.cha_time <= 300) {
                            page.setData({
                                buy_time: cha_time,
                                buy_type: buy_type,
                                buy_url: buy_url,
                                buy_user: (res.data.user.length >= 5) ? res.data.user.slice(0, 4) + "..." : res.data.user,
                                buy_avatar_url: res.data.avatar_url,
                                buy_address: (res.data.address.length >= 8) ? res.data.address.slice(0, 7) + "..." : res.data.address,
                            });
                            msgHistory = res.md5;
                        } else {
                            page.setData({
                                buy_user: '',
                                buy_type: '',
                                buy_url: buy_url,
                                buy_address: '',
                                buy_avatar_url: '',
                                buy_time: '',
                            });
                        }

                    }
                }
            });
        }, 10000);
    },

    /**
     * 加载页面数据
     */
    loadData: function (options) {
        var page = this;
        var pages_index_index = wx.getStorageSync('pages_index_index');
        if (pages_index_index) {
            pages_index_index.act_modal_list = [];
            page.setData(pages_index_index);
        }
        app.request({
            url: api.default.index,
            success: function (res) {
                if (res.code == 0) {
                    if (!page_first_init) {
                        res.data.act_modal_list = [];
                    } else {
                        page_first_init = false;
                    }
                    var topic_list = res.data.topic_list;
                    var topic_new = new Array();
                    if(topic_list && res.data.update_list.topic.count!=1){
                        if(topic_list.length==1){
                            topic_new[0] = new Array();
                            topic_new[0] = topic_list;
                        }else{
                            for(var i = 0, k = 0;i<topic_list.length;i+=2,k++){
                                if(topic_list[i+1]!=undefined){
                                  topic_new[k] = new Array();
                                  topic_new[k][0]=topic_list[i];
                                  topic_new[k][1]=topic_list[i+1];
                                }
                            };                        
                        };
                        res.data.topic_list = topic_new;
                    };

                    page.setData(res.data);
                    page.banner_pointer_module.init(res.data.banner_list, page);
                    wx.setStorageSync('store', res.data.store);
                    wx.setStorageSync('pages_index_index', res.data);
                    var _user_info = wx.getStorageSync('user_info');
                    if (_user_info) {
                        page.setData({
                            _user_info: _user_info,
                        });
                    }
                    page.miaoshaTimer();

                    page.setupLsActivity(res.data.hongbao, res.data.dasai);

                    page.setupHuanqiu(res.data);

                    page.setupQiandao(res.data);
                }
            },
            complete: function () {
                wx.stopPullDownRefresh();
            }
        });

    },
    /**
     * 生命周期函数--监听页面显示
     */
    onShow: function () {
        app.pageOnShow(this);
        ls_hongbao.init(this, this.closeHongbaoCallback);
        ls_ssds.init(this);
        share_count = 0;
        var store = wx.getStorageSync("store");
        if (store && store.name) {
            wx.setNavigationBarTitle({
                title: store.name,
            });
        }
        if (store.purchase_frame === 1) {
            this.suspension(this.data.time);
        } else {
            this.setData({
                buy_user: '',
            })
        };
        clearInterval(int);
        this.notice();
    },

    /**
     * 页面相关事件处理函数--监听用户下拉动作
     */
    onPullDownRefresh: function () {
        clearInterval(timer);
        this.loadData();
    },

    /**
     * 用户点击右上角分享
     */
    onShareAppMessage: function (options) {
        console.log("share options", options);
        //if ( options.from == "button" && options.target.id == "ls_hongbao_share"){
        if (options.from == "button") {
          //ls_hongbao.setIsShow(false);
          getApp().track("打开红包", null);
          return ls_hongbao.onShareFriend(options);
        }

        var page = this;
        var user_info = wx.getStorageSync("user_info");
        return {
            path: "/pages/index/index?user_id=" + user_info.id,
            imageUrl:"https://api.anmeila.com.cn/statics/images/ssds/wx_share_hongbao.png",
            /*success: function (e) {
                share_count++;
                if (share_count == 1)
                    app.shareSendCoupon(page);
            },*/
            title: "拉手平台 - 红包活动 全新玩法"
        };
    },
    receive: function (e) {
        var page = this;
        var id = e.currentTarget.dataset.index;
        wx.showLoading({
            title: '领取中',
            mask: true,
        })
        if (!page.hideGetCoupon) {
            page.hideGetCoupon = function (e) {
                var url = e.currentTarget.dataset.url || false;
                page.setData({
                    get_coupon_list: null,
                });
                if (url) {
                    wx.navigateTo({
                        url: url,
                    });
                }
            };
        }
        app.request({
            url: api.coupon.receive,
            data: { id: id },
            success: function (res) {
                wx.hideLoading();
                if (res.code == 0) {
                    page.setData({
                        get_coupon_list: res.data.list,
                        coupon_list: res.data.coupon_list
                    });
                } else {
                    wx.showToast({
                        title: res.msg,
                        duration: 2000
                    })
                    page.setData({
                        coupon_list: res.data.coupon_list
                    });
                }
            },
            // complete: function () {
            //   wx.hideLoading();
            // }
        });
    },

    navigatorClick: function (e) {
        var page = this;
        var open_type = e.currentTarget.dataset.open_type;
        var url = e.currentTarget.dataset.url;
        if (open_type != 'wxapp')
            return true;
        //console.log(url);
        url = parseQueryString(url);
        url.path = url.path ? decodeURIComponent(url.path) : "";
        console.log("Open New App");
        wx.navigateToMiniProgram({
            appId: url.appId,
            path: url.path,
            complete: function (e) {
                console.log(e);
            }
        });
        return false;

        function parseQueryString(url) {
            var reg_url = /^[^\?]+\?([\w\W]+)$/,
                reg_para = /([^&=]+)=([\w\W]*?)(&|$|#)/g,
                arr_url = reg_url.exec(url),
                ret = {};
            if (arr_url && arr_url[1]) {
                var str_para = arr_url[1], result;
                while ((result = reg_para.exec(str_para)) != null) {
                    ret[result[1]] = result[2];
                }
            }
            return ret;
        }
    },
    closeCouponBox: function (e) {
        this.setData({
            get_coupon_list: ""
        });
    },

    notice: function () {
        var page = this;
        var notice = page.data.notice;
        if (notice == undefined) {
            return;
        }
        var length = notice.length * 14;
        return;
    },
    miaoshaTimer: function () {
        var page = this;
        if (!page.data.miaosha || !page.data.miaosha.rest_time)
            return;
        timer = setInterval(function () {
            if (page.data.miaosha.rest_time > 0) {
                page.data.miaosha.rest_time = page.data.miaosha.rest_time - 1;
            } else {
                clearInterval(timer);
                return;
            }
            page.data.miaosha.times = page.getTimesBySecond(page.data.miaosha.rest_time);
            page.setData({
                miaosha: page.data.miaosha,
            });
        }, 1000);

    },

    onHide: function () {
        app.pageOnHide(this);
        this.setData({
            play: -1
        });
        clearInterval(int);
        clearInterval(interval);
        console.log('hide')
    },
    onUnload: function () {
        app.pageOnUnload(this);
        this.setData({
            play: -1
        });
        clearInterval(timer);
        clearInterval(int);
        clearInterval(interval);
        console.log('unload')
    },
    showNotice: function () {
        this.setData({
            show_notice: true
        });
    },
    closeNotice: function () {
        this.setData({
            show_notice: false
        });
    },

    getTimesBySecond: function (s) {
        s = parseInt(s);
        if (isNaN(s))
            return {
                h: '00',
                m: '00',
                s: '00',
            };
        var _h = parseInt(s / 3600);
        var _m = parseInt((s % 3600) / 60);
        var _s = s % 60;
        var type = 0;
        if (_h >= 1) {
            _h -= 1;
        }
        return {
            h: _h < 10 ? ('0' + _h) : ('' + _h),
            m: _m < 10 ? ('0' + _m) : ('' + _m),
            s: _s < 10 ? ('0' + _s) : ('' + _s),
        };

    },
    to_dial: function () {
        var contact_tel = this.data.store.contact_tel;
        wx.makePhoneCall({
            phoneNumber: contact_tel
        })
    },

    closeActModal: function () {
        var page = this;
        var act_modal_list = page.data.act_modal_list;
        var show_next = true;
        var next_i;
        for (var i in act_modal_list) {
            var index = parseInt(i);
            if (act_modal_list[index].show) {
                act_modal_list[index].show = false;
                next_i = index + 1;
                if (typeof act_modal_list[next_i] != 'undefined' && show_next) {
                    show_next = false;
                    setTimeout(function () {
                        page.data.act_modal_list[next_i].show = true;
                        page.setData({
                            act_modal_list: page.data.act_modal_list
                        });
                    }, 500);
                }
            }
        }
        page.setData({
            act_modal_list: act_modal_list,
        });
    },
    naveClick: function (e) {
        var page = this;
        app.navigatorClick(e, page);
    },
    play: function (e) {
        this.setData({
            play: e.currentTarget.dataset.index
        });
    },
    onPageScroll: function (e) {
        var page = this;
        if (fullScreen) {
            return;
        }
        if (page.data.play != -1) {
            wx.createSelectorQuery().select('.video').fields({
                rect: true
            }, function (res) {
                console.log('page-scroll')
                console.log(res.top);
                var max = wx.getSystemInfoSync().windowHeight;
                if (res.top <= -200 || res.top >= max - 57) {
                    page.setData({
                        play: -1
                    });
                }
            }).exec();
        }
    },
    fullscreenchange: function (e) {
        if (e.detail.fullScreen) {
            fullScreen = true;
        } else {
            fullScreen = false;
        }
    },

    /**
     *
     * module lashou activity
     * 
     * */
    setupLsActivity: function (hongbao, dasai) {
      console.log("lashou activity", hongbao, dasai);
      //只显示一个 优先显示红包
      if (hongbao != null && typeof hongbao != 'undefined') {
        ls_hongbao.setHongbao(hongbao, true);

        if (dasai != null && typeof dasai != 'undefined') {
          ls_ssds.setDasai(dasai, false);
        }
      } else if (dasai != null && typeof dasai != 'undefined') {
        ls_ssds.setDasai(dasai, true);
      }
    },
    closeHongbaoCallback:function(){
      getApp().track("关闭红包", null);
      var dasai = ls_ssds.getDasai();
      if(dasai != null && typeof dasai != "undefined"){
        ls_ssds.setIsShow(true);
      }
    },
    
    doSearchLsGoods:function(e){
      console.log(e);
      var keywords = e.detail.value.header_keywords;
      wx.navigateTo({
        url: '/pages/search/search?keywords=' + keywords,
      })
    },

    //huanqiu
    setupHuanqiu:function(response){
      console.log("huanqiu", response);

      var huanqiu = {};
      var huanqiu_platform_show = true;
      var huanqiu_hot_show = false;

      huanqiu.platform = {
        list: response.recomment_platform.list,
        title: response.recomment_platform.title,
      };

      huanqiu.hot = {
        list: response.recomment_hot.list,
        title: response.recomment_hot.title,
      };

      this.setData({ huanqiu: huanqiu, huanqiu_platform_show: huanqiu_platform_show, huanqiu_hot_show: huanqiu_hot_show});
    },
    tapHuanqiuTitle:function(e){
      console.log(e);
      var page = this;

      var id = e.currentTarget.id;
      switch(id){
        case "hot":
        {
            var huanqiu_scroll_left = 0;
            var huanqiu_scroll_into = "huanqiu-bd-saperate";

            var huanqiu_platform_show = false;
            var huanqiu_hot_show = true;
            page.setData({ huanqiu_scroll_left: huanqiu_scroll_left, huanqiu_scroll_into: huanqiu_scroll_into, huanqiu_platform_show: huanqiu_platform_show, huanqiu_hot_show: huanqiu_hot_show});
          break;
        }

        case "platform":
          {
            var huanqiu_scroll_left = 0;
            var huanqiu_scroll_into = "";

            var huanqiu_platform_show = true;
            var huanqiu_hot_show = false;
            page.setData({ huanqiu_scroll_left: huanqiu_scroll_left, huanqiu_scroll_into: huanqiu_scroll_into, huanqiu_platform_show: huanqiu_platform_show, huanqiu_hot_show: huanqiu_hot_show});
            break;
          }
      }
    },
    scrollHuanqiu:function(e){
      return;
      var huanqiu_platform_show = true;
      var huanqiu_hot_show = false;

      var res = wx.getSystemInfoSync();
      var ratio = res.pixelRatio;
      var plateform_count = this.data.huanqiu.platform.list.length;

      var show_hot = e.detail.scrollLeft * ratio >= plateform_count * (250+16);
      if (show_hot) {
        huanqiu_platform_show = false;
        huanqiu_hot_show = true;
      } else {
        huanqiu_platform_show = true;
        huanqiu_hot_show = false;
      }
      console.log("scroll", "ratio", ratio, "platform count", plateform_count, show_hot ? "hot" : "not hot", e.detail);
      this.setData({ huanqiu_platform_show: huanqiu_platform_show, huanqiu_hot_show: huanqiu_hot_show });

      /*var huanqiu = this.data.huanqiu;

      var item_width = 250 + 16;
      var saperator_width = 60;

      var plateform_count = this.data.huanqiu.platform.list.length;
      var hot_start = plateform_count * item_width + saperator_width + 60;
      
      var scrollLeft = e.detail.scrollLeft;
      var show_hot = scrollLeft > hot_start;
      if (show_hot){
        huanqiu_platform_show = false;
        huanqiu_hot_show = true;
      } else {
        huanqiu_platform_show = true;
        huanqiu_hot_show = false;
      }

      //this.setData({ huanqiu_platform_show: huanqiu_platform_show, huanqiu_hot_show: huanqiu_hot_show});

      console.log("scroll", "platform count", plateform_count, "hot_start", hot_start, "scrollLeft", scrollLeft, "hot_start", hot_start, show_hot ? "hot" : "not hot", e.detail);*/
    },

    initQiandao:function(){
      /*var device = wx.getSystemInfoSync();
      var width = device.screenWidth;
      var height = device.screenHeight;

      this.setData({
        qiandao_x: width - 50 - 8,
        qiandao_y: height - 50 - 8,
      });*/
    },
    setupQiandao:function(response){
      this.setData({
        show_qiandao:response.can_qiandao,
      });
    },
    tapQiandao:function(e){
      var page = this;
      getApp().track("签到", null);
      app.request({
        url: api.ssds.qiandao_v2,
        data: { form_id: e.detail.formId},
        success: function (res) {
          if (res.code == 0) {
            page.setData({ show_qiandao: false});
            getApp().track("签到奖励", res.data.bonus_list);
            var tips = [];
            var bonus_list = res.data.bonus_list;
            for(var i=0; i<bonus_list.length; i++){
              var bonus = bonus_list[i];
              switch(bonus.type){
                case 1:
                  tips.push(bonus.val  +  "元红包");
                  break;
                case 2:
                  tips.push(bonus.val + "积分");
                  break;
              }
            }

            wx.showModal({
              title: '签到奖励',
              content: '恭喜您获得 ' + tips.join(","),
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
    addFormIdNewerBonus:function(e){
      app.request({
        url: api.form_id.add_newer_bonus,
        data: { form_id: e.detail.formId },
        success: function (res) {
          console.log(res);
        },
      });
    }
});
