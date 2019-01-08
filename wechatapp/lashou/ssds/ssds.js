var ls_ssds = {
  _page: null,
  _dasai: null,
  _is_show: false,
  init: function (page) {
    this._page = page;
    console.log("hongbao init page", page);

    this._refreshUi();
    this._bindEvents();
  },
  setDasai:function(dasai, is_show){
    this._dasai = dasai;
    if(typeof is_show != "undefined"){
      this._is_show = is_show;
    }else{
      this._is_show = !(dasai == null || typeof dasai == "undefined");
    }
    this._refreshUi();
  },
  setIsShow:function(is_show){
    this._is_show = is_show;
    this._refreshUi();
  },
  getDasai:function(){
    return this._dasai;
  },
  _refreshUi: function () {
    var context = this;
    context._page.setData({
      ls_ssds: { dasai: context._dasai, is_show:context._is_show},
    })
  },
  _bindEvents:function(){
    var context = this;
    this._page.lsCloseSsds = function(e){
      console.log("you click lashou close ssds", e);
      context.setIsShow(false);
    }
  },
}
module.exports = ls_ssds;