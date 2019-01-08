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
    has_timeline_task:false,
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
      count: 1,
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
            var my_res = JSON.parse(res.data);

            var imageList = that.data.form.imageList;
            imageList.push(my_res.file);

            that.setData({
              form: { imageList: imageList}
            })

            wx.hideLoading();
          }
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
          var pageData = { video_list: res.data.current_weight_logs};
          if (res.data.player_info != null && typeof res.data.player_info != "undefined"){
            pageData.player_info = res.data.player_info;
            pageData.form = { imageList: res.data.player_info.img };
          }
          if (res.data.user_info) {
            pageData.user_info = res.data.user_info;
          } 
          if (res.data.current_share_result) {
            pageData.current_share_result = res.data.current_share_result;
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

    app.request({
      url: api.ssds.has_timeline_task,
      data: null,
      success: function (res) {
        if (res.code == 0) {
          page.setData({
            has_timeline_task: res.data.has_timeline_task,
          });
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

  formSubmit: function (e) {
    var page = this;
    page.data.form.name = e.detail.value.name;
    page.data.form.xuanyan = e.detail.value.xuanyan;

    page.data.player_info.name = e.detail.value.name;
    page.data.player_info.declaration = e.detail.value.xuanyan;

    page.setData({
      form:page.data.form,
      player_info: page.data.player_info
    });
    
    app.request({
      url: api.activity.vote.edit, //仅为示例，非真实的接口地址
      method: 'POST',
      //data: { phone: data.phone_number},
      data: {
        declaration: e.detail.value.xuanyan,
        name: e.detail.value.name
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

  goto298: function () {
    wx.navigateTo({
      url: '/pages/add-share/index',
    })
  },
  gotoJfsc:function(){
    wx.navigateTo({
      url: '/pages/jfsc/jfsc'
    })
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
            wx.openSetting({
              success: function (res) {
                console.log("openSetting ", res);
              }
            })
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
  },
  play: function (e) {
    var url = e.target.dataset.url;//获取视频链接
    this.setData({
      url: url,
      show_play: true,
    });
    var videoContext = wx.createVideoContext('video');
    videoContext.play();
  },
  closePlayVideo:function(){
    this.setData({
      show_play: false,
    });
  },
  removePlayerImage:function(e){
    var context = this;
    wx.showModal({
      title: '提示',
      content: '确定要删除该图片吗？',
      success: function (res) {
        if (res.confirm) {
          console.log('用户点击确定')
          var source_id = e.currentTarget.dataset.sourceId;
          app.request({
            url: api.ssds.remove_image,
            data: { id: source_id },
            success: function (res) {
              if (res.code == 0) {
                var imageList = context.data.form.imageList;
                var _imageList = [];
                for(var i=0; i<imageList.length; i++){
                  var img = imageList[i];
                  if (img.id == source_id){
                    continue;
                  }else{
                    _imageList.push(img);
                  }
                }
                context.setData({
                  form: { imageList: _imageList }
                })
              } else {
                wx.showToast({
                  title: res.msg,
                  icon: 'none'
                })
              }
            }
          });
        } else if (res.cancel) {
          console.log('用户点击取消')
        }
      }
    })
  }
})