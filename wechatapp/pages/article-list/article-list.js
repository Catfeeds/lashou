// pages/article-list/article-list.js
var api = require('../../api.js');
var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {
        article_list: [],
        cat_id:2,
        title:"服务中心"
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
        app.pageOnLoad(this);

        if(typeof options.id != "undefined" && options.id > 0){
          this.data.cat_id = options.id;
        }

        if (typeof options.title != "undefined") {
          this.data.title = options.title;
        }

        wx.setNavigationBarTitle({
          title: this.data.title,
        })

        var page = this;
        wx.showLoading();
        app.request({
            url: api.default.article_list,
            data: {
              cat_id: page.data.cat_id,
            },
            success: function (res) {
                wx.hideLoading();
                page.setData({
                    article_list: res.data.list,
                });
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