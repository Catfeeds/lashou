var app = getApp();
var api = require('../../api.js');
var sourceType = [['camera'], ['album'], ['camera', 'album']]
var sizeType = [['compressed'], ['original'], ['compressed', 'original']]
var user_info = wx.getStorageSync("user_info");

function getRandomColor() {
  let rgb = []
  for (let i = 0; i < 3; ++i) {
    let color = Math.floor(Math.random() * 256).toString(16)
    color = color.length == 1 ? '0' + color : color
    rgb.push(color)
  }
  return '#' + rgb.join('')
}

Page({
  onReady: function (res) {
    this.videoContext = wx.createVideoContext('myVideo')
  },
  inputValue: '',
  data: {
    src: '',
    weight: 0,
    danmuList: [
      {
        text: '第 1s 出现的弹幕',
        color: '#ff0000',
        time: 1
      },
      {
        text: '第 3s 出现的弹幕',
        color: '#ff00ff',
        time: 3
      }]
  },
  bindInputBlur: function (e) {
    this.inputValue = e.detail.value
  },
  bindButtonTap: function () {
    var that = this
    wx.chooseVideo({
      sourceType: ['album', 'camera'],
      maxDuration: 60,
      camera: ['front', 'back'],
      success: function (res) {
        that.setData({
          src: res.tempFilePath
        })
      }
    })
  },
  bindSendDanmu: function () {
    this.videoContext.sendDanmu({
      text: this.inputValue,
      color: getRandomColor()
    })
  },


  upVideoSubmit: function (e) {
    if(this.data.weight == 0){
      wx.showToast({
        icon: 'none',
        title: '请输入体重',
      });
      return;
    }
    var user_info = wx.getStorageSync("user_info");
    var that = this
    wx.chooseVideo({
      sourceType: ['album', 'camera'],
      maxDuration: 60,
      camera: 'back',
      success: function (res) {
        wx.showLoading({
          title: '正在上传...',
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
            console.log('data', data);
            if (data.code == 0) {
              app.request({
                url: api.ssds.update_weight,
                data: { weight: that.data.weight, video: data.data.video.val},
                success:function(res){
                  console.log("res is ", res);
                  if(res.code == 0){
                    wx.hideLoading();
                    wx.showToast({
                      title: "上传成功",
                      duration: 2000
                    })
                  }else{
                    wx.showToast({
                      title: res.msg,
                      icon: 'none',
                      duration: 2000
                    })
                  }
                }
              });
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
  onGoodsImageClick: function (e) {
    var page = this;
    var urls = [];
    var index = e.currentTarget.dataset.index;
    //console.log(page.data.goods.pic_list);
    for (var i in page.data.goods.pic_list) {
      urls.push(page.data.goods.pic_list[i].pic_url);
    }
    wx.previewImage({
      urls: urls, // 需要预览的图片http链接列表
      current: urls[index],
    });
  },

  setupWeight:function(e){
    var weight = e.detail.value;
    weight = parseFloat(weight);
    if(!isNaN(weight)){
      weight = weight.toFixed(2);
      this.data.weight = weight;
    }
    console.log('weight is ', weight);
  }
})
  


