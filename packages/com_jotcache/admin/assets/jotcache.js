var jotcache = {
    lang: [], re: /^[._a-zA-Z0-9\-]+[=]?[._a-zA-Z0-9\-]+/, resetSelect: function (a) {
        if (a === 1) {
            jQuery("#filter_view").val("")
        }
        this.setReset();
        jQuery("#adminForm").submit()
    }, setReset: function () {
        jQuery("#adminForm").attr("target", "");
        jQuery("#form_view").val("main");
        jQuery("#form_task").val("display")
    }, setRecache: function () {
        jQuery("#form_view").val("recache");
        jQuery("#adminForm").attr("target", "_blank")
    }, submitform: function (a, b) {
        if (a === "recache.display") {
            this.setRecache()
        } else {
            this.setReset()
        }
        if (b === undefined) {
            b = document.getElementById("adminForm");
            if (!b) {
                b = document.adminForm
            }
        }
        if (a !== undefined && "" !== a) {
            b.task.value = a
        }
        if (typeof b.onsubmit === "function") {
            b.onsubmit()
        }
        if (typeof b.fireEvent === "function") {
            b.fireEvent("submit")
        }
        b.submit()
    }, chckon: function (a) {
        if (a.get("value") !== "") {
            this.top = a.getParent().getParent();
            this.top.getElement("input[type=checkbox]").checked = true
        }
    }, valoff: function (a) {
        if (a.checked === false) {
            this.top = a.getParent().getParent();
            this.top.getElements("input[name^=ex]").each(function (b) {
                b.value = ""
            })
        }
    }
};
var jotcacheajax = {
    timer: null, requestinfo: function () {
        var a;
        if (jotcacheflag === 1) {
            a = "plugin=" + jQuery("#myTabTabs").find("li.active a").text().toLowerCase()
        } else {
            a = "flag=stop"
        }
        var b = jQuery.ajax({
            url: jotcachereq, async: true, type: "GET", data: a, beforeSend: function () {
                jQuery("#toolbar-start").css({opacity: "0.5"});
                jQuery("#toolbar-stop").css({opacity: "1.0"});
                jQuery("#spinner-here").css({display: "inline"})
            }, success: function (c) {
                jQuery("#message-here").text(c)
            }, error: function (c) {
                jQuery("#message-here").text(c)
            }
        })
    }, again: function () {
        jotcacheajax.timer = jotcacheajax.requestinfo.periodical(2000)
    }
};