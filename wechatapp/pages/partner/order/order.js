// pages/partner/market/market.js

// pages/share/index.js
var api = require('../../../api.js');
var app = getApp();
var p = 0
var GetList = function (that) {
  wx.showLoading({
    title: '数据加载中',
  })

  that.setData({
    hidden: false
  });
  app.request({
    url: api.partner.myOrders,
    method: 'get',
    data: {
      page: p
    },
    success: function (res) {
      console.log(res);
      wx.stopPullDownRefresh()
      var l = that.data.list
      if (res.code == 0) {
        for (var i = 0; i < res.partnerOrders.length; i++) {
          l.push(res['partnerOrders'][i])
        }
        that.setData({
          list: l,
          hidden: true
        });
        p++;

      } else {

        wx.showToast({
          title: res.msg,
          icon: 'none',
          duration: 2000
        })

      }
      wx.hideLoading();
    }

  });
}


Page({

  /**
   * 页面的初始数据
   */
  data: {
    form: {
      express: '',
      order_id:'',
      express_no: ''
    },
    list: [],
    showModal: false,
  },
  showDialogBtn: function (e) {

    
    this.setData({
      form:{
        order_id: e.target.dataset.order_id,
        index: e.currentTarget.dataset.index
      },
      showModal: true
    })
  },
  /**
   * 弹出框蒙层截断touchmove事件
   */
  preventTouchMove: function () {
  },
  /**
   * 隐藏模态对话框
   */
  hideModal: function () {
    this.setData({
      showModal: false
    });
  },
  /**
   * 对话框取消按钮点击事件
   */
  onCancel: function () {
    this.hideModal();
  },
  /**
   * 对话框确认按钮点击事件e
   */
  formSubmit: function (e) {
    console.log(e);
    wx.showLoading({
      title: '数据加载中',
    })


    var page = this;
    page.data.form = e.detail.value;

    if (page.data.form.express == '' || page.data.form.express == undefined){
      wx.showToast({
        title: "请输入物流公司",
        image: "/images/icon-warning.png",
      });
      return;
    }
        
    if (this.data.form.express_no == '' || this.data.form.express_no == undefined) {
      wx.showToast({
        title: "请输入物流单号",
        image: "/images/icon-warning.png",
      });
      return;
    }


    var index = e.currentTarget.dataset.index;
    var l = page.data.list
    console.log(api.partner.order_grab)
    app.request({
      url: api.partner.order_sendGoods,
      method: 'POST',
      data: {
        order_id: page.data.form.order_id,
        express: page.data.form.express,
        express_no: page.data.form.express_no
      },
      success: function (res) {
        console.log(res);
        if (res.code == 0) {
          l[page.data.form.index]['is_send'] = 1;
          //l[page.data.form.index]['subName'] = '已发货';
          page.setData({
            list: l,
            express:'',
            express_no:''
          })
          wx.showToast({
            title: '已发货',
            duration: 2000
          })
        } else {
          wx.showToast({
            title: res.msg,
            icon: 'none',
          })

        }
        wx.hideLoading();
      }

    });
    this.hideModal();



    
  },
  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    getApp().pageOnLoad(this);
    var page = this;
    var user_info = wx.getStorageSync("user_info");
    if (user_info.is_partner != 1) {
      wx.redirectTo({
        url: '/pages/partner/reg/reg',
      })
    } else {
      GetList(page)
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
    p = 0
  },

  /**
   * 生命周期函数--监听页面卸载
   */
  onUnload: function () {
    p = 0
  },

  /**
   * 页面相关事件处理函数--监听用户下拉动作
   */
  onPullDownRefresh: function () {
    //下拉  
    console.log("下拉");
    p = 0;
    this.setData({
      list: [],
    });
    var that = this
    GetList(that)

  },
  /**
   * 页面上拉触底事件的处理函数
   */
  onReachBottom: function () {
    //上拉  
    console.log("上拉")
    var that = this
    GetList(that)
  },

  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function (options) {
      return getApp().onShareAppMessage(options);
  },
  giveorder: function (e) {
    var page = this;
    var oid = e.currentTarget.dataset.test;
    wx.showModal({
      title: '提示',
      content: '您确定要放弃订单吗？',
      success: function (res) {
        if (res.confirm) {
          console.log('用户点击确定')
          //这里处理清空openid的操作
          app.request({
            url: api.act.giveorder,
            data: {
              order_id: oid,
            },
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
              wx.redirectTo({
                url: '/pages/partner/order/order',
              })
              // $('#but_' + oid).hide()
            }
          });
        } else if (res.cancel) {
          console.log('用户点击取消')
        }
      }
    })
  }
})