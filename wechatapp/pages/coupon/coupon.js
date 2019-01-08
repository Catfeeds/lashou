// pages/coupon/coupon.js
var api = require('../../api.js');
var app = getApp();
Page({

    /**
     * 页面的初始数据
     */
    data: {
        list: [],
    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
        app.pageOnLoad(this);
        this.setData({
            status: options.status || 0,
        });
        this.loadData(options);
    },

    loadData: function (options) {
        var page = this;
        wx.showLoading({
            title: "加载中",
        });
        app.request({
            url: api.coupon.index,
            data: {
                status: page.data.status,
            },
            success: function (res) {
                if (res.code == 0) {
                    page.setData({
                        list: res.data.list,
                    });
                }
                // console.log(res.data.list)
                // var list = res.data.list;
                // for (var i in list) {
                //     var cat = list[i].cat
                //     console.log(list[i].cat)
                //     for (var x in cat) {
                //         var length = cat[x].name.length
                //     }
                // }
                // console.log(length)
            },
            complete: function () {
                wx.hideLoading();
            }
        });
    },

    /**
     * 生命周期函数--监听页面显示
     */
    onShow: function () {

    },
    xia:function(e){
        var index = e.target.dataset.index;
        this.setData({
            check: index,
        });
    },
    shou: function () {
        this.setData({
            check: -1,
        });
    },
    
});