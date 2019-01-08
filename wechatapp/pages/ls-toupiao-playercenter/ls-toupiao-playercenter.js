var app = getApp();
var api = require('../../api.js');
var sourceType = [['camera'], ['album'], ['camera', 'album']]
var sizeType = [['compressed'], ['original'], ['compressed', 'original']]
var user_info = wx.getStorageSync("user_info");
Page({

  /**
   * 页面的初始数据
   */
  data: {
    showModal: false,
    
    sourceTypeIndex: 2,
    sourceType: ['拍照', '相册', '拍照或相册'],

    sizeTypeIndex: 2,
    sizeType: ['压缩', '原图', '压缩或原图'],
    form:{
      name:'',
      imageList: [],
      xuanyan:''
    },
    countIndex: 8,
    count: [1, 2, 3, 4, 5, 6, 7, 8, 9],
    items: [
      { name: '1', value: '图一', checked: 'true'  },
      { name: '2', value: '图二'},
      { name: '3', value: '图三' },
    ]
  },
  chooseImage: function () {
    var that = this
    var user_info = wx.getStorageSync("user_info");
    wx.chooseImage({
      sourceType: sourceType[this.data.sourceTypeIndex],
      sizeType: sizeType[this.data.sizeTypeIndex],
      count: this.data.count[this.data.countIndex],
      success: function (res) {
        //console.log(res)
        var tempFilePaths = res.tempFilePaths
        wx.showLoading({
          title: '正在上传...',
        })
        wx.uploadFile({
          url: api.activity.vote.upLoadImage + '&user_id=' + user_info.id, //仅为示例，非真实的接口地址
          filePath: tempFilePaths[0],
          name: 'file',
          formData: {},
          success: function (res) {
            wx.showToast({
              title: '上传成功',
              icon: 'none',
            })
          }
        })

        that.setData({
          form: {imageList: res.tempFilePaths}
        })
      }
    })
  },
  previewImage: function (e) {
    var current = e.target.dataset.src

    wx.previewImage({
      current: current,
      urls: this.data.form.imageList
    })
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


  sourceTypeChange: function (e) {
    this.setData({
      sourceTypeIndex: e.detail.value
    })
  },
  sizeTypeChange: function (e) {
    this.setData({
      sizeTypeIndex: e.detail.value
    })
  },
  countChange: function (e) {
    this.setData({
      countIndex: e.detail.value
    })
  },

  /**
   * 生命周期函数--监听页面加载
   */
  onLoad: function (options) {
    wx.hideShareMenu()
    getApp().pageOnLoad(this);
    var page = this;
    app.request({
      url: api.ssds.info,
      data: null,
      success: function (res) {
        if (res.code == 0) {
          //console.log(res.data.player_info.img);
          var pageData = {};
          if (res.data.player_info != null && typeof res.data.player_info != "undefined"){
            pageData.player_info = res.data.player_info;
            pageData.form = { imageList: res.data.player_info.img };
          }
          if (res.data.user_info) {
            pageData.user_info = res.data.user_info;
          }
          page.setData(pageData);
        //  console.log( res)
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

  editSubmit:function(e){

    this.setData({
      showModal: true
    })


  },
  upVideoSubmit: function (e) {
    var user_info = wx.getStorageSync("user_info");
    var that = this

    wx.navigateTo({
      url: "/pages/ls-ssds-scsp/index?user_id=" + user_info.id + "",
    })
    return;
    wx.chooseVideo({
      sourceType: ['album', 'camera'],
      maxDuration: 60,
      camera: 'back',
      success: function (res) {
        wx.showLoading({
          title: '正在上傳...',
        })
        wx.uploadFile({
          url: api.activity.vote.upLoadVideo + '&user_id=' + user_info.id,
          filePath: res.tempFilePath,
          name: 'file',
          formData: {
            'user': 'test'
          },
          success: function (res) {
           // console.log(res)
            var data = JSON.parse(res.data)
            if (data.code == 0) {
              wx.showToast({
                title: '上傳成功',
                icon: 'success',
                duration: 2000
              });
              this.setData({
                showModal: false
              })
            } else {
              wx.showToast({
                title: data.msg,
                icon: 'none',
                duration: 2000
              })
            }
            wx.hideLoading();
          }
          
        })
      }
    })
   

  },

  formSubmit: function (e) {
    var page = this;
   
    page.data.form = e.detail.value;
   // console.log(page.data.form);

    app.request({
      url: api.activity.vote.edit, //仅为示例，非真实的接口地址
      method: 'POST',
      //data: { phone: data.phone_number},
      data: {
        declaration: page.data.form.xuanyan,
        name: page.data.form.name
      },
      success: function (res) {
        if(res){
          wx.showToast({
            title: '修改成功',
            icon: 'success',
            duration: 2000
          });
        }else{
          wx.showToast({
            title: '修改失敗',
            icon: 'none',
            duration: 2000
          });
        }
        wx.hideLoading();
        page.hideModal();
      }
    });

    

  },



  /**
   * 用户点击右上角分享
   */
  onShareAppMessage: function (res) {
    /*var page = this;
    var user_info = wx.getStorageSync("user_info");
    return {
      path: "/pages/ls-toupiao-share/ls-toupiao-share?user_id=" + user_info.id,
      imageUrl: "https://api.anmeila.com.cn/statics/images/ssds/wx_share_ssds.png?v=0525",
      success: function (e) {
      },
      title: "我正在参加瘦身大赛赢得3000万奖金，快来帮我投一票！"
    };*/
   

    var typeId = res.target.dataset.id;
    var page = this;
    var user_info = wx.getStorageSync("user_info");

    app.request({
      url: api.ssds.share, //仅为示例，非真实的接口地址
      method: 'POST',
      //data: { phone: data.phone_number},
      data: {
        type: typeId
      },
      success: function (res) {
 
      }
    });

    var res = {

      imageUrl: "https://api.anmeila.com.cn/statics/images/ssds/wx_share_ssds.png?v=025",
      path: "/pages/ls-toupiao-share/ls-toupiao-share?user_id=" + user_info.id + "&isticket=1&from_type=" + typeId,
      
      success: function (res) {
        　　　　　　// 转发成功之后的回调
        if (res.errMsg == 'shareAppMessage:ok') {
        　}
      },

      title: "有一种朋友叫“帮我投票”，一起瓜分49万，动动手指帮我投票吧......"
    };

    if (typeof page.data.player_info != "undefined" && page.data.player_info != null && typeof page.data.player_info.img != "undefined" && page.data.player_info.img != null && page.data.player_info.img.length > 0) {
      console.log("share center has img", page.data.player_info);
      res.imageUrl = page.data.player_info.img[0];
    } else {
      console.log("share center not has img");
      res.imageUrl = "https://api.anmeila.com.cn/statics/images/ssds/wx_share_ssds.png?v=0525";
    }

    return res;

    
  },


  goto216: function () {
    wx.navigateTo({
      url: '/pages/bundles/bundles',
    })
  },
  goto298: function () {
    wx.navigateTo({
      url: '/pages/bundles/bundles',
    })
  },
  gotoJfsc:function(){
    wx.navigateTo({
      url: '/pages/jfsc/jfsc'
    })
  },
  qiandao:function(){
    var page = this;
    app.request({
      url: api.ssds.qiandao, //仅为示例，非真实的接口地址
      method: 'POST',
      //data: { phone: data.phone_number},
      data: {},
      success: function (res) {
        wx.showToast({
          title: res.msg,
          duration: 2000
        })
      }
    });
  },
  showShareModal: function () {
    var page = this;
    page.setData({
      share_modal_active: "active",
      no_scroll: true,
    });
  },

  shareModalClose: function () {
    var page = this;
    page.setData({
      share_modal_active: "",
      no_scroll: false,
    });
  },
  getGoodsQrcode: function () {
    var page = this;
    //console.log(wx.getStorageSync("user_info").data.plaer_info.id)
    page.setData({
      goods_qrcode_active: "active",
      share_modal_active: "",
    });
    if (page.data.goods_qrcode)
      return true;
    app.request({
      url: api.ssds.share_qr,
      data: {
        user_id: 412717,
      },
      success: function (res) {
        if (res.code == 0) {
          page.setData({
            goods_qrcode: res.data.pic_url,
            goods_qrcodes: res.data.pic_url,
          });
        }
        if (res.code == 1) {
          page.goodsQrcodeClose();
          // wx.showModal({
          //   title: "提示",
          //   content: res.msg,
          //   showCancel: false,
          //   success: function (res) {
          //     if (res.confirm) {

          //     }
          //   }
          // });
        }
      },
    });
  },
   hideAttrPicker: function () {
    var page = this;
    page.setData({
      show_attr_picker: false,
    });
  },
  showAttrPicker: function () {
    var page = this;
    page.setData({
      show_attr_picker: true,
    });
  },
  goodsQrcodeClose: function () {
    var page = this;
    page.setData({
      goods_qrcode_active: "",
      no_scroll: false,
    });
  },
  saveGoodsQrcode: function () {
    var page = this;
    if (!wx.saveImageToPhotosAlbum) {
      // 如果希望用户在最新版本的客户端上体验您的小程序，可以这样子提示
      wx.showModal({
        title: '提示',
        content: '当前微信版本过低，无法使用该功能，请升级到最新微信版本后重试。',
        showCancel: false,
      });
      return;
    }

    wx.showLoading({
      title: "正在保存图片",
      mask: false,
    });

    wx.downloadFile({
      url: page.data.goods_qrcode,
      success: function (e) {
        wx.showLoading({
          title: "正在保存图片",
          mask: false,
        });
        wx.saveImageToPhotosAlbum({
          filePath: e.tempFilePath,
          success: function () {
            wx.showModal({
              title: '提示',
              content: '商品海报保存成功',
              showCancel: false,
            });
          },
          fail: function (e) {
            wx.showModal({
              title: '图片保存失败',
              content: e.errMsg,
              showCancel: false,
            });
          },
          complete: function (e) {
            console.log(e);
            wx.hideLoading();
          }
        });
      },
      fail: function (e) {
        wx.showModal({
          title: '图片下载失败',
          content: e.errMsg + ";" + page.data.goods_qrcode,
          showCancel: false,
        });
      },
      complete: function (e) {
        console.log(e);
        wx.hideLoading();
      }
    });

  },
  radioChange: function (e) {
    var page = this;
    console.log('radio发生change事件，携带value值为：', e.detail.value)
    if(e.detail.value==1){
      page.setData({
        goods_qrcode: page.data.goods_qrcodes,
      });
    }else if (e.detail.value==2){
      page.setData({
        goods_qrcode: 'https://api.anmeila.com.cn/statics/images/ssds/fxtp1.jpg',
      });
    }else if (e.detail.value==3){
      page.setData({
        goods_qrcode: 'https://api.anmeila.com.cn/statics/images/ssds/fxtp2.jpg',
      });
    }
  }
})