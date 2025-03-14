<?php
class ControllerExtensionStartupSlaSoftRedirect extends Controller {
	private $file_cache = 'redirect';
	private $expire = 864000;
	
	public function index() {
		if (!$this->config->get('slasoft_redirect_status')) return;
		$request_uri = urldecode($this->request->server['REQUEST_URI']);
		$url = parse_url($request_uri);
		
		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		$from_url = '';
		if (!empty($url['path'])) {
			$from_url = ltrim($url['path'],'/');
		}
		if (!empty($url['query'])) {
			$from_url .= '?' . $url['query'];
		}
		$cache_data = $this->getCache();
		if (!$cache_data) {
			$sql = "SELECT from_url, to_url, code FROM `" . DB_PREFIX . "slasoft_redirect` WHERE status = 1 AND code = 403";
			$result = $this->db->query($sql);
			$cache_data = array();
			foreach ($result->rows as $row) {
				$cache_data[$row['from_url']] = array(
					'to_url' =>  $row['to_url'],
					'code' => $row['code'],
				);
			}
			$this->setCache($cache_data);
		}
		if (!empty($cache_data[$from_url])) {
			$to_url = $server . $cache_data[$from_url]['to_url'];
			$code = $cache_data[$from_url]['code'];
			$this->goRedirect(array(
				'code'     => $code,
				'to_url'   => $to_url,
				'from_url' => $from_url
			));
		} else {
			foreach ($cache_data as $key_from_url => $to_code) {
				$pos_reg = strpos($key_from_url, '#');
				if ($pos_reg === 0) {
					if (preg_match($key_from_url, $from_url)) {
						$to_url = $server . $to_code['to_url'];
						$code = $to_code['code'];
						$this->goRedirect(array(
							'code'     => $code,
							'to_url'   => $to_url,
							'from_url' => $key_from_url
						));
					}
				}
			}
		}
	}
	
	protected function goRedirect($data) {
		$this->addLog($data['from_url']);
		switch ($data['code']) {
			case 302:
			case 301:
			case 307: if ($data['to_url']) {
						header('Location: ' . str_replace('&amp;','&',$data['to_url']), true, $data['code']); exit;
					}
					break;
			case 404: return; break;
			case 410: $this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 410 Gone');
					break;
			case 403: header($_SERVER["SERVER_PROTOCOL"] . ' 403 Forbidden'); exit;
				break;
		}
	}

	protected function addLog($from_url) {
		$sql = "UPDATE " . DB_PREFIX . "slasoft_redirect SET
		cnt = cnt + 1,
		last_date = NOW()
		WHERE from_url = '" . $this->db->escape($from_url) . "'";
		$this->db->query($sql);
	}
	
	protected function getCache() {
		if(!isset($this->cache)) {
			$this->cache= new Cache($this->config->get('cache_engine'), $this->config->get('cache_expire'));
		}
		return $this->cache->get($this->file_cache);
	}

	protected function setCache($data=array()) {
		if(!isset($this->cache)) {
			$this->cache= new Cache($this->config->get('cache_engine'), $this->config->get('cache_expire'));
		}
		if ($data)
			$this->cache->set($this->file_cache, $data);
	}

	public function getUrl() {
		if ($this->request->server['REQUEST_METHOD'] == 'POST') {
			$query = base64_decode($this->request->post['query']);
			$queries = json_decode($query,true);
			if ($queries) {
				$sql = "SELECT * FROM " . DB_PREFIX . "language ";
				$results = $this->db->query($sql);
				if ($results->num_rows) {
					$languages= array();
					foreach ($results->rows as $row) {
						$languages[$row['language_id']] = $row['code'];
					}

					$servers['config_ssl'] = $this->config->get('config_ssl');
					$servers['config_url'] = $this->config->get('config_url');

					foreach ($queries as $language_id=>$link) {
						$this->config->set('config_language_id', $language_id);
						$this->session->data['language'] = $languages[$language_id];
						$url = $this->url->link($link);
						
						$url = str_replace('index.php?','',str_replace(array($servers['config_url'],$servers['config_ssl']),'',$url));
						$urls[$language_id] = $url;
					}
					echo (base64_encode(json_encode($urls)));
				}
			}
		}
	}

	public function redirect301(&$view, &$data, &$output) {
		if (!$this->config->get('slasoft_redirect_status')) return;

		$request_uri = urldecode($this->request->server['REQUEST_URI']);
		$url = parse_url($request_uri);
		
		if ($this->request->server['HTTPS']) {
			$server = $this->config->get('config_ssl');
		} else {
			$server = $this->config->get('config_url');
		}

		$from_url = '';
		if (!empty($url['path'])) {
			$from_url = ltrim($url['path'],'/');
		}
		if (!empty($url['query'])) {
			$from_url .= '?' . $url['query'];
		}

		$sql = "SELECT to_url, code, from_url FROM " . DB_PREFIX . "slasoft_redirect 
		WHERE '" . $this->db->escape($from_url) . "' = from_url AND code <> 403 AND status=1 LIMIT 1";
		$query = $this->db->query($sql);
		$regexp = false;
		if (!$query->num_rows) {
			$sql = "SELECT to_url, code, from_url FROM " . DB_PREFIX . "slasoft_redirect 
			WHERE '" . $this->db->escape($from_url) . "' REGEXP from_url AND code <> 403 AND status=1 LIMIT 1";
			$query = $this->db->query($sql);
			if ($query->num_rows) { $regexp = true;}
		}
		if ($query->num_rows) {
			if (preg_match('#^(http|https):\/\/#', $query->row['to_url'])) {
				$to_url = $query->row['to_url'];
			} else {
				if ($this->request->server['HTTPS']) {
					$server = $this->config->get('config_ssl');
				} else {
					$server = $this->config->get('config_url');
				}
				if ($regexp) {
					if (strpos($query->row['to_url'],'$') !==false) {
						$to_url = preg_replace('#' . $query->row['from_url'] . '#', $query->row['to_url'], $from_url);
					} else {
						$to_url = $query->row['to_url'];
					}
				} else {
					$to_url = $query->row['to_url'];
				}
				
				if ($to_url) {
					$to_url = $server . ltrim($to_url,'/');
				}
			}
			$code = $query->row['code'];
				$this->goRedirect(array(
					'code'=> $code,
					'to_url'=> $to_url,
					'from_url'=> $query->row['from_url']
				));
		}
	}
}