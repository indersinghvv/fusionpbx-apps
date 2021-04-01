<?php
//callback for bandwidth
include "../root.php";

require_once "resources/require.php";
require_once "../sms_hook_common.php";

if ($debug) {
	error_log('[SMS] REQUEST: ' .  print_r($_SERVER, true));
}
//change here for any upgradation
$banwidth_token="d336b4add0b3fef41003ce7ad776653e";
$banwidth_secret="1680fdff00a7c0bfe0f360ca2252cbe60eee";
$file_stored_url="https://fdev01.example.com/app/sms/hook/files/";
$local_file_store_path="/var/www/fusionpbx/app/sms/hook/files/";

if (check_acl()) {
	if  ($_SERVER['CONTENT_TYPE'] == 'application/json; charset=utf-8') {

		$data = json_decode(file_get_contents("php://input"),true);
		$body=["body"=>$data[0]['message']['text']];

		if ($data[0]['type']=="message-delivered" && $data[0]['message']['media']) {
			foreach ($data[0]['message']['media'] as $key=> $val) {
				$path=explode("/",$val);
				$filename=basename($path[7]);
				unlink($local_file_store_path.$filename);
			}
		}
        if ($data[0]['type']=="message-received") {
			$attachments=[];
			
            if ($data[0]['message']['media']) {
				
                foreach ($data[0]['message']['media'] as $key=> $val) {
					$file_extension=pathinfo($val, PATHINFO_EXTENSION);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $val);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                    curl_setopt($ch, CURLOPT_USERPWD, $banwidth_token . ':' . $banwidth_secret);

                    $result = curl_exec($ch);
                    if (curl_errno($ch)) {
                        echo 'Error:' . curl_error($ch);
                    }
					curl_close($ch);
					$path=explode(".com",$val);
					$filename=basename($path[1]);
					// $filename=explode(".",$filename);
					unlink($local_file_store_path.$filename);
					$random_number = md5(rand());
					//change for saving bandwidth decrypted form to softphone decypted
					$file_path=$local_file_store_path.$random_number.'.'.$file_extension;
                    $fp = fopen($file_path, 'w') or die("Unable to open file!");//opens file in append mode
                    fwrite($fp, $result);
					fclose($fp);
					$file_mime='image/jpeg';
					if ($file_extension=="ogg") {
						$file_mime='audio/ogg';
					}elseif($file_extension=="mp4"){
						$file_mime='video/mp4';
					}else{
						$file_mime=mime_content_type($file_path);
					}
					
					$content =file_get_contents($file_path);
					$cipher_method = 'aes-128-ctr';
  					$enc_key = openssl_digest(php_uname(), 'md5', TRUE);
  					
  					$enc_iv = pack("H*", "00000000000000000000000000000000");
  					$inputKey=bin2hex($enc_key);
					$encrypted = openssl_encrypt($content, $cipher_method, $enc_key, 1, $enc_iv);
					$fp = fopen($local_file_store_path.$random_number.'.'.$file_extension, 'w') or die("Unable to open file!");//opens file in append mode
                    fwrite($fp, $encrypted);
                    fclose($fp);
					// json format for cloud softphone
					$z[]=['content-url'=>$file_stored_url.$random_number.'.'.$file_extension,
					'content-type'=>$file_mime,
					'encryption-key'=>$inputKey
				];
                    $attachments=['attachments'=>$z];
                }
            }
        }
		$body=array_merge($body,$attachments);
		$body=json_encode($body,JSON_UNESCAPED_SLASHES);
		
		if ($debug) {
			//path to see error log -->/var/log/nginx
			$data1=json_encode($data);
			error_log('[SMS] REQUEST: ' .  print_r($data1, true));
		}
		$attachments=json_encode($data[0]['message']['media']);
		if($data[0]['type']=="message-received"){
			route_and_send_sms($data[0]['message']['from'], $data[0]['message']['owner'], $body,$attachments);
		}
		return $response->withStatus(200)
        ->withHeader('Content-Type', 'application/json')
        ->write(json_encode($data));

	} else {
		error_log('[SMS] REQUEST: No SMS Data Received');
		die("no");
	}
} else {
	error_log('ACCESS DENIED [SMS]: ' .  print_r($_SERVER['REMOTE_ADDR'], true));
	die("access denied");
}
?>
