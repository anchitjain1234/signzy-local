$(function () {
    var cid = $('.compid').attr('id');
    var uid = $('.userid').attr('id');

    $.remind_heads = function () {
        $.ajax({
            url: '../compmember/remind_leagal_heads/' + cid,
            method: 'POST',
            data: {"cid": cid, "uid": uid}
        }).success(function (res) {
            res = JSON.parse(res);
            console.log(res);
        }).error(function (res) {
            $('#alertdiv').append("<div id=\"alert\"></div>");
            $('#alert').addClass("alert alert-danger");
            $('#alert').html("<a href=\"#\" class=\"close\" data-dismiss=\"alert\" aria-label=\"close\">&times;</a>\n\
                                          <strong>Error!</strong> Network error.Please check if you internet connection is stable");
        });
    };
});


