<?php

function decryptFile($encrypt_method,$key,$iv,$filePath,$encrypted_file_name,$content_type){
		$encrypted_file_path=$filePath."/".$encrypted_file_name;
		$encrypted =file_get_contents($encrypted_file_path);
		$output = openssl_decrypt($encrypted, $encrypt_method, $key, OPENSSL_RAW_DATA, $iv);
		$decrypted_file_path="check_content_type";
		//content type --> audio/mp3
		$file_extension=explode('/',$content_type);
		$decrypted_file_path=$filePath."/".$encrypted_file_name."d.".$file_extension[1];

		$myfile = fopen($decrypted_file_path, "w") or die("Unable to open file!");
		fwrite($myfile, $output);
		fclose($myfile);
		unlink($encrypted_file_path);
	return $decrypted_file_path;
	
}

$encrypt_method = $_POST['encrypt_method'];
$content_type = $_POST['content_type'];
$key = hex2bin($_POST['key']);
$iv = hex2bin($_POST['iv']);
$filePath=$_POST['filePath'];
$encrypted_file_name=$_POST['encrypted_file_name'];
// echo 'encrypt_method-'.$encrypt_method."\n";
// echo 'key-'.$_POST['key']."\n";
// echo 'iv-'.$_POST['iv']."\n";
// echo 'filepath-'.$_POST['filePath']."\n";
// echo 'encrypted file name-'.$_POST['encrypted_file_name']."\n";
$result= decryptFile($encrypt_method,$key,$iv,$filePath,$encrypted_file_name,$content_type);
echo $result;
?>