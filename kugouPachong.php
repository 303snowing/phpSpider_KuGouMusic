<meta charset="utf-8">
<?php
	ini_set('max_execution_time','0');
	include 'getIP.php';

	if($conn = mysql_connect('localhost','root','')){
		//echo "success";
		mysql_select_db('test');
		mysql_set_charset('utf8');

	}else{
		die('数据库连接失败');
	}
	
	
	//向酷狗歌手单曲页面请求，返回一个歌手全部单曲的二维数组 包含 0歌手歌曲名 1歌曲hash值 2不知道啥代码
	function songNH($url,$ip,$port){
			if(!empty($url)){

					//使用代理请求
					do{
						//!!!!!!!!!!!!!!!反复判断IP合理性
						//请求前随机取得一个代理IP
						$count = rand(0,count($ipArr)-1);
						$ip_port = explode(":",$ipArr[$count]);
						if(!empty($ip_port)){
							if(!empty($ip)&&!empty($port)){
								$ip = $ip_port[0];
								$port = $ip_port[1];
								break;
							}else{
								continue;
							}
							
						}else{
							continue;
						}
					}while (!empty($ipArr));
					
					//！！！！！！！！待解决:浏览器屏蔽！！！改写请求头信息！！！定义浏览器标识！
					$userAgent[0] = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.135 Safari/537.36 Edge/12.10240";
					$userAgent[1] = "Mozilla/5.0 (Windows NT 10.0; WOW64; rv:47.0) Gecko/20100101 Firefox/47.0";
					$userAgent[2] = "Mozilla/5.0 (Windows NT 10.0; WOW64; Trident/7.0; rv:11.0) like Gecko";
					$userAgent[3] = "Mozilla/5.0 (X11; Linux i686; rv:43.0) Gecko、20100101 Firefox/43.0 Iceweasel/43.0.4";
					
					$ch = curl_init();
					$timeout = 300;
					curl_setopt($ch,CURLOPT_URL,$url);
					curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
					curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);//请求等待时间
					curl_setopt($ch,CURLOPT_HTTPPROXYTUNNEL,true);//开启代理
					curl_setopt($ch, CURLOPT_PROXYAUTH, CURLAUTH_BASIC); //代理认证模式
					curl_setopt($ch, CURLOPT_PROXY, $ip); //代理服务器地址 
					curl_setopt($ch, CURLOPT_PROXYPORT, $port); //代理服务器端口
					curl_setopt($ch,CURLOPT_USERAGENT,$userAgent[rand(0,3)]);//设置User-Agent
					curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); //使用http代理模式
					curl_setopt($ch,CURLOPT_HEADER,0);
					$kg_contents = curl_exec($ch);
					curl_close($ch);
					
					if(file_put_contents('kgCon.html',$kg_contents)){
						if(phpQuery::newDocumentFile('kgCon.html')){

							if(pq("#song_container li a input")){
								//var_dump($songList->html());
								foreach (pq("#song_container li a input") as $songHtml) {
									//var_dump(pq($songHtml)->val());
									$songHtml = pq($songHtml)->val();

									$song = explode("|",$songHtml);
									//var_dump($song);
									//歌名 - 歌手名 $song[0]   hash值 $song[1]
									$song[1] = strtolower($song[1]);
									//echo "歌名:{$song[0]} hash:{$hash}<br>";
									$songArrList[] = $song;
								}
								
								return $songArrList;

								
							}else{
								continue;
							}
						}else{
							continue;
						}
					}else{
						echo "获取页面内容失败!";
					}
			}else{
				continue;
			}
	}


	//用hash值请求歌曲信息，返回歌曲信息二维数组
	//[songNaem]歌名[songer]歌手[fileName]歌名-歌手[url]歌曲文件链接[fileSize]文件大小[status]状态(不知啥用)
	//[extName]格式名称[bitRate]比特流[timeLength]歌曲时长
	function getSongData($songArrList){
		//var_dump($songArrList);
		foreach ($songArrList as $songArr) {
			//print_r($songArr);
			$hash = $songArr[1];
			$key = md5($hash."kgcloud");	//key值编码后请求

			//sleep(5);	//每首歌请求开始前暂停5秒，防止网站反爬虫屏蔽IP
			
			$getSongUrl = "http://trackercdn.kugou.com/i/?cmd=3&acceptMp3=1&pid=6&hash={$hash}&key={$key}";
			if ($songInfo = file_get_contents($getSongUrl)) {
				//var_dump($songInfo);
				//var_dump(explode(",",str_replace("\\","",$songInfo)));
				$songInfo = explode(",",str_replace("}","",str_replace("{","",str_replace("\\","",$songInfo))));
				foreach ($songInfo as $key => $value) {
					//echo $value."<br>";

					$data = explode(":",str_replace("\"","",$value),2);
					//var_dump($data);
					$keyArr[] = $data[0];
					$valueArr[] = $data[1];
					
				}
				//从文件名分离歌手和歌曲名
				$song_ng = explode("-",$songArr[0]);
				$songer = rtrim($song_ng[0]);
				$songName = ltrim($song_ng[1]);
				//echo $songer.'<br>';
				
				$songData['songer'] = $songer;
				$songData['songName'] = $songName;

				//var_dump($keyArr);
				//var_dump($valueArr); 
				foreach($keyArr as $i => $key){
					$songData[$key] = $valueArr[$i];
				}
				$songData['fileName'] = $songArr[0];
				$songData['hash'] = $hash;
				
				//echo '<br><br>';
				//print_r($songData);
				//echo '<br><br>';

				$songDataList[] = $songData;
			
			}else{
				echo "<br><br>获取{$songArr[0]}失败，地址{$getSongUrl}<br><br>";
				continue;
			}
		}
		return $songDataList;
		
	}



	function creatSong($songDataList){

		//向数据库中写入歌曲信息(一个歌手全部单曲为一个数据包)
		if(!empty($songDataList)){
			$count = 0;
			foreach ($songDataList as $songData) {
				$addtime = time();
				$sql = "insert into pachong_kugou (filename,songname,songer,filesize,extname,bitrate,timelength,songlink,hash,addtime) values ('{$songData["fileName"]}','{$songData["songName"]}','{$songData["songer"]}','{$songData["fileSize"]}','{$songData["extName"]}','{$songData["bitRate"]}','{$songData["timeLength"]}','{$songData["url"]}','{$songData["hash"]}','{$addtime}' )";
				//echo $sql.'<br>';
				if(mysql_query($sql)==false){
					mysql_query("ROLLBACK");
					echo "<p style='color:red'>{$songData['fileName']}<b>写入失败</b></p>";
					print_r(error_get_last());
					continue;
				}

				$count++;
				echo "{$songData['fileName']}<b>写入成功</b><br>";

			}
			echo "<h1>{$songData['songer']}单曲共写入{$count}首.</h1>";
		}else{
			echo "<script>alert('接收到空数据包！！')</script>";
		}
	}

	//酷狗爬虫(歌手单曲页面1-2000页,每页5-30条数据)
	function pachong(){
		for($page = 1;$page<2000; $page++){
			
			sleep(1);	//每次页面请求之前休眠1秒

			$url = "http://www.kugou.com/yy/singer/home/{$page}.html";
			
			do{
				//!!!!!!!!!!!!!!!反复判断IP合理性
				//请求前随机取得一个代理IP
				$count = rand(0,count($ipArr)-1);
				$ip_port = explode(":",$ipArr[$count]);
				if(!empty($ip_port)){
					if(!empty($ip)&&!empty($port)){
						$ip = $ip_port[0];
						$port = $ip_port[1];
						break;
					}else{
						continue;
					}
					
				}else{
					continue;
				}
			}while (!empty($ipArr));
			
			
			
				$songDataList = getSongData(songNH($url,$ip,$port));

				// foreach ($songDataList as $songData) {
				// 	print_r($songData);
				// }

				creatSong($songDataList);
			
		}
	}




	
	function seach($songName){
		$select = "select filename,extname,timelength,hash from pachong_kugou where songname like '{$songName}%' ";
		if($result = mysql_query($select)){
			while($row = mysql_fetch_assoc($result)){
				//var_dump($row);
				foreach ($row as $key => $value) {
					$songData[$key] = $value;
				}
				$key = md5($songData['hash']."kgcloud");
				$songData['link'] = "http://trackercdn.kugou.com/i/?cmd=3&acceptMp3=1&pid=6&hash={$songData['hash']}&key={$key}";
				$songList[] = $songData;
			}
			return $songList;
		}
	
	}


pachong();
//var_dump(seach("爱"));