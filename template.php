<!DOCTYPE html>
<html>
<head>
	<title>评议助手</title>
	<meta charset="utf-8" />
	<script type="text/javascript" src="jquery.js"></script>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
	<meta content="width=device-width, initial-scale=1.0" name="viewport"/>
</head>
<body>
<style type="text/css">
.popWindow { display: none; position: fixed; left: 0px; right: 0px; top: 100px; margin: auto; width: 300px; background: #F5F5F5; border: 1px solid #E5E5E5; box-shadow: 0px 0px 50px #999 }
.list { width: 300px; margin: 0px auto; padding: 0px }
.list p { margin: 0px; padding: 0px; font-size: 13px; line-height: 20px }
</style>

<div align="center">
	<h2>评议助手</h2>
	<p style="font-size:12px">本工具可减少您评议的繁琐步骤，只需输入验证码即可<br/>提示：使用PC操作可能更畅快</p>
</div>

<div class="list">
	<h4>使用本程序,默认你同意以下约定:</h4>
	<p>1.不向周围人传播,自己爽就好</p>
	<p>2.用此程序只是迫不得已,我一定会认真评议老师的。</p>
	<p>3.我已对有我自己想提意见、鼓励的老师进行了手动评议。</p>
	<p>4.我愿意承担使用本程序的一切后果。</p>
	<p>5.我知道这么做是对学校、老师的不负责。</p>
	<p>6.<b>本程序不会给老师满分！！！！在80~90之间</b></p>
	<p>7.拒绝查水表</p>
</div>
   
<div align="center">
	<p style="font-size:12px;color:#F00" id="error"></p>
	<p>学号：<input type="text" maxlength="9" value="" name="sn" /></p>
	<p>密码：<input type="password" name="pw" value="" /></p>
	<button id="save" onclick="save()">验证账号</button>

	<p>用途：
		<label><input type="radio" name="usefor" value="xqxk" checked="checked" /> 选课</label>
		<label><input type="radio" name="usefor" value="score" /> 查成绩</label>
	</p>
	<button id="start" onclick="start()" disabled="disabled">开始评议</button>
	<div id="list" style="margin: 10px"></div>
	<p><img src="pic.jpg" style="max-width:300px" /></p>

	<script type="text/javascript">var cnzz_protocol = (("https:" == document.location.protocol) ? " https://" : " http://");document.write(unescape("%3Cspan style='display:none;' id='cnzz_stat_icon_1261009579'%3E%3C/span%3E%3Cscript src='" + cnzz_protocol + "s4.cnzz.com/stat.php%3Fid%3D1261009579%26show%3Dpic' type='text/javascript'%3E%3C/script%3E"));</script>
</div>

<div class="popWindow" align="center">
	<p>由于教务处改版，请手动输入验证码</p>
	<img src="" id="code" />
	<p><input type="text" id="userInput" /></p>
	<p><button type="button" id="another">更换验证码</button> <button type="button" id="confirm">确定</button></p>
</div>

<script type="text/javascript">
var curUrl;
var curIndex = 0;
var teaListUrl;

function showError(error) {
	$("#error").text(error);
}

function save() {
	var sn = $("[name='sn']").val();
	var pw = $("[name='pw']").val();

	sn = $.trim(sn);
	pw = $.trim(pw);

	if(sn == "") {
		showError("请输入学号");
	} else if(pw == "") {
		showError("请输入密码");
	} else {
		$.ajax({
			url: "index.php?f=saveUserInfo",
			type: "POST",
			async: false,
			data: {
				sn: sn,
				pw: pw
			},
			dataType: "json",
			success: function(ret) {
				if(ret.status) {
					$("#start").prop("disabled", false);
				}
				$("#save").text("重新验证").prop("disabled", false);
				showError(ret.message);
			},
			beforeSend: function() {
				$("#save").text("验证中..").prop("disabled", true);
			}
		})
	}
}

function start() {
	$.ajax({
		url: "index.php?f=fetchList",
		type: "GET",
		async: false,
		data: {
			type: $("[name='usefor']:checked").val()
		},
		dataType: "json",
		success: function(ret) {
			if(ret.length != 0) {
				var nameList = "";
				teaListUrl = new Array();
				$("#start").text("处理列表中");
				for(var i in ret) {
					var name = ret[i].match(/jsxm=([^&]+)/);
					nameList += '<span>'+name[1]+'</span><br/>';
					teaListUrl.push(ret[i]);
				}
				$("#list").html(nameList);
				processList();
			} else {
				$("#start").text("开始评议").prop("disabled", false);
				alert("评议失败，请重试！(或者已经全部评议完成）");
			}
		},
		beforeSend: function() {
			$("#start").text("获取列表中").prop("disabled", true);
		}
	})
}

function getCode(url) {
	$.ajax({
		url: "index.php?f=getImage",
		type: "POST",
		async: false,
		data: {
			url: url
		},
		dataType: "text",
		success: function(imageData) {			
			$("#code").attr("src", "data:image/gif;base64,"+imageData);
		}
	});
}

function processList() {
	if(curIndex == teaListUrl.length) {
		alert("恭喜您，全部评议完成！");
	} else {
		curUrl = teaListUrl[curIndex];
		getCode(curUrl);
		$(".popWindow").show();
		$(".popWindow #userInput").val("").focus();
	}
}

$(".popWindow #confirm").click(function() {
	var code = $.trim($(".popWindow #userInput").val());
	if(code != "") {
		$.ajax({
			url: "index.php?f=doProcess",
			type: "POST",
			async: false,
			data: {
				url: curUrl,
				code: code,
			},
			dataType: "json",
			success: function(ret) {
				if(!ret.status) {
					showError("评议失败，请重新验证！");
					$("#list").html("");
					$("#start").text("开始评议").prop("disabled", true);
				} else {
					$("#list span:eq("+curIndex+")").text("已完成该老师的评议");
					curIndex++;
					processList();
				}
			},
			beforeSend: function() {
				$("#list span:eq("+curIndex+")");
				$(".popWindow").hide();
			}
		});
	}
});

$(".popWindow #another").click(function() {
	getCode(curUrl);
});

$("#userInput").keyup(function(e) {
	if(e.keyCode == 13) {
		$("#confirm").click();
	}
});
</script>
</body>
</html>