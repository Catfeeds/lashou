// pages/cash/cash.js
var api = require('../../api.js');
var app = getApp();

function min(var1, var2) {
    var1 = parseFloat(var1);
    var2 = parseFloat(var2);
    return var1 > var2 ? var2 : var1;
}

Page({

    /**
     * 页面的初始数据
     */
    data: {
        is_partner:0,
        price: 0.00,
        cash_max_day: -1,
        selected: -1,
        partner_amount: 0.00,

    },

    /**
     * 生命周期函数--监听页面加载
     */
    onLoad: function (options) {
      this.setData({
        is_partner: options.is_partner
      });
        app.pageOnLoad(this);
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
        var page = this;
        var share_setting = wx.getStorageSync("share_setting");
        var custom = wx.getStorageSync("custom");
        page.setData({
            share_setting: share_setting,
            custom: custom
        });
        app.request({
            url: api.share.get_price,
            success: function (res) {
                if (res.code == 0) {
                    var cash_last = res.data.cash_last;
                    var name = '';
                    var mobile = '';
                    var bank_name = '';
                    var type = '';
                    var selected = page.data.selected;
                    if (cash_last) {
                        name = cash_last['name'];
                        mobile = cash_last['mobile'];
                        bank_name = cash_last['bank_name'];
                        type = cash_last['type'];
                    }
                    page.setData({
                        total_price: res.data.price.total_price,
                        partner_amount: res.data.price.partner_amount,
                        price: res.data.price.price,
                        cash_max_day: res.data.cash_max_day,
                        pay_type: res.data.pay_type,
                        bank: res.data.bank,
                        remaining_sum: res.data.remaining_sum,
                        name: name,
                        mobile: mobile,
                        bank_name: bank_name,
                        selected: selected,
                        check: type
                    });
                }
            }
        });
    },

    /**
     * 页面相关事件处理函数--监听用户下拉动作
     */
    onPullDownRefresh: function () {

    },
    formSubmit: function (e) {
        var page = this;
        var cash = parseFloat(parseFloat(e.detail.value.cash).toFixed(2));
        if (page.data.is_partner == 1)
           var cash_max = page.data.partner_amount;
        else
           var cash_max = page.data.price;
        if (page.data.cash_max_day != -1) {
            cash_max = min(cash_max, page.data.cash_max_day)
        }
        if (!cash) {
            wx.showToast({
                title: "请输入提现金额",
                image: "/images/icon-warning.png",
            });
            return;
        }
        if (cash > cash_max) {
            wx.showToast({
                title: "提现金额不能超过" + cash_max + "元",
                image: "/images/icon-warning.png",
            });
            return;
        }

        
        if (cash < parseFloat(page.data.share_setting.min_money)) {
            wx.showToast({
                title: "提现金额不能低于" + page.data.share_setting.min_money + "元",
                image: "/images/icon-warning.png",
            });
            return;
        }
        var selected = page.data.selected;
        if (selected != 0 && selected != 1 && selected != 2 && selected != 3) {
            wx.showToast({
                title: '请选择提现方式',
                image: "/images/icon-warning.png",
            });
            return;
        }

        if (selected == 0 || selected == 1 || selected == 2) {
            var name = e.detail.value.name;
            if (!name || name == undefined) {
                wx.showToast({
                    title: '姓名不能为空',
                    image: "/images/icon-warning.png",
                });
                return;
            }
            var mobile = e.detail.value.mobile;
            if (!mobile || mobile == undefined) {
                wx.showToast({
                    title: '账号不能为空',
                    image: "/images/icon-warning.png",
                });
                return;
            }
        }
        if (selected == 2) {
            var bank_name = e.detail.value.bank_name;
            if (!bank_name || bank_name == undefined) {
                wx.showToast({
                    title: '开户行不能为空',
                    image: "/images/icon-warning.png",
                });
                return;
            }
        } else {
            var bank_name = '';
        }
        if (selected == 3) {
            var bank_name = '';
            var mobile = '';
            var name = '';
        }
        wx.showLoading({
            title: "正在提交",
            mask: true,
        });
        app.request({
            url: api.share.apply,
            method: 'POST',
            data: {
                is_partner: page.data.is_partner,
                cash: cash,
                name: name,
                mobile: mobile,
                bank_name: bank_name,
                pay_type: selected,
                scene: 'CASH',
                form_id: e.detail.formId,
            },
            success: function (res) {
                wx.hideLoading();
                wx.showModal({
                    title: "提示",
                    content: res.msg,
                    showCancel: false,
                    success: function (e) {
                        if (e.confirm) {
                            if (res.code == 0) {
                                wx.redirectTo({
                                    url: '/pages/cash-detail/cash-detail',
                                })
                            }
                        }
                    }
                });
            }
        });
    },

    showCashMaxDetail: function () {
        wx.showModal({
            title: "提示",
            content: "今日剩余提现金额=平台每日可提现金额-今日所有用户提现金额"
        });
    },
    select: function (e) {
        var index = e.currentTarget.dataset.index;
        var check = this.data.check;
        if (index != check) {
            this.setData({
                name: '',
                mobile: '',
                bank_name: '',
            });
        }
        this.setData({
            selected: index
        });
    }

});