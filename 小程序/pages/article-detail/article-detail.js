// pages/article-detail/article-detail.js
var api = require('../../api.js');
var app = getApp();
var WxParse = require('../../wxParse/wxParse.js');
Page({

    /**
     * 页面的初始数据
     */
    data: {
      show_video: false,
      video_url: ''
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
        app.pageOnLoad(this);
      getApp().track("帮助详情页", options);
        var page = this;
        app.request({
            url: api.default.article_detail,
            data: {
                id: options.id,
            },
            success: function (res) {
                if (res.code == 0) {
                    wx.setNavigationBarTitle({
                        title: res.data.title,
                    });
                    WxParse.wxParse("content", "html", res.data.content, page);

                    if (res.data.video_url && res.data.video_url.length > 0){
                      page.setData({
                        show_video:true,
                        video_url: res.data.video_url,
                      });
                    }
                }
                if (res.code == 1) {
                    wx.showModal({
                        title: "提示",
                        content: res.msg,
                        showCancel: false,
                        confirm: function (e) {
                            if (e.confirm) {
                                wx.navigateBack();
                            }
                        }
                    });
                }
            }
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
})