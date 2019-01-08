
var api = require('../../api.js');
var app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    form: {
      phone_number: null,
      uid: '',
      pwd: '',
    },
    phone:'',
    mycode:'',
    isold:1,
    items: [
      { name: '1', value: '手机号', checked: 'true' },
      { name: '2', value: '账号密码' },
    ],
    index: 0,
    index: 0,
    countdown:60,
    is_delete:0,
    mycodestr:'获取验证码',
    user:'',
    encryptuser:'',//加密用户信息
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
  
  },
  radioChange: function (e) {
    console.log('radio发生change事件，携带value值为：', e.detail.value);
    var page = this;
    if (e.detail.value == 2) {
      page.setData({
        index: 1
      })
    } else {
      page.setData({
        index: 0
      })
    }
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
  
  },

  getPhoneNumber: function (e) {
    var page = this;
    console.log(e.detail.errMsg)
    console.log(e.detail.iv)
    console.log(e.detail.encryptedData)

  
  },
  bindPickerChange: function (e) {
    console.log('picker发送选择改变，携带值为', e.detail.value)
    this.setData({
      index: e.detail.value
    })
  },
  getcode:function(e){
    console.log(this.data.phone);
    if(this.isphone2(this.data.phone)){

    }else{
      wx.showToast({
        title: '手机号码有误',
        icon: 'none',
        duration: 2000
      })
      return;
    }
    this.settime();
    var phone = this.data.phone;
    if (phone!=''){
      var mycode = parseInt(Math.random() * (9999 - 1000 + 1) + 1000);
      wx.setStorage({
        key: "mycode_"+phone,
        data: mycode
      })
      //发送短信
      app.request({
        url: api.act.sendsms,
        method: 'POST',
        data: {
          phone: phone,
          mycode: mycode

        },
        success: function (res) {
          console.log(res.data)
          var smsstr = res.data.user;
          var arr = smsstr.split(',');
          if (arr[0]==0){
            wx.showToast({
              title: '信息发送成功',
              icon: 'success',
              duration: 2000
            })
          }else{
            wx.showToast({
              title: '信息发送失败' + smsstr,
              icon: 'success',
              duration: 2000
            })
          }

        }
      })
    }
  },
  phone_input:function(e){
    console.log(e.detail.value);
    this.setData({
      phone: e.detail.value,
    })
  },
  mycode_input: function (e) {
    console.log(e.detail.value);
    this.setData({
      mycode: e.detail.value,
    })
  },
  formSubmit:function(e){
      console.log(e);
      var page = this;
      if(page.data.index==1){
        var user = e.detail.value.user;
        var password = e.detail.value.password;
        console.log(user);
        page.setData({
          user: user,
        });
        app.request({
          url: api.act.getuserinfo,
          method: 'POST',
          data: {
            user: user,
            password: password
          },
          success: function (res) {
            if (res.code == 1) {
              //未找到用户
              wx.showToast({
                title: res.msg,
                icon: 'none',
                duration: 2000
              })
            } else {
              //找到用户
              console.log(res.data.user);
              var isuser = JSON.parse(res.data.user);
              console.log(isuser);
              if (isuser.result==1){
                //设置加密user
                page.setData({
                  encryptuser: isuser.uid,
                });
                console.log(page.data.encryptuser);
                wx.showToast({
                  title: '验证通过',
                  icon: 'success',
                  duration: 500
                })
                //获取信息并且注册
                wx.showModal({
                  title: '提示',
                  content: '拉手想要获取您的微信昵称',
                  success: function (res) {
                    if (res.confirm) {
                      page.onGotUserInfo();
                    } else if (res.cancel) {
                      console.log('用户点击取消')
                    }
                  }
                })
              }else{
                wx.showToast({
                  title: '验证失败',
                  icon: 'none',
                  duration: 2000
                })
              }
              
            }
          }
        })
      }else{
        wx.getStorage({
          key: 'mycode_' + page.data.phone,
          success: function (res) {
            console.log(res.data)
            if (page.data.mycode == res.data){
                //验证码对，请求数据
              wx.showModal({ 
                title: '提示',
                content: '拉手想要获取您的微信昵称', 
                success: function (res) {
                  if (res.confirm) {
                    page.onGotUserInfo();
                  } else if (res.cancel) {
                    console.log('用户点击取消')
                  }
                }
              })
              
            }else{
              //验证码输入有误
              wx.showToast({
                title: '验证码输入有误',
                icon: 'none',
                duration: 2000
              })
            }
          },
          fail:function(){
            //验证码输入有误
            wx.showToast({
              title: '获取验证码失败',
              icon: 'none',
              duration: 2000
            })
          }
        })

      }
      //console.log(wx.getStorage('mycode_'+this.data.phone));
  },
  //验证手机号
  getphoneinfo:function(){
    var page = this;
    //获取手机号对应的信息
    var phone = page.data.phone;
    if (this.isphone2(this.data.phone)) {

    } else {
      wx.showToast({
        title: '手机号码有误',
        icon: 'none',
        duration: 2000
      })
      return;
    }
    app.request({
      url: api.act.getphoneinfo,
      method: 'POST',
      data: {
        phone: phone
      },
      success: function (res) {
        if(res.code==1){ 
          //未找到用户
          wx.showToast({ 
            title: res.msg,
            icon: 'none',
            duration: 2000
          })
        }else{
          //找到用户
          wx.showToast({
            title: '验证通过',
            icon: 'success',
            duration: 2000
          })
          page.setData({
            index2:1,
          })
        }
        console.log(res.data)
      }
    })
  },
  onGotUserInfo: function (e) {
    var page = this;
    console.log(page.data.phone);
    console.log(page.data.encryptuser);
    page.onGetUserInfo2(e, 2, page.data.phone, page.data.encryptuser);
  },
  isphone:function(){
    var page = this;
    if(page.data.index==0){
      page.setData({
        index:1
      })
    }else{
      page.setData({
        index: 0
      })
    }
  },
  settime:function () {
    var page = this;
    if (page.data.countdown == 0) {
      page.setData({
        countdown:60,
        is_delete:0,
        mycodestr: "获取验证码"
      });
      return;
    } else {
      page.setData({
        countdown: page.data.countdown-1,
        is_delete:1,
        mycodestr: "重新发送" + page.data.countdown + ""
      });
      
    }
    console.log(page.data.countdown);
    setTimeout(function() {
      page.settime()
    }
    , 1000)
},
  /*判断输入是否为合法的手机号码*/
   isphone2:function(inputString)
     {
    var partten = /^1[3,7,5,8]\d{9}$/;
    var fl= false;
    if(partten.test(inputString)) {
      //alert('是手机号码');
      return true;
    }
          else
          {
      return false;
      //alert('不是手机号码');
    }
  },
  notsms:function(){
    wx.showToast({
      title: '收不到短信或者验证码错，请更换验证方式激活',
      icon: 'none',
      duration: 3000
    })
      this.setData({
        index: 1,
        items: [
          { name: '1', value: '手机号'},
          { name: '2', value: '账号密码', checked: 'true' },
        ],
      })
  },
  notpws:function(){
    wx.showToast({
      title: '忘记密码，请更换验证方式激活',
      icon: 'none',
      duration: 3000
    })
    this.setData({
      index:0,
      items: [
        { name: '1', value: '手机号', checked: 'true' },
        { name: '2', value: '账号密码'},
      ],
    })
  },
  onGetUserInfo2: function (e, first, oldphone, olduser) {
    var context = this;
    console.log("onGetUserInfo2", e);
    var pages = getCurrentPages();
    var page = pages[(pages.length - 1)];
    console.log("current pages 1", pages);
    // return;
    wx.showLoading({
      title: "正在登录",
      mask: true,
    });
    wx.login({
      success: function (res) {
        if (res.code) {
          var code = res.code;
          wx.getUserInfo({
            success: function (res) {
              console.log(res);
              getApp().request({
                url: api.passport.login,
                method: "post",
                data: {
                  code: code,
                  user_info: res.rawData,
                  encrypted_data: res.encryptedData,
                  iv: res.iv,
                  signature: res.signature,
                  first: first,
                  oldphone: oldphone,
                  olduser: olduser,
                },
                success: function (res) {
                  wx.hideLoading();
                  // console.log(code)
                  if (res.code == 0) {
                    wx.setStorageSync("access_token", res.data.access_token);
                    wx.setStorageSync("user_info", res.data);
                    // console.log(res);
                    // var parent_id = wx.getStorageSync("parent_id");
                    var p = getCurrentPages();
                    console.log("current pages 2", p);
                    var parent_id = 0;
                    if (p[0].options.user_id != undefined) {
                      var parent_id = p[0].options.user_id;
                    }
                    else if (p[0].options.scene != undefined) {
                      var parent_id = p[0].options.scene;
                    }
                    // console.log(parent_id, p[0].options.scene, p[0].options.user_id);
                    getApp().bindParent({
                      parent_id: parent_id || 0
                    });

                    if (page == undefined) {
                      console.log("after login page 1 ", page);
                      return;

                    }
                    console.log("after login page 2", page);
                    
                    if (context.currentPage != null) {
                      wx.redirectTo({
                        url: "/" + context.currentPage.route + "?" + util.objectToUrlParams(context.currentPage.options),
                        fail: function () {
                          wx.switchTab({
                            url: "/" + context.currentPage.route,
                          });
                        },
                      });
                    } else {
                      wx.navigateTo({
                        url: "/pages/index/index",
                      })
                     
                    }
                  } else if (res.code == -200) {
                    wx.showToast({
                      title: res.msg,
                      icon: 'none',
                      duration: 2000
                    });
                    //跳转到激活页面
                    wx.navigateTo({
                      url: '/pages/login/hellologin',
                    })
                  }
                  else {
                    wx.showToast({
                      title: res.msg,
                      icon: 'none',
                      duration: 2000
                    });
                  }
                }
              });
            },
            fail: function (res) {
              wx.hideLoading();
              getApp().getauth({
                content: '需要获取您的用户信息授权，请到小程序设置中打开授权',
                cancel: true,
                success: function (e) {
                  if (e) {
                    getApp().login();
                  }
                },
              });
            }
          });
        } else {
          //console.log(res);
        }

      }
    });
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function () {
    return {
      path: "/pages/anmeila/anmeila",
      imageUrl: "https://api.anmeila.com.cn/statics/images/ssds/wx_share_plateform.png",
      success: function (e) {
      },
      title: "拉手平台-安美拉邀请您激活"
    };
  }
})