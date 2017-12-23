<meta charset="utf-8">
<?php

	include './phpQuery/phpQuery.php';

	// 2016.07.27
	// 国内高匿名代理
	// 获取代理IP页面 http://www.xicidaili.com/nn/

	$page = 1;
	$url = "http://www.xicidaili.com/nn/{$page}";

	$cookie = "CNZZDATA1256960793=2044521018-1469592615-null%7C1469689952; _free_proxy_session=BAh7B0kiD3Nlc3Npb25f
aWQGOgZFVEkiJTU4MjA5ZWQyOWVlYWVjMGE2MzMxOTdmNTkxN2JlMzc5BjsAVEkiEF9jc3JmX3Rva2VuBjsARkkiMUZDMzFLSm5DN1g3QkRXZTFaMExsNGJFOVFuRldkMTdXZHJ2VlFxYkRmbmM9BjsARg
%3D%3D--28580e57d0a28ce98a5259cea51e538a3ddf35d0";

	$userAgent[0] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.10240";
	$userAgent[1] = "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0";
	$userAgent[2] = "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko";
	$userAgent[3] = "Mozilla/5.0 (X11; Linux i686; rv:43.0) Gecko、20100101 Firefox/43.0 Iceweasel/43.0.4";

	$ch = curl_init();	//打开一个curl会话
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//设置获取到的内容是否输出到流 非零是不输出
	curl_setopt($ch,CURLOPT_HEADER,0);
	curl_setopt($ch,CURLOPT_COOKIE,$cookie);//设置请求是浏览器cookie
	curl_setopt($ch,CURLOPT_ENCODING,"gzip, deflate");//设置Accept-Encoding
	curl_setopt($ch,CURLOPT_REFERER,"http://www.xicidaili.com/");//设置Referer
	curl_setopt($ch,CURLOPT_USERAGENT,$userAgent[rand(0,3)]);//设置User-Agent
	$data = curl_exec($ch);	//执行curl请求，并获取输出的内容

	//将输出的内容写入文件，用以解析筛选数据
	if(file_put_contents('ip.html',$data)){
		//echo file_get_contents('ip.html');
		if(phpQuery::newDocumentFile('ip.html')){
			if($iplist = pq("#ip_list tr:eq(0)")->nextAll()){
				//echo pq("#ip_list tr:eq(0)")->nextAll()->html();
				foreach ($iplist as $value) {
					//var_dump(trim($value->nodeValue));
					$ip = "/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?\s*\d{2,5})/";
					$timeout = "/\b\d{1,4}[\u4e00-\u9fa5]{1,2}/";

					preg_match_all($ip, $value->nodeValue, $ipinfo);
					//preg_match_all($timeout, $value->nodeValue, $timeout);
					//var_dump(str_replace("\n      ",":",$ip[0][0]));
					$ipArr[] = str_replace("\n      ",":",$ipinfo[0][0]);
				}
				//print_r($ipArr);
				
			}else{
				echo "选定数据失败!";
			}
		}else{
			echo "文件读取失败!";
		}
	}else{
		echo "文件写入失败!";
	}

	curl_close($ch);