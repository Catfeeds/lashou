// user.js
var api = require('../../api.js');
var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {
        contact_tel: "",
        show_customer_service: 0,
        //user_center_bg: "/images/img-user-bg.png",
        ssds:0
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
        app.pageOnLoad(this);
        this.setData({
          ssds:getApp().ssds == 1,
        });
    },

    loadData: function (options) {
        var page = this;
        page.setData({
            store: wx.getStorageSync('store'),
        });
        var pages_user_user = wx.getStorageSync('pages_user_user');
        if (pages_user_user) {
            page.setData(pages_user_user);
        }
        app.request({
            url: api.user.index,
            success: function (res) {
                if (res.code == 0) {
                    page.setData(res.data);
                    wx.setStorageSync('myData', res.data.mydata);
                    wx.setStorageSync('pages_user_user', res.data);
                    wx.setStorageSync("share_setting", res.data.share_setting);
                    wx.setStorageSync("user_info", res.data.user_info);
                }
             //   console.log(res.data.user_info);
            }
        });
    },

    /**
     * 用户点击右上角分享
     */
    onShareAppMessage: function () {
      var page = this;
      var user_info = wx.getStorageSync("user_info");
      return {
        path: "/pages/index/index?user_id=" + user_info.id,
        imageUrl: "https://api.anmeila.com.cn/statics/images/ssds/wx_share_plateform.png",
        success: function (e) {
        },
        title: "拉手平台"
      };
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
        app.pageOnShow(this);
        var page = this;
        page.loadData();
    },

    callTel: function (e) {
        var tel = e.currentTarget.dataset.tel;
        wx.makePhoneCall({
            phoneNumber: tel, //仅为示例，并非真实的电话号码
        });
    },
    apply: function (e) {
        var page = this;
        var share_setting = wx.getStorageSync("share_setting");
        
        var user_info = wx.getStorageSync("user_info");
        
       // console.log(user_info);
        if (share_setting.share_condition == 1) {
            wx.navigateTo({
                url: '/pages/bundles/bundles',
            })
        } else if (share_setting.share_condition == 0 || share_setting.share_condition == 2) {
            if (user_info.is_distributor == 0) {
                wx.showModal({
                    title: "申请成为分销商",
                    content: "是否申请？",
                    success: function (r) {
                        if (r.confirm) {
                            wx.showLoading({
                                title: "正在加载",
                                mask: true,
                            });
                            app.request({
                                url: api.share.join,
                                method: "POST",
                                data: {
                                    form_id: e.detail.formId
                                },
                                success: function (res) {
                                    if (res.code == 0) {
                                        if (share_setting.share_condition == 0) {
                                            user_info.is_distributor = 2;
                                            wx.navigateTo({
                                                url: '/pages/add-share/index',
                                            })
                                        } else {
                                            user_info.is_distributor = 1;
                                            wx.navigateTo({
                                                url: '/pages/share/index',
                                            })
                                        }
                                        wx.setStorageSync("user_info", user_info);
                                    }
                                },
                                complete: function () {
                                    wx.hideLoading();
                                }
                            });
                        }
                    },
                })
            } else {
                wx.navigateTo({
                    url: '/pages/add-share/index',
                })
            }
        }
    },
    verify: function (e) {
        wx.scanCode({
            onlyFromCamera: false,
            success: function (res) {
            //    console.log(res)
                wx.navigateTo({
                    url: '/' + res.path,
                })
            }, fail: function (e) {
                wx.showToast({
                    title: '失败'
                });
            }
        });
    },
    member: function () {
        wx.navigateTo({
            url: '/pages/member/member',
        })
    }
  ,
    removebd: function () {
      wx.showModal({
        title: '提示',
        content: '该功能只针对原安美拉平移过程中没有正确激活的用户，您确定要注销么【账户一旦注销，您通过支付购买的礼包和星级身份将会丢失】！！',
        success: function (res) {
          if (res.confirm) {
            console.log('用户点击确定')
            //这里处理清空openid的操作
            app.request({
              url: api.act.removebd,
              success: function (res) {
                if (res.code == 0) {
                  wx.showToast({
                    title: res.msg,
                    icon: 'none',
                    duration: 2000
                  })

                } else {
                  wx.showToast({
                    title: res.msg,
                    icon: 'none',
                    duration: 2000
                  })
                }
                wx.clearStorage();
                try {
                  wx.clearStorageSync()
                } catch (e) {
                  // Do something when catch error
                }
                wx.redirectTo({
                  url: '/pages/login/login',
                })
              }
            });
          } else if (res.cancel) {
            console.log('用户点击取消')
          }
        }
      })
    }
});