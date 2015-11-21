<?php
namespace Splitice\SslStore;

class SslStoreApiClient
{
	private $ch;
	private $host;

	public function __construct($cert, $key, $ca, $host)
	{
		$this->host = $host;
		$this->ch = curl_init();

		curl_setopt($this->ch, CURLOPT_SSLCERT, $cert);
		curl_setopt($this->ch, CURLOPT_SSLKEY, $key);
		curl_setopt($this->ch, CURLOPT_CAINFO, $ca);
		curl_setopt($this->ch, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, 10);
		curl_setopt($this->ch, CURLOPT_TIMEOUT, 20);
	}

	function __destruct()
	{
		curl_close($this->ch);
	}

	public function upload_data($remote_path, $data, $verify = true)
	{
		if (empty($remote_path)) {
			throw new \Exception('$remote_path must be provided');
		}

		if ($remote_path{0} != '/') {
			$remote_path = '/' . $remote_path;
		}

		curl_setopt($this->ch, CURLOPT_URL, 'https://' . $this->host . '/api/upload' . $remote_path);
		//curl_setopt($this->ch, CURLOPT_PUT, true);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, "PUT");
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, $data);

		$ret = curl_exec($this->ch);

		if ($ret === false) {
			throw new \Exception('Curl Error: ' . curl_error($this->ch));
		}

		curl_setopt($this->ch, CURLOPT_POSTFIELDS, null);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, null);
		curl_setopt($this->ch, CURLOPT_HTTPGET, true);

		$code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

		if ($code == 201) {
			if ($verify) {
				$server_md5 = $this->md5_file($remote_path);
				if ($server_md5 != md5($data)) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	public function upload_file($remote_path, $local_file, $verify = true)
	{
		if (empty($remote_path)) {
			throw new \Exception('$remote_path must be provided');
		}

		if ($remote_path{0} != '/') {
			$remote_path = '/' . $remote_path;
		}

		curl_setopt($this->ch, CURLOPT_URL, 'https://' . $this->host . '/api/upload' . $remote_path);
		curl_setopt($this->ch, CURLOPT_PUT, true);

		$fh_res = fopen($local_file, 'r');

		curl_setopt($this->ch, CURLOPT_INFILE, $fh_res);
		curl_setopt($this->ch, CURLOPT_INFILESIZE, filesize($local_file));

		$ret = curl_exec($this->ch);

		if ($ret === false) {
			throw new \Exception('Curl Error: ' . curl_error($this->ch));
		}

		fclose($fh_res);

		curl_setopt($this->ch, CURLOPT_INFILE, null);
		curl_setopt($this->ch, CURLOPT_PUT, false);
		curl_setopt($this->ch, CURLOPT_HTTPGET, true);

		$code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

		if ($code == 201) {
			if ($verify) {
				$server_md5 = $this->md5_file($remote_path);
				if ($server_md5 != md5_file($local_file)) {
					return false;
				}
			}

			return true;
		}

		return false;
	}

	public function md5_file($remote_path)
	{
		if (empty($remote_path)) {
			throw new \Exception('$remote_path must be provided');
		}

		if ($remote_path{0} != '/') {
			$remote_path = '/' . $remote_path;
		}

		curl_setopt($this->ch, CURLOPT_URL, 'https://' . $this->host . '/api/md5' . $remote_path);

		$ret = curl_exec($this->ch);

		if ($ret === false) {
			throw new \Exception('Curl Error: ' . curl_error($this->ch));
		}

		$code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

		if ($code != 200) {
			return false;
		}

		return $ret;
	}

	public function md5_folder($remote_path)
	{
		if (empty($remote_path)) {
			throw new \Exception('$remote_path must be provided');
		}

		if ($remote_path{0} != '/') {
			$remote_path = '/' . $remote_path;
		}

		curl_setopt($this->ch, CURLOPT_URL, 'https://' . $this->host . '/api/md5' . $remote_path);

		$ret = curl_exec($this->ch);

		if ($ret === false) {
			throw new \Exception('Curl Error: ' . curl_error($this->ch));
		}

		$code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

		if ($code != 200) {
			return false;
		}

		$ret = explode("\n", $ret);

		$split = array();

		foreach ($ret as $v) {
			$v = explode(':', trim($v));
			if (count($v) == 2) {
				$split[$v[0]] = $v[1];
			}
		}

		return $split;
	}

	public function md5_list($list_files)
	{
		curl_setopt($this->ch, CURLOPT_URL, 'https://' . $this->host . '/api/md5');

		foreach ($list_files as $k => $v) {
			if ($v{0} != '/')
				$list_files[$k] = '/' . $v;
		}

		curl_setopt($this->ch, CURLOPT_POST, true);
		$post_data = array('files' => implode(',', $list_files));
		curl_setopt($this->ch, CURLOPT_POSTFIELDS, http_build_query($post_data));

		$ret = curl_exec($this->ch);

		curl_setopt($this->ch, CURLOPT_POST, false);

		if ($ret === false) {
			throw new \Exception('Curl Error: ' . curl_error($this->ch));
		}

		$code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);

		if ($code != 200) {
			return false;
		}

		$ret = explode("\n", $ret);

		$split = array();

		foreach ($ret as $v) {
			$v = explode(':', trim($v));
			if (count($v) == 2) {
				$split[$v[0]] = $v[1];
			}
		}

		return $split;
	}

	public function get_file($remote_path)
	{

		if (empty($remote_path)) {
			throw new \Exception('$remote_path must be provided');
		}

		if ($remote_path{0} != '/') {
			$remote_path = '/' . $remote_path;
		}

		curl_setopt($this->ch, CURLOPT_URL, 'https://' . $this->host . '/api/download' . $remote_path);

		$ret = curl_exec($this->ch);

		if ($ret === false) {
			throw new \Exception('Curl Error: ' . curl_error($this->ch));
		}

		return $ret;
	}

	public function delete_file($remote_path)
	{

		if (empty($remote_path)) {
			throw new \Exception('$remote_path must be provided');
		}

		if ($remote_path{0} != '/') {
			$remote_path = '/' . $remote_path;
		}

		curl_setopt($this->ch, CURLOPT_URL, 'https://' . $this->host . '/api/delete' . $remote_path);
		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		$ret = curl_exec($this->ch);

		curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, null);

		if ($ret === false) {
			throw new \Exception('Curl Error: ' . curl_error($this->ch));
		}

		return $ret;
	}
}