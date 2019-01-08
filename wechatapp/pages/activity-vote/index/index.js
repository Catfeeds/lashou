// pages/add-share/index.js
var api = require('../../../api.js');
var app = getApp();
Page({

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

  formSubmit: function (e) {
    var page = this;
    var user_info = wx.getStorageSync("user_info");
    page.data.form = e.detail.value;
    /*  if (page.data.form.name == undefined || page.data.form.name == '') {
          wx.showToast({
              title: "请填写姓名！",
              image: "/images/icon-warning.png",
          });
          return;
      }
      if (page.data.form.mobile == undefined || page.data.form.mobile == '') {
          wx.showToast({
              title: "请填写联系方式！",
              image: "/images/icon-warning.png",
          });
          return;
      }*/
    var data = e.detail.value;
    data.form_id = e.detail.formId;
    if (page.data.agree == 0) {
      wx.showToast({
        title: "请先阅读并确认分销申请协议！！",
        image: "/images/icon-warning.png",
      });
      return;
    }
    console.log(page.data.agree);
    wx.showLoading({
      title: "正在提交",
      mask: true,
    });

    app.request({
      // url: api.share.join,
      url: api.recharge.submit_share,
      method: 'POST',
      data: data,
      /* success: function (res) {
           if (res.code == 0) {
               user_info.is_distributor = 2;
               wx.setStorageSync(
                   "user_info", user_info
               );
               wx.redirectTo({
                   url: '/pages/add-share/index',
               })
           } else {
               wx.showToast({
                   title: res.msg,
                   image: "/images/icon-warning.png",
               });
           }
       }*/

      success: function (res) {
        if (res.code == 0) {
          setTimeout(function () {
            wx.hideLoading();
          }, 1000);
          wx.requestPayment({
            timeStamp: res.data.timeStamp,
            nonceStr: res.data.nonceStr,
            package: res.data.package,
            signType: res.data.signType,
            paySign: res.data.paySign,
            complete: function (e) {
              if (e.errMsg == "requestPayment:fail" || e.errMsg == "requestPayment:fail cancel") {
                wx.showModal({
                  title: "提示",
                  content: "订单尚未支付",
                  showCancel: false,
                  confirmText: "确认",
                });
                return;
              }
              if (e.errMsg == "requestPayment:ok") {
                wx.showModal({
                  title: "提示",
                  content: "充值成功",
                  showCancel: false,
                  confirmText: "确认",
                  success: function (res) {
                    wx.navigateBack({
                      delta: 1
                    })
                  }
                });
              }
            },
          });
          return;
        } else {
          wx.showModal({
            title: '提示',
            content: res.msg,
            showCancel: false
          });
          wx.hideLoading();
        }
      }





    });
  },
  upVideoSubmit: function (e) {
    var user_info = wx.getStorageSync("user_info");
    var that = this
    wx.chooseVideo({
      sourceType: ['album', 'camera'],
      maxDuration: 60,
      camera: 'back',
      success: function (res) {
        console.log(res.tempFilePath)
        wx.uploadFile({
          url: api.activity.vote.upLoadVideo + '&user_id=' + user_info.id,
          filePath: res.tempFilePath,
          name: 'file',
          formData: {
            'user': 'test'
          },
          success: function (res) {
            var data = res.data
            console.log(data)
            if (data.code == 0){

            //{data: "{"code":0,"msg":"success","data":{"url":"http:\/\/…ideo\/3f\/3f2b588be46e0d21690ee8dbfbd860f8.mp4"}}",                      statusCode: 200, errMsg: "uploadFile:ok"}
            //do something
            }else{
              wx.showToast({
                title: data.msg,
                icon: 'success',
                duration: 1000
              })
            }
          }
        })
      }
    })
    
  },
  onShareAppMessage: function (res) {
    console.log(res);
    if (res.from === 'button') {
      // 来自页面内转发按钮
      
    }
    return {
      title: '自定义转发标题',
      path: '/page/',
      complete: function (res) {
        if (res.errMsg = 'shareAppMessage:ok'){
          wx.vibrateLong()
          wx.showToast({
            title: '分享成功',
            icon: 'success',
            duration: 1000
          })
        }
      }
    }
  }




})