<?php

/**
 *	转码为GBK
 */
function GBK($string) {
	return mb_convert_encoding($string, "GBK", "UTF-8");
}

/**
 *	转码为UTF8
 */
function UTF8($string) {
	return mb_convert_encoding($string, "UTF-8", "GBK");
}

/**
 *	处理用户信息保存
 */
function saveUserInfo() {
	$sn = trim($_POST['sn']);
	$pw = trim($_POST['pw']);

	$ch = curl_init("http://59.77.226.32/logincheck.asp");
	curl_setopt_array($ch, array(
		CURLOPT_HEADER		=> true,
		CURLOPT_HTTPHEADER	=> array(
			"Host: 59.77.226.32"
		),
		CURLOPT_REFERER		=> "http://jwch.fzu.edu.cn/",
		CURLOPT_POST		=> true,
		CURLOPT_POSTFIELDS	=> http_build_query(array(
			"muser"		=> $sn,
			"passwd"	=> $pw
		)),
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true
	));
	$result = curl_exec($ch);

	preg_match_all('/Set-Cookie:(.+?)\r\n/i', $result, $raw1);
	preg_match('/src="left\.aspx\?id=([^"]+)"/', $result, $raw2);

	$id 	= @$raw2[1];
	$cookie = implode(";", $raw1[1]);

	$response = array();
	$response['status'] = false;

	if($id) {
		$response['status'] = true;
		setcookie("USER_COOKIE", $cookie, time()+3600);
		setcookie("USER_ID", $id, time()+3600);
		$response['message'] = "验证成功，请点击开始评议";
	} else {
		$response['message'] = "教务处验证失败，请重试！";
	}

	curl_close($ch);
	echo json_encode($response, true);
}

/**
 *	获取评议列表
 */
function fetchList() {
	$type = $_GET['type'];
	$ch = curl_init("http://59.77.226.35/student/jscp/TeaList.aspx?id=".$_COOKIE['USER_ID']."&bj=".$type);
	curl_setopt_array($ch, array(
		CURLOPT_HEADER		=> false,
		CURLOPT_HTTPHEADER	=> array(
			"Host: 59.77.226.35"
		),
		CURLOPT_REFERER		=> "http://59.77.226.35/left.aspx?id=".$_COOKIE['USER_ID'],
		CURLOPT_COOKIE		=> $_COOKIE['USER_COOKIE'],
		CURLOPT_RETURNTRANSFER => true
	));
	$result = curl_exec($ch);
	$list = array();

	preg_match_all("/href='TeaEvaluation\.aspx\?([^']+)'/i", $result, $raw);

	foreach($raw[1] as $each) {
		$url = "http://59.77.226.35/student/jscp/TeaEvaluation.aspx?".$each;
		$url = preg_replace("/&id=\d+&bj=/", "&id=".$_COOKIE['USER_ID']."&bj=", $url);
		$list[] = $url;
	}
	curl_close($ch);

	echo json_encode($list, true);
}

/**
 *	获取验证码
 */
function getImage() {
	$url = $_POST['url'];
	$ch = curl_init($url);
	curl_setopt_array($ch, array(
		CURLOPT_HEADER		=> true,
		CURLOPT_HTTPHEADER	=> array(
			"Host: 59.77.226.35"
		),
		CURLOPT_REFERER		=> "http://jwch.fzu.edu.cn/",
		CURLOPT_COOKIE		=> $_COOKIE['USER_COOKIE'],
		CURLOPT_RETURNTRANSFER => true
	));
	$result = curl_exec($ch);
	curl_close($ch);

	// 表单参数
	preg_match('/<input type="hidden" name="__VIEWSTATE" id="__VIEWSTATE" value="([^"]+)" \/>/', $result, $raw1);
	preg_match('/<input type="hidden" name="__EVENTVALIDATION" id="__EVENTVALIDATION" value="([^"]+)" \/>/', $result, $raw2);
	
	// 验证码的COOKIE
	preg_match_all('/Set\-Cookie\:([^\r\n]+)/i', $result, $raw3);

	$viewstate 		 = @$raw1[1];
	$eventValidation = @$raw2[1];
	$codeCookie 	 = implode(";", $raw3[1]);

	$_SESSION["VIEWSTATE"]			= $viewstate;
	$_SESSION["EVENTVALIDATION"]	= $eventValidation;
	$_SESSION["CODE_COOKIE"]		= $codeCookie;

	$ch = curl_init("http://59.77.226.35/student/jscp/ValidNums.aspx");
	curl_setopt_array($ch, array(
		CURLOPT_HEADER		=> false,
		CURLOPT_HTTPHEADER	=> array(
			"Host: 59.77.226.35"
		),
		CURLOPT_REFERER		=> "http://jwch.fzu.edu.cn/",
		CURLOPT_COOKIE		=> $_COOKIE['USER_COOKIE'].";".$codeCookie,
		CURLOPT_RETURNTRANSFER => true
	));
	$result = curl_exec($ch);
	curl_close($ch);

	echo base64_encode($result);
}

// 评议过程
function doProcess() {
	$data = array(
		'__VIEWSTATE'							=> $_SESSION['VIEWSTATE'],
		'__EVENTVALIDATION' 					=> $_SESSION['EVENTVALIDATION'],
		'ctl00$ContentPlaceHolder1$TB_zf'		=> rand(80, 90),
		'ctl00$ContentPlaceHolder1$TB_pj' 		=> "老师上课认真，备课充分，讲义详细，和蔼可亲，非常赞！谢谢老师的努力付出！",
		'ctl00$ContentPlaceHolder1$RB_List1' 	=> "优秀",
		'ctl00$ContentPlaceHolder1$RB_List2' 	=> "优秀",
		'ctl00$ContentPlaceHolder1$RB_List3' 	=> "优秀",
		'ctl00$ContentPlaceHolder1$RB_List4' 	=> "优秀",
		'ctl00$ContentPlaceHolder1$RB_List5' 	=> "优秀",
		'ctl00$ContentPlaceHolder1$verifycode' 	=> $_POST['code'],
		'ctl00$ContentPlaceHolder1$Button_xk' 	=> "确定",
	);

	$dataStr = $split = "";
	foreach($data as $key => $val) {
		$dataStr .= $split.$key."=".rawurlencode($val);
		$split = "&";
	}

	$ch = curl_init($_POST['url']);
	curl_setopt_array($ch, array(
		CURLOPT_HTTPHEADER	=> array(
			"Host: 59.77.226.35"
		),
		CURLOPT_REFERER		=> "http://59.77.226.35",
		CURLOPT_POST		=> true,
		CURLOPT_POSTFIELDS	=> $dataStr,
		CURLOPT_COOKIE		=> $_COOKIE['USER_COOKIE'].";".$_SESSION["CODE_COOKIE"],
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_FOLLOWLOCATION => true
	));
	$result = curl_exec($ch);

	$response = array();
	$response['status'] = true;

	if(strpos($result, "alert")) {
		$response['status'] = false;
	}

	echo json_encode($response);
}

function requireUserInterface() {
	include "template.php";
}