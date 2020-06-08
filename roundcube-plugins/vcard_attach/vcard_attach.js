function opencontactwindow(a) {
    var d = Math.min(screen.width - 20, 1400),
        b = screen.left ? screen.left : 0,
        c = screen.top ? screen.top : 0,
        e = (screen.width - d) / 2 + b,
        f = (screen.height - 800) / 2 + c - 50,
        a = window.open(a, "", "scrollbars=no,width=" + d + ",height=800,top=" + (f < c ? c : f) + ",left=" + (e < b ? b : e));
    a.focus();
    return a;
}
$(document).ready(function () {
    $("td").each(function () {
        (!$(this).hasClass("title") || -1 < $(this).html().indexOf("<!-- -->")) && $(this).css("vertical-align", "top");
    });
});
