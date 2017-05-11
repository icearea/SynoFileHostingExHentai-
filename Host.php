<?php
class SynoFileHostingExHentai {
	private $Url;
	private $Username;
	private $Password;
	private $HostInfo;
	private $EXHENTAI_COOKIE = '/tmp/exhentai.cookie';
	private $EXHENTAI_LOGIN_URL = 'https://forums.e-hentai.org/index.php?act=Login&CODE=01';
	private $EXHENTAI = 'exhentai';
	private $EXHENTAI_FREE_KEYWORD = 'Free!';
	public function __construct($Url, $Username, $Password, $HostInfo) {
		$this->Url = $Url;
		$this->Username = $Username;
		$this->Password = $Password;
		$this->HostInfo = $HostInfo;
	}
	//This function returns download url.
	public function GetDownloadInfo() {
		$ret = FALSE;
		$VerifyRet = $this->Verify(FALSE);
		if (LOGIN_FAIL != $VerifyRet) {
			$ret = $this->DownloadFree();
		}
		if (file_exists($this->EXHENTAI_COOKIE)) {
			unlink($this->EXHENTAI_COOKIE);
		}
		return $ret;
	}
	//This function verifies and returns account type.
	public function Verify($ClearCookie)
	{
		$ret = LOGIN_FAIL;
		if (!empty($this->Username) && !empty($this->Password)) {
			$this->CookieValue = $this->ExHentaiLogin($this->Username, $this->Password);
			if(FALSE != $this->CookieValue){
				$ret = USER_IS_PREMIUM;
			}
		}
		if ($ClearCookie && file_exists($this->EXHENTAI_COOKIE)) {
			unlink($this->EXHENTAI_COOKIE);
		}
		return $ret;
	}
	//This function performs login action.
	private function ExHentaiLogin($Username, $Password) {
		$ret = FALSE;
		//Save cookie file
		$PostData = array('UserName'=>$this->Username,
						  'PassWord'=>$this->Password,
						  'CookieDate'=>'1',
						  'b'=>'d',
						  'bt'=>'1-1');
		$queryUrl = $this->EXHENTAI_LOGIN_URL;
		$PostData = http_build_query($PostData);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $PostData);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->EXHENTAI_COOKIE);
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($curl, CURLOPT_HEADER, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_URL, $queryUrl);
		$LoginInfo = curl_exec($curl);
		curl_close($curl);
		if (FALSE != $LoginInfo && file_exists($this->EXHENTAI_COOKIE)) {
			preg_match('/ipb_member_id=(0|[1-9][0-9]*);/', $LoginInfo, $ipb_member_id);
			preg_match('/ipb_pass_hash=([0-9a-f]{32});/', $LoginInfo, $ipb_pass_hash);
			if(isset($ipb_member_id[1]) && isset($ipb_pass_hash[1])) {
				$ret = 'ipb_member_id='.$ipb_member_id[1].';ipb_pass_hash='.$ipb_pass_hash[1];
			}
		}
		return $ret;
	}
	//This function get free download url.
	private function DownloadFree() {
		$DownloadPage = $this->GetDownloadPage($this->Url);
		$ret = array();
		if (FALSE == $DownloadPage) {
			$ret[DOWNLOAD_ERROR] = ERR_NOT_SUPPORT_TYPE;
			return $ret;
		}
		$DownloadFree = $this->IsDownloadFree($DownloadPage);
		if (FALSE == $DownloadFree) {
			$ret[DOWNLOAD_ERROR] = ERR_REQUIRED_PREMIUM;
			return $ret;
		}
		$DownloadUrl = $this->QueryDownloadUrl($DownloadPage);
		if(FALSE == $DownloadUrl) {
			$ret[DOWNLOAD_ERROR] = ERR_FILE_NO_EXIST;
		}
		else {
			$ret[DOWNLOAD_URL] = trim($DownloadUrl);
		}
		return $ret;
	}
	private function GetDownloadPage($Url) {
		$ret = FALSE;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
		curl_setopt($curl, CURLOPT_COOKIE, $this->CookieValue);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $this->EXHENTAI_COOKIE);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->EXHENTAI_COOKIE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_URL, $Url);
		$InfoRet = curl_exec($curl);
		curl_close($curl);
		preg_match('/popUp\(\'(.*)\',480,320\)">Archive Download/', $InfoRet, $match);
		if (isset($match[1])) {
			$ret = str_replace("&amp;", "&", $match[1]);
		}
		return $ret;
	}
	
	private function IsDownloadFree($Url) {
		$ret = FALSE;
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
		curl_setopt($curl, CURLOPT_COOKIE, $this->CookieValue);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $this->EXHENTAI_COOKIE);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->EXHENTAI_COOKIE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_URL, $Url);
		$InfoRet = curl_exec($curl);
		curl_close($curl);
		preg_match('/Download Cost: &nbsp; <strong>(.*)<\/strong>/', $InfoRet, $match);
		if (isset($match[1])) {
			if (strstr($match[1], $this->EXHENTAI_FREE_KEYWORD)) {
				$ret = TRUE;
			}
		}
		return $ret;
	}
	private function QueryDownloadUrl($Url) {
		
		$ret = FALSE;
		$PostData = array('dltype'=>'org',
						  'dlcheck'=>'Download Original Archive');
		$PostData = http_build_query($PostData);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, TRUE);
		curl_setopt($curl, CURLOPT_USERAGENT, DOWNLOAD_STATION_USER_AGENT);
		curl_setopt($curl, CURLOPT_POST, TRUE);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $PostData);
		curl_setopt($curl, CURLOPT_COOKIE, $this->CookieValue);
		curl_setopt($curl, CURLOPT_COOKIEFILE, $this->EXHENTAI_COOKIE);
		curl_setopt($curl, CURLOPT_COOKIEJAR, $this->EXHENTAI_COOKIE);
		curl_setopt($curl, CURLOPT_HEADER, TRUE);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($curl, CURLOPT_URL, $Url);
		$QueryInfo = curl_exec($curl);
		curl_close($curl);
		preg_match('/document.location = "(.*)";/', $QueryInfo, $match);
		if (isset($match[1])) {
			$ret = $match[1].'?start=1';
		}
		return $ret;
	}
}
?>