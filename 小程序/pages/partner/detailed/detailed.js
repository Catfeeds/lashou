// pages/order-detail/order-detail.js
var api = require('../../../api.js');
var app = getApp();
Page({

  /**
   * 页面的初始数据
   */
  data: {
    order: null,
    form: {
      express: '',
      express_no: ''
    },
    showModal: false,
    getGoodsTotalPrice: function () {
      return this.data.order.total_price;
    },
    express:'',
    index:0
  },
  bindPickerChange: function (e) {
    console.log('picker发送选择改变，携带值为', e.detail.value)
    this.setData({
      index: e.detail.value
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

    if (page.data.form.express == '' || page.data.form.express == undefined) {
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
    //console.log(api.partner.order_grab)
    app.request({
      url: api.partner.order_sendGoods,
      method: 'POST',
      data: {
        order_id: page.data.order_id,
        express: page.data.form.express,
        express_no: page.data.form.express_no
      },
      success: function (res) {
        console.log(res);
        if (res.code == 0) {
          page.setData({
            express: '',
            express_no: '',
            is_send:1
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
  showDialogBtn: function (e) {


    this.setData({
      form: {
        order_id: e.target.dataset.order_id,
        index: e.currentTarget.dataset.index
      },
      showModal: true
    })
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    
    app.pageOnLoad(this);



    var page = this;
    page.setData({
      store: wx.getStorageSync("store"),
      order_id: options.id,
    });
    wx.showLoading({
      title: "正在加载",
    });
   
    app.request({
      url: api.partner.detail,
      data: {
        order_id: options.id,
      },
      success: function (res) {
        console.log(res)
        if (res.code == 0) {
          page.setData({
            order: res.data,
            is_send: res.data.is_send,
            can_send: res.data.can_send,

          });
        }
      },
      complete: function () {
        wx.hideLoading();
      }
    });
    app.request({
      url: api.act.getexpress,
      success: function (res) {
        console.log(res)
        if (res.code == 0) {
          page.setData({
            express:res.data.list,
          });
        }
      },
      complete: function () {
        wx.hideLoading();
      }
    });
  },

  copyText: function (e) {
    var page = this;
    var text = e.currentTarget.dataset.text;
    wx.setClipboardData({
      data: text,
      success: function () {
        wx.showToast({
          title: "已复制"
        });
      }
    });
  },
  location: function () {
    var page = this;
    var shop = page.data.order.shop;
    wx.openLocation({
      latitude: parseFloat(shop.latitude),
      longitude: parseFloat(shop.longitude),
      address: shop.address,
      name: shop.name
    })
  },

  sendLashou:function(e){
    var page = this;
    var orderId = e.target.dataset.order_id;

    wx.showModal({
      title: '提示',
      content: '确定由平台代发吗？',
      success(res) {
        if (res.confirm) {
          console.log('用户点击确定')
          app.request({
            url: api.partner.change_lashou_sendgoods,
            data: {
              order_id: orderId,
            },
            success: function (res) {
              console.log(res)
              if (res.code == 0) {
                page.setData({
                  can_send: false,
                });
              }
            },
            complete: function () {
              wx.hideLoading();
            }
          });
        } else if (res.cancel) {
          console.log('用户点击取消')
        }
      }
    })
  }
});