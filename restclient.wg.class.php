<?php 
/**
 * Webgalamb REST Client
 * 
 * 2020 ENS Zrt.
 */
class WebgalambRestClient {

    private $options;
    private $handle;
    public $response;
    public $headers;
    public $info;
    public $error;
    private $response_status_lines;
    
    public function __construct($api_url, $token, $secret) {
		$stamp = date('c');
		$sig = hash_hmac('SHA256', $stamp, $secret);
		
        $this->options = [
            'headers' => [
				'X-Auth-Token' => $token,
				'X-Auth-Signature' => $sig,
				'X-Auth-Timestamp' => $stamp
			], 
            'curl_options' => [],
            'base_url' => $api_url
        ];
    }
    
    public function get($url, $parameters=[]) {
        return $this->execute($url, 'GET', $parameters);
    }
    
    public function post($url, $parameters=[]) {
        return $this->execute($url, 'POST', $parameters);
    }
    
    private function execute($url, $method='GET', $parameters=[], $headers=[]) {
		$parameters = ['parameters' => json_encode($parameters)];
        $parameters_string = http_build_query($parameters);
        $client = clone $this;
        $client->url = $client->options['base_url'] .'?request=' . $url . '/';
        $client->handle = curl_init();
        $curlopt = [
            CURLOPT_HEADER => TRUE, 
            CURLOPT_RETURNTRANSFER => TRUE, 
            CURLOPT_USERAGENT => 'WG RestClient/1.1'
        ];
        
		$curlopt[CURLOPT_HTTPHEADER] = [];
		foreach($client->options['headers'] as $key => $values) {
			foreach(is_array($values)? $values : [$values] as $value) {
				$curlopt[CURLOPT_HTTPHEADER][] = sprintf("%s:%s", $key, $value);
			}
		}
        
        if(strtoupper($method) == 'POST') {
            $curlopt[CURLOPT_POST] = TRUE;
            $curlopt[CURLOPT_POSTFIELDS] = $parameters_string;
        } elseif(strtoupper($method) != 'GET') {
            $curlopt[CURLOPT_CUSTOMREQUEST] = strtoupper($method);
            $curlopt[CURLOPT_POSTFIELDS] = $parameters_string;
        } elseif($parameters_string) {
            $client->url .= $parameters_string;
        }
		
        $curlopt[CURLOPT_URL] = $client->url;
        
        if($client->options['curl_options']) {
            foreach($client->options['curl_options'] as $key => $value) {
                $curlopt[$key] = $value;
            }
        }
        curl_setopt_array($client->handle, $curlopt);
        
        $client->parse_response(curl_exec($client->handle));
        $client->info = (object) curl_getinfo($client->handle);
        $client->error = curl_error($client->handle);
        
        curl_close($client->handle);
		
        return $this->decode_response($client);
    }
    
    private function parse_response($response) {
        $headers = [];
        $this->response_status_lines = [];
        $line = strtok($response, "\n");
        do {
            if(strlen(trim($line)) == 0) {
                if(count($headers) > 0) break;
            } elseif(strpos($line, 'HTTP') === 0) {
                $this->response_status_lines[] = trim($line);
            } else { 
                list($key, $value) = explode(':', $line, 2);
                $key = trim(strtolower(str_replace('-', '_', $key)));
                $value = trim($value);
                
                if(empty($headers[$key]))
                    $headers[$key] = $value;
                elseif(is_array($headers[$key]))
                    $headers[$key][] = $value;
                else
                    $headers[$key] = [$headers[$key], $value];
            }
        } 
		while($line = strtok("\n"));
        
        $this->headers = (object) $headers;
        $this->response = strtok("");
    }
    
    private function decode_response($client=false) {
		if($client->error) {
			$decoded_response = $client->error;
		} elseif($client->info->http_code != 200) {
			$decoded_response = ['error' => 'HTTP error: '.$client->info->http_code];
		} else {
			$decoded_response = json_decode($client->response, 1);
			if(isset($decoded_response['results']))
				$decoded_response = $decoded_response['results'];
		}
        
        return $decoded_response;
    }
}
